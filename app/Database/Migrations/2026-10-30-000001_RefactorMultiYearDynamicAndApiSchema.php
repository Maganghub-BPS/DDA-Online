<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorMultiYearDynamicAndApiSchema extends Migration
{
    public function up()
    {
        $db = $this->db;
        $forge = $this->forge;

        // =========================================================================
        // 1. NORMALISASI CHARSET & COLLATION KE utf8mb4
        // =========================================================================
        if ($db->tableExists('m_list_tabel')) {
            $db->query("ALTER TABLE m_list_tabel CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        }
        if ($db->tableExists('t_tahun_tabel')) {
            $db->query("ALTER TABLE t_tahun_tabel CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        }

        // =========================================================================
        // 2. PERUBAHAN STRUKTUR m_list_tabel
        // =========================================================================
        if ($db->tableExists('m_list_tabel')) {
            $fieldsToAdd = [];
            if (!$db->fieldExists('no_tabel', 'm_list_tabel')) {
                $fieldsToAdd['no_tabel'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'id_unitkerja',
                    'comment'    => 'Nomor tabel baku (misal 1.1.1)',
                ];
            }
            if (!$db->fieldExists('jenis_sumber', 'm_list_tabel')) {
                $fieldsToAdd['jenis_sumber'] = [
                    'type'       => 'ENUM',
                    'constraint' => ['api_kominfo', 'dinamis'],
                    'default'    => 'api_kominfo',
                    'after'      => 'judul_en',
                    'comment'    => 'Pilihan sumber data baku master tabel',
                ];
            }
            if (!$db->fieldExists('sumber_data', 'm_list_tabel')) {
                $fieldsToAdd['sumber_data'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'kondef',
                    'comment'    => 'Teks sumber kaki tabel',
                ];
            }
            if (!$db->fieldExists('is_active', 'm_list_tabel')) {
                $fieldsToAdd['is_active'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'sumber_data',
                ];
            }
            if (!empty($fieldsToAdd)) {
                $forge->addColumn('m_list_tabel', $fieldsToAdd);
            }

            // Isi m_list_tabel.no_tabel dari data terbaru jika kosong
            $db->query("
                UPDATE m_list_tabel lt
                LEFT JOIN (
                    SELECT tt.id_tabel, COALESCE(NULLIF(i.no_tabel, ''), NULLIF(tt.no_tabel, '')) AS best_no
                    FROM t_tahun_tabel tt
                    LEFT JOIN m_indikator_tabel i ON i.id_tahun_tabel = tt.id
                    WHERE (i.no_tabel IS NOT NULL AND i.no_tabel != '') OR (tt.no_tabel IS NOT NULL AND tt.no_tabel != '')
                    ORDER BY tt.id DESC
                ) sub ON sub.id_tabel = lt.id
                SET lt.no_tabel = sub.best_no
                WHERE (lt.no_tabel IS NULL OR lt.no_tabel = '') AND sub.best_no IS NOT NULL;
            ");

            // Isi m_list_tabel.kondef dari m_kondef jika masih kosong
            if ($db->tableExists('m_kondef') && $db->fieldExists('id_tabel', 'm_kondef')) {
                // Jika id_tabel di m_kondef merujuk ke t_tahun_tabel.id
                $db->query("
                    UPDATE m_list_tabel lt
                    JOIN t_tahun_tabel tt ON tt.id_tabel = lt.id
                    JOIN m_kondef k ON k.id_tabel = tt.id
                    SET lt.kondef = k.kondef
                    WHERE (lt.kondef IS NULL OR lt.kondef = '' OR lt.kondef = '-') 
                      AND k.kondef IS NOT NULL AND k.kondef != '' AND k.kondef != '-';
                ");
                // Atau jika id_tabel di m_kondef merujuk ke m_list_tabel.id
                $db->query("
                    UPDATE m_list_tabel lt
                    JOIN m_kondef k ON k.id_tabel = lt.id
                    SET lt.kondef = k.kondef
                    WHERE (lt.kondef IS NULL OR lt.kondef = '' OR lt.kondef = '-') 
                      AND k.kondef IS NOT NULL AND k.kondef != '' AND k.kondef != '-';
                ");
            }

            // Set jenis_sumber = 'dinamis'
            $unitKerjaDinamis = [
                'bps', 'sosial', 'produksi', 'distribusi', 'nerwilis', 'ipds', 'pertanian', 'bkn', 'bpn',
                'kemenkumham', 'bi', 'kai', 'pln', 'bulog', 'klimatologi', 'ojk', 'polda', 'kejaksaan',
                'pengtinggi', 'pengadilanagama', 'ptun', 'kemenag', 'bkkbn', 'kpu', 'taspen', 'ptpn_ix',
                'bpdas', 'perhutani', 'kadin', 'DJBC', 'tanjungemas', 'achmadyani', 'pos', 'pegadaian',
                'djp1jateng', 'djp2jateng', 'djpb', 'bpkp', 'bpom', 'bpjs', 'lldikti', 'kopertaisx',
                'koni', 'imigrasi', 'lapas'
            ];
            $quotedUnit = "'" . implode("','", $unitKerjaDinamis) . "'";

            $db->query("
                UPDATE m_list_tabel lt
                SET lt.jenis_sumber = 'dinamis'
                WHERE lt.id IN (SELECT DISTINCT tt.id_tabel FROM m_indikator_tabel i JOIN t_tahun_tabel tt ON tt.id = i.id_tahun_tabel)
                   OR lt.id IN (SELECT DISTINCT id_tabel FROM t_tahun_tabel WHERE is_dynamic = 1)
                   OR lt.id_unitkerja IN ({$quotedUnit});
            ");

            // Bersihkan akhiran tahun statis pada judul_ind dan judul_en secara aman
            $allTables = $db->table('m_list_tabel')->select('id, id_unitkerja, judul_ind, judul_en')->get()->getResult();
            $patternYear = '/,?\s*(Tahun\s+|Year\s+)?(19|20)\d{2}(\s*[-–\/]\s*(19|20)\d{2})?\s*$/i';

            foreach ($allTables as $t) {
                $cleanInd = trim(preg_replace($patternYear, '', $t->judul_ind));
                $cleanEn  = !empty($t->judul_en) ? trim(preg_replace($patternYear, '', $t->judul_en)) : null;

                if ($cleanInd !== '' && $cleanInd !== $t->judul_ind) {
                    // Cek apakah judul bersih bentrok dengan baris lain di OPD yang sama
                    $conflict = $db->table('m_list_tabel')
                        ->where('id !=', $t->id)
                        ->where('id_unitkerja', $t->id_unitkerja)
                        ->where('judul_ind', $cleanInd)
                        ->countAllResults();

                    if ($conflict === 0) {
                        $updateData = ['judul_ind' => $cleanInd];
                        if ($cleanEn !== null) {
                            $updateData['judul_en'] = $cleanEn;
                        }
                        $db->table('m_list_tabel')->where('id', $t->id)->update($updateData);
                    }
                }
            }
        }

        // =========================================================================
        // 3. PEMBUATAN & MIGRASI TABEL m_konfigurasi_api
        // =========================================================================
        if (!$db->tableExists('m_konfigurasi_api')) {
            $db->query("
                CREATE TABLE IF NOT EXISTS m_konfigurasi_api (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    id_tabel INT NOT NULL UNIQUE,
                    id_api VARCHAR(100) NULL,
                    link_api TEXT NULL,
                    config_tabel LONGTEXT NULL,
                    cached_json LONGTEXT NULL,
                    tahun_tersedia VARCHAR(100) NULL,
                    last_sync_at DATETIME NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    CONSTRAINT fk_konfigurasi_api_tabel FOREIGN KEY (id_tabel) REFERENCES m_list_tabel(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }

        // Migrasikan link API dari t_tahun_tabel terbaru
        $now = date('Y-m-d H:i:s');
        $apiTables = $db->query("
            SELECT tt.id_tabel, tt.link_tabel
            FROM t_tahun_tabel tt
            INNER JOIN (
                SELECT id_tabel, MAX(id) AS max_id 
                FROM t_tahun_tabel 
                WHERE link_tabel IS NOT NULL AND link_tabel != '' 
                GROUP BY id_tabel
            ) latest ON tt.id = latest.max_id
        ")->getResult();

        foreach ($apiTables as $at) {
            $link = trim($at->link_tabel);
            $idApi = null;
            if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $link, $matches)) {
                $idApi = $matches[0];
            }

            // Cek jika t_tabel_match memiliki pemetaan id_api_new
            if ($idApi && $db->tableExists('t_tabel_match') && $db->fieldExists('id_api', 't_tabel_match')) {
                $match = $db->table('t_tabel_match')->where('id_api', $idApi)->get()->getRow();
                if ($match && !empty($match->id_api_new) && (int)$match->id_api_new > 0) {
                    $idApi = (string)$match->id_api_new;
                }
            }

            $existing = $db->table('m_konfigurasi_api')->where('id_tabel', $at->id_tabel)->get()->getRow();
            if (!$existing) {
                $db->table('m_konfigurasi_api')->insert([
                    'id_tabel'    => $at->id_tabel,
                    'id_api'      => $idApi,
                    'link_api'    => $link,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }

        // =========================================================================
        // 4. PERUBAHAN STRUKTUR & SEEDING m_periode_kelompok & m_periode_item
        // =========================================================================
        if ($db->tableExists('m_periode_kelompok')) {
            $pkFields = [];
            if (!$db->fieldExists('kategori_periode', 'm_periode_kelompok')) {
                $pkFields['kategori_periode'] = [
                    'type'       => 'ENUM',
                    'constraint' => ['standar', 'kustom', 'arsip_tahunan'],
                    'default'    => 'kustom',
                    'after'      => 'judul_periode_en',
                ];
            }
            if (!$db->fieldExists('kode_standar', 'm_periode_kelompok')) {
                $pkFields['kode_standar'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'kategori_periode',
                ];
            }
            if (!empty($pkFields)) {
                $forge->addColumn('m_periode_kelompok', $pkFields);
            }
        }

        if ($db->tableExists('m_periode_item')) {
            if (!$db->fieldExists('tipe_item', 'm_periode_item')) {
                $forge->addColumn('m_periode_item', [
                    'tipe_item' => [
                        'type'       => 'ENUM',
                        'constraint' => ['periode', 'jumlah', 'rata_rata'],
                        'default'    => 'periode',
                        'after'      => 'nama_periode_en',
                    ],
                ]);
            }
        }

        // Seed 5 Kelompok Periode Standar
        // 1. Bulanan
        $cekBulanan = $db->table('m_periode_kelompok')->where('kode_standar', 'bulanan')->get()->getRow();
        if (!$cekBulanan) {
            $db->table('m_periode_kelompok')->insert([
                'nama_kelompok_ind' => 'Bulanan (Januari - Desember)',
                'nama_kelompok_en'  => 'Monthly (January - December)',
                'judul_periode_ind' => 'Bulan',
                'judul_periode_en'  => 'Month',
                'kategori_periode'  => 'standar',
                'kode_standar'      => 'bulanan',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $idBln = $db->insertID();
            $bulanItems = [
                ['Januari', 'January'], ['Februari', 'February'], ['Maret', 'March'],
                ['April', 'April'], ['Mei', 'May'], ['Juni', 'June'],
                ['Juli', 'July'], ['Agustus', 'August'], ['September', 'September'],
                ['Oktober', 'October'], ['November', 'November'], ['Desember', 'December']
            ];
            foreach ($bulanItems as $idx => $b) {
                $db->table('m_periode_item')->insert([
                    'id_kelompok'      => $idBln,
                    'nama_periode_ind' => $b[0],
                    'nama_periode_en'  => $b[1],
                    'tipe_item'        => 'periode',
                    'no_urut'          => $idx + 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // 2. Triwulanan
        $cekTriwulan = $db->table('m_periode_kelompok')->where('kode_standar', 'triwulanan')->get()->getRow();
        if (!$cekTriwulan) {
            $db->table('m_periode_kelompok')->insert([
                'nama_kelompok_ind' => 'Triwulanan (Triwulan I - IV)',
                'nama_kelompok_en'  => 'Quarterly (Quarter I - IV)',
                'judul_periode_ind' => 'Triwulan',
                'judul_periode_en'  => 'Quarter',
                'kategori_periode'  => 'standar',
                'kode_standar'      => 'triwulanan',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $idTw = $db->insertID();
            $twItems = [
                ['Triwulan I', 'Quarter I'], ['Triwulan II', 'Quarter II'],
                ['Triwulan III', 'Quarter III'], ['Triwulan IV', 'Quarter IV']
            ];
            foreach ($twItems as $idx => $tw) {
                $db->table('m_periode_item')->insert([
                    'id_kelompok'      => $idTw,
                    'nama_periode_ind' => $tw[0],
                    'nama_periode_en'  => $tw[1],
                    'tipe_item'        => 'periode',
                    'no_urut'          => $idx + 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // 3. Semesteran
        $cekSemester = $db->table('m_periode_kelompok')->where('kode_standar', 'semesteran')->get()->getRow();
        if (!$cekSemester) {
            $db->table('m_periode_kelompok')->insert([
                'nama_kelompok_ind' => 'Semesteran (Semester I - II)',
                'nama_kelompok_en'  => 'Semesterly (Semester I - II)',
                'judul_periode_ind' => 'Semester',
                'judul_periode_en'  => 'Semester',
                'kategori_periode'  => 'standar',
                'kode_standar'      => 'semesteran',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $idSm = $db->insertID();
            $smItems = [
                ['Semester I', 'Semester I'], ['Semester II', 'Semester II']
            ];
            foreach ($smItems as $idx => $sm) {
                $db->table('m_periode_item')->insert([
                    'id_kelompok'      => $idSm,
                    'nama_periode_ind' => $sm[0],
                    'nama_periode_en'  => $sm[1],
                    'tipe_item'        => 'periode',
                    'no_urut'          => $idx + 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // 4. Maret dan September (id = 31)
        $g31 = $db->table('m_periode_kelompok')->where('id', 31)->get()->getRow();
        if ($g31) {
            $db->table('m_periode_kelompok')->where('id', 31)->update([
                'kategori_periode' => 'standar',
                'kode_standar'     => 'maret_september',
            ]);
        } else {
            $cekMs = $db->table('m_periode_kelompok')->where('kode_standar', 'maret_september')->get()->getRow();
            if (!$cekMs) {
                $db->table('m_periode_kelompok')->insert([
                    'id'                => 31,
                    'nama_kelompok_ind' => 'Maret dan September',
                    'nama_kelompok_en'  => 'March and September',
                    'judul_periode_ind' => 'Periode',
                    'judul_periode_en'  => 'Period',
                    'kategori_periode'  => 'standar',
                    'kode_standar'      => 'maret_september',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
                $db->table('m_periode_item')->insertBatch([
                    [
                        'id' => 122, 'id_kelompok' => 31, 'nama_periode_ind' => 'Maret',
                        'nama_periode_en' => 'March', 'tipe_item' => 'periode', 'no_urut' => 1,
                        'created_at' => $now, 'updated_at' => $now
                    ],
                    [
                        'id' => 123, 'id_kelompok' => 31, 'nama_periode_ind' => 'September',
                        'nama_periode_en' => 'September', 'tipe_item' => 'periode', 'no_urut' => 2,
                        'created_at' => $now, 'updated_at' => $now
                    ]
                ]);
            }
        }

        // 5. Februari dan Agustus
        $cekFa = $db->table('m_periode_kelompok')->where('kode_standar', 'februari_agustus')->get()->getRow();
        if (!$cekFa) {
            $db->table('m_periode_kelompok')->insert([
                'nama_kelompok_ind' => 'Februari dan Agustus',
                'nama_kelompok_en'  => 'February and August',
                'judul_periode_ind' => 'Periode',
                'judul_periode_en'  => 'Period',
                'kategori_periode'  => 'standar',
                'kode_standar'      => 'februari_agustus',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $idFa = $db->insertID();
            $faItems = [
                ['Februari', 'February'], ['Agustus', 'August']
            ];
            foreach ($faItems as $idx => $fa) {
                $db->table('m_periode_item')->insert([
                    'id_kelompok'      => $idFa,
                    'nama_periode_ind' => $fa[0],
                    'nama_periode_en'  => $fa[1],
                    'tipe_item'        => 'periode',
                    'no_urut'          => $idx + 1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // =========================================================================
        // 5. PERUBAHAN STRUKTUR & DEDUPLIKASI m_indikator_tabel
        // =========================================================================
        if ($db->tableExists('m_indikator_tabel')) {
            $indFields = [];
            if (!$db->fieldExists('id_tabel', 'm_indikator_tabel')) {
                $indFields['id_tabel'] = [
                    'type'       => 'INT',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'id',
                    'comment'    => 'FK ke m_list_tabel.id (Relasi 1:1)',
                ];
            }
            if (!$db->fieldExists('tipe_periode', 'm_indikator_tabel')) {
                $indFields['tipe_periode'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 40,
                    'default'    => 'tahunan',
                    'after'      => 'id_baris_kelompok',
                ];
            }
            if (!$db->fieldExists('tahun_tampil', 'm_indikator_tabel')) {
                $indFields['tahun_tampil'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'id_periode_kelompok',
                ];
            }
            if (!$db->fieldExists('config_tampilan', 'm_indikator_tabel')) {
                $indFields['config_tampilan'] = [
                    'type'       => 'LONGTEXT',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'desimal',
                ];
            }
            if (!empty($indFields)) {
                $forge->addColumn('m_indikator_tabel', $indFields);
            }

            // Ubah default kolom desimal ke -1 (Otomatis Excel)
            $db->query("ALTER TABLE m_indikator_tabel MODIFY COLUMN desimal TINYINT NOT NULL DEFAULT -1;");

            // Isi id_tabel dari t_tahun_tabel
            $db->query("
                UPDATE m_indikator_tabel i 
                JOIN t_tahun_tabel tt ON tt.id = i.id_tahun_tabel 
                SET i.id_tabel = tt.id_tabel 
                WHERE i.id_tabel IS NULL;
            ");

            // Deduplikasi id_tabel ganda jika ada (ambil indikator dengan sel nilai terbanyak)
            $dups = $db->query("
                SELECT id_tabel, COUNT(*) as cnt 
                FROM m_indikator_tabel 
                WHERE id_tabel IS NOT NULL 
                GROUP BY id_tabel HAVING cnt > 1
            ")->getResult();

            foreach ($dups as $dup) {
                $rows = $db->table('m_indikator_tabel')->where('id_tabel', $dup->id_tabel)->get()->getResult();
                $bestId = null;
                $maxCells = -1;
                foreach ($rows as $r) {
                    $cnt = $db->table('t_nilai_dinamis')->where('id_indikator', $r->id)->countAllResults();
                    if ($cnt > $maxCells) {
                        $maxCells = $cnt;
                        $bestId = $r->id;
                    }
                }
                foreach ($rows as $r) {
                    if ($r->id != $bestId) {
                        $db->query("UPDATE IGNORE t_nilai_dinamis SET id_indikator = {$bestId} WHERE id_indikator = {$r->id}");
                        $db->table('t_nilai_dinamis')->where('id_indikator', $r->id)->delete();
                        $db->table('m_indikator_tabel')->where('id', $r->id)->delete();
                    }
                }
            }

            // Lepas FK lama pada id_tahun_tabel jika ada
            $fksOld = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'm_indikator_tabel' 
                  AND CONSTRAINT_NAME = 'fk_indikator_tahun_tabel'
            ")->getResult();
            if (!empty($fksOld)) {
                $db->query("ALTER TABLE m_indikator_tabel DROP FOREIGN KEY fk_indikator_tahun_tabel;");
            }

            // Pasang Unique Key dan FK ke m_list_tabel
            $idxUk = $db->query("SHOW INDEX FROM m_indikator_tabel WHERE Key_name = 'uk_indikator_id_tabel'")->getResult();
            if (empty($idxUk)) {
                $db->query("ALTER TABLE m_indikator_tabel ADD UNIQUE KEY uk_indikator_id_tabel (id_tabel);");
            }

            $fkNew = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'm_indikator_tabel' 
                  AND CONSTRAINT_NAME = 'fk_indikator_master_tabel'
            ")->getResult();
            if (empty($fkNew)) {
                $db->query("ALTER TABLE m_indikator_tabel ADD CONSTRAINT fk_indikator_master_tabel FOREIGN KEY (id_tabel) REFERENCES m_list_tabel(id) ON DELETE CASCADE;");
            }
        }

        // =========================================================================
        // 6. PERUBAHAN STRUKTUR t_nilai_dinamis & KONVERSI DATA MULTI-TAHUN
        // =========================================================================
        if ($db->tableExists('t_nilai_dinamis')) {
            $ndFields = [];
            if (!$db->fieldExists('tahun_data', 't_nilai_dinamis')) {
                $ndFields['tahun_data'] = [
                    'type'       => 'SMALLINT',
                    'unsigned'   => true,
                    'default'    => 2025,
                    'after'      => 'id_indikator',
                ];
            }
            if (!$db->fieldExists('jumlah_desimal', 't_nilai_dinamis')) {
                $ndFields['jumlah_desimal'] = [
                    'type'       => 'TINYINT',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'simbol',
                ];
            }
            if (!empty($ndFields)) {
                $forge->addColumn('t_nilai_dinamis', $ndFields);
            }

            // Drop unique key lama uk_cell_position
            $oldUk = $db->query("SHOW INDEX FROM t_nilai_dinamis WHERE Key_name = 'uk_cell_position'")->getResult();
            if (!empty($oldUk)) {
                $db->query("ALTER TABLE t_nilai_dinamis DROP KEY uk_cell_position;");
            }

            // -----------------------------------------------------------------
            // EKSEKUSI KONVERSI 5 KATEGORI PERIODE
            // -----------------------------------------------------------------

            // Kasus A: id_periode_kelompok IN (17, 18) - Tahun + Satuan per Baris
            $unitRows = $db->table('t_nilai_dinamis')
                ->whereIn('id_periode_item', [74, 80])
                ->where('simbol IS NOT NULL AND simbol != ""')
                ->get()->getResult();

            foreach ($unitRows as $ur) {
                $bi = $db->table('m_baris_item')->where('id', $ur->id_baris_item)->get()->getRow();
                if ($bi && !str_contains($bi->nama_baris_ind, '||')) {
                    $unitStr = trim($ur->simbol);
                    $db->table('m_baris_item')->where('id', $ur->id_baris_item)->update([
                        'nama_baris_ind' => $bi->nama_baris_ind . ' || ' . $unitStr,
                        'nama_baris_en'  => ($bi->nama_baris_en ?: $bi->nama_baris_ind) . ' || ' . $unitStr,
                    ]);
                }
            }

            $indA = $db->table('m_indikator_tabel')->whereIn('id_periode_kelompok', [17, 18])->get()->getResult();
            foreach ($indA as $ia) {
                if (!empty($ia->id_baris_kelompok)) {
                    $db->table('m_baris_kelompok')->where('id', $ia->id_baris_kelompok)->update([
                        'judul_sub_baris_ind' => 'Satuan',
                        'judul_sub_baris_en'  => 'Unit',
                    ]);
                }
            }
            $db->table('m_indikator_tabel')->whereIn('id_periode_kelompok', [17, 18])->update([
                'tampilan_baris' => '2_kolom',
            ]);
            $db->table('t_nilai_dinamis')->whereIn('id_periode_item', [74, 80])->delete();

            // Kasus B: id_periode_kelompok = 25 (Tahun Ajaran 2024/2025 & 2025/2026)
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2024, id_periode_item = 0 WHERE id_periode_item = 99;");
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2025, id_periode_item = 0 WHERE id_periode_item = 100;");
            $db->table('m_indikator_tabel')->where('id_periode_kelompok', 25)->update([
                'tipe_periode'        => 'tahun_ajaran',
                'id_periode_kelompok' => null,
                'tahun_tampil'        => '2024,2025',
            ]);

            // Kasus C: id_periode_kelompok IN (21, 30) - Maret & September Multi-Tahun
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2024, id_periode_item = 122 WHERE id_periode_item IN (87, 137);");
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2024, id_periode_item = 123 WHERE id_periode_item IN (88, 138);");
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2025, id_periode_item = 122 WHERE id_periode_item IN (89, 139);");
            $db->query("UPDATE t_nilai_dinamis SET tahun_data = 2025, id_periode_item = 123 WHERE id_periode_item IN (90, 140);");
            $db->table('m_indikator_tabel')->whereIn('id_periode_kelompok', [21, 30])->update([
                'tipe_periode'        => 'custom',
                'id_periode_kelompok' => 31,
                'tahun_tampil'        => '2024,2025',
            ]);

            // Kasus D: id_periode_kelompok IN (16, 35) - Tahunan + Jumlah
            $db->table('t_nilai_dinamis')->whereIn('id_periode_item', [73, 136])->delete();

            // Kasus E: Seluruh item tahun 4 digit murni (1900-2099)
            $db->query("
                UPDATE t_nilai_dinamis nd 
                JOIN m_periode_item pi ON pi.id = nd.id_periode_item 
                SET nd.tahun_data = CAST(pi.nama_periode_ind AS UNSIGNED), nd.id_periode_item = 0 
                WHERE pi.nama_periode_ind REGEXP '^(19|20)[0-9]{2}$';
            ");

            // Update m_indikator_tabel untuk kelompok tahun murni
            $yearGroups = $db->query("
                SELECT DISTINCT k.id 
                FROM m_periode_kelompok k 
                JOIN m_periode_item i ON i.id_kelompok = k.id 
                WHERE k.id NOT IN (25, 31) 
                GROUP BY k.id 
                HAVING SUM(CASE WHEN i.nama_periode_ind REGEXP '^(19|20)[0-9]{2}$' THEN 1 ELSE 0 END) = COUNT(*)
            ")->getResult();

            $yearGroupIds = array_map(fn($g) => (int)$g->id, $yearGroups);
            if (!empty($yearGroupIds)) {
                foreach ($yearGroupIds as $ygId) {
                    $items = $db->table('m_periode_item')->where('id_kelompok', $ygId)->orderBy('no_urut', 'ASC')->get()->getResult();
                    $years = [];
                    foreach ($items as $it) {
                        if (preg_match('/^(19|20)\d{2}$/', trim($it->nama_periode_ind))) {
                            $years[] = trim($it->nama_periode_ind);
                        }
                    }
                    $tampilStr = !empty($years) ? implode(',', $years) : '2025';
                    $db->table('m_indikator_tabel')->where('id_periode_kelompok', $ygId)->update([
                        'tipe_periode'        => 'tahunan',
                        'id_periode_kelompok' => null,
                        'tahun_tampil'        => $tampilStr,
                    ]);
                }

                // Tandai sebagai arsip_tahunan
                $db->table('m_periode_kelompok')->whereIn('id', $yearGroupIds)->update([
                    'kategori_periode' => 'arsip_tahunan',
                ]);
            }

            // Arsipkan juga kelompok 16, 17, 18, 21, 25, 30, 35
            $db->table('m_periode_kelompok')->whereIn('id', [16, 17, 18, 21, 25, 30, 35])->update([
                'kategori_periode' => 'arsip_tahunan',
            ]);

            // Untuk indikator dengan id_periode_kelompok IS NULL
            $db->query("
                UPDATE m_indikator_tabel 
                SET tipe_periode = 'tahunan', tahun_tampil = '2025' 
                WHERE id_periode_kelompok IS NULL AND (tahun_tampil IS NULL OR tahun_tampil = '');
            ");

            // Deteksi otomatis presisi jumlah_desimal
            $db->query("
                UPDATE t_nilai_dinamis 
                SET jumlah_desimal = CHAR_LENGTH(TRIM(TRAILING '0' FROM SUBSTRING_INDEX(CAST(nilai AS CHAR), '.', -1))) 
                WHERE nilai IS NOT NULL AND INSTR(CAST(nilai AS CHAR), '.') > 0 AND jumlah_desimal IS NULL;
            ");
            $db->query("
                UPDATE t_nilai_dinamis 
                SET jumlah_desimal = 0 
                WHERE nilai IS NOT NULL AND INSTR(CAST(nilai AS CHAR), '.') = 0 AND jumlah_desimal IS NULL;
            ");

            // -----------------------------------------------------------------
            // PEMBERSIHAN DATA SALAH KAMAR & AUTO-DEDUPLIKASI
            // -----------------------------------------------------------------

            // Hapus dummy indicator id = 367 ('test') jika ada
            $db->table('t_nilai_dinamis')->where('id_indikator', 367)->delete();
            $db->table('m_indikator_tabel')->where('id', 367)->delete();

            // Hapus test groups
            $db->table('m_baris_item')->where('id_kelompok', 121)->delete();
            $db->table('m_baris_kelompok')->where('id', 121)->delete();
            $db->table('m_karakteristik_item')->where('id_kelompok', 255)->delete();
            $db->table('m_karakteristik_kelompok')->where('id', 255)->delete();

            // Hapus orphan groups di m_baris_kelompok & m_karakteristik_kelompok
            foreach ([82, 83, 89] as $obk) {
                $used = $db->table('m_indikator_tabel')->where('id_baris_kelompok', $obk)->countAllResults();
                if ($used === 0) {
                    $db->table('m_baris_item')->where('id_kelompok', $obk)->delete();
                    $db->table('m_baris_kelompok')->where('id', $obk)->delete();
                }
            }
            $usedK143 = $db->table('m_indikator_tabel')->where('id_karakteristik_kelompok', 143)->countAllResults();
            if ($usedK143 === 0) {
                $db->table('m_karakteristik_item')->where('id_kelompok', 143)->delete();
                $db->table('m_karakteristik_kelompok')->where('id', 143)->delete();
            }

            // Konversi Tahun di m_baris_kelompok (id = 70, 116, 120, 127)
            foreach ([70, 116, 120, 127] as $yrg) {
                $exists = $db->table('m_baris_kelompok')->where('id', $yrg)->get()->getRow();
                if ($exists) {
                    $db->query("
                        UPDATE t_nilai_dinamis nd 
                        JOIN m_baris_item bi ON bi.id = nd.id_baris_item 
                        SET nd.tahun_data = CAST(bi.nama_baris_ind AS UNSIGNED), nd.id_baris_item = 0 
                        WHERE bi.id_kelompok = {$yrg} AND bi.nama_baris_ind REGEXP '^(19|20)[0-9]{2}$';
                    ");
                    $db->table('m_indikator_tabel')->where('id_baris_kelompok', $yrg)->update([
                        'id_baris_kelompok' => null,
                        'posisi_hirarki'    => 'periode_baris',
                    ]);
                    $db->table('m_baris_item')->where('id_kelompok', $yrg)->delete();
                    $db->table('m_baris_kelompok')->where('id', $yrg)->delete();
                }
            }

            // Konversi id = 115 ('2017 || Maret', '2017 || September' s.d. '2025 || September')
            $g115 = $db->table('m_baris_kelompok')->where('id', 115)->get()->getRow();
            if ($g115) {
                $items115 = $db->table('m_baris_item')->where('id_kelompok', 115)->get()->getResult();
                foreach ($items115 as $it115) {
                    if (preg_match('/(20\d{2})\s*\|\|\s*(Maret|September)/i', $it115->nama_baris_ind, $m)) {
                        $yr = (int)$m[1];
                        $bln = strtolower($m[2]);
                        $targetPeriodeItem = ($bln === 'maret') ? 122 : 123;
                        $db->query("
                            UPDATE t_nilai_dinamis 
                            SET tahun_data = {$yr}, id_periode_item = {$targetPeriodeItem}, id_baris_item = 0 
                            WHERE id_baris_item = {$it115->id};
                        ");
                    }
                }
                $db->table('m_indikator_tabel')->where('id_baris_kelompok', 115)->update([
                    'id_baris_kelompok'    => null,
                    'id_periode_kelompok' => 31,
                    'tipe_periode'        => 'custom',
                    'posisi_hirarki'      => 'periode_baris',
                ]);
                $db->table('m_baris_item')->where('id_kelompok', 115)->delete();
                $db->table('m_baris_kelompok')->where('id', 115)->delete();
            }

            // Satukan m_baris_kelompok.id = 106 (Bulan dan Jawa Tengah) ke id = 9 (Bulan)
            $g106 = $db->table('m_baris_kelompok')->where('id', 106)->get()->getRow();
            if ($g106) {
                $items9 = $db->table('m_baris_item')->where('id_kelompok', 9)->get()->getResult();
                $map9 = [];
                foreach ($items9 as $it9) {
                    $map9[strtolower(trim($it9->nama_baris_ind))] = $it9->id;
                }
                $items106 = $db->table('m_baris_item')->where('id_kelompok', 106)->get()->getResult();
                foreach ($items106 as $it106) {
                    $k = strtolower(trim($it106->nama_baris_ind));
                    if (isset($map9[$k])) {
                        $targetId = $map9[$k];
                        $db->query("UPDATE IGNORE t_nilai_dinamis SET id_baris_item = {$targetId} WHERE id_baris_item = {$it106->id};");
                    }
                }
                $db->table('m_indikator_tabel')->where('id_baris_kelompok', 106)->update(['id_baris_kelompok' => 9]);
                $db->table('m_baris_item')->where('id_kelompok', 106)->delete();
                $db->table('m_baris_kelompok')->where('id', 106)->delete();

                $cekTotal9 = $db->table('m_baris_item')->where('id_kelompok', 9)->where('tipe_baris', 'total')->get()->getRow();
                if (!$cekTotal9) {
                    $db->table('m_baris_item')->insert([
                        'id_kelompok'     => 9,
                        'no_urut'         => 13,
                        'nama_baris_ind'  => 'Jawa Tengah',
                        'nama_baris_en'   => 'Jawa Tengah',
                        'tipe_baris'      => 'total',
                        'level_indentasi' => 0,
                        'is_bold'         => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            }

            // Pastikan indikator 69, 112, 174..178, 329, 371 bersih dari id_periode_kelompok tahunan lama
            $db->table('m_indikator_tabel')->whereIn('id', [174, 175, 176, 177, 178])->update([
                'tipe_periode'        => 'tahunan',
                'id_periode_kelompok' => null,
                'tahun_tampil'        => '2021,2022,2023,2024,2025',
            ]);
            $db->table('m_indikator_tabel')->where('id', 69)->update([
                'tipe_periode'        => 'tahunan',
                'id_periode_kelompok' => null,
                'tahun_tampil'        => '2022,2023,2024,2025',
            ]);
            $db->table('m_indikator_tabel')->where('id', 112)->update([
                'tipe_periode'        => 'tahunan',
                'id_periode_kelompok' => null,
                'tahun_tampil'        => '2020,2025,2026',
            ]);
            $db->table('m_indikator_tabel')->whereIn('id', [329, 371])->update([
                'tahun_tampil' => '2017,2018,2019,2020,2021,2022,2023,2024,2025',
            ]);

            // Bersihkan m_periode_kelompok test (id=36) dan arsipkan 12, 14, 32
            $db->table('m_periode_item')->where('id_kelompok', 36)->delete();
            $db->table('m_periode_kelompok')->where('id', 36)->delete();
            $db->table('m_periode_kelompok')->whereIn('id', [12, 14, 32])->update([
                'kategori_periode' => 'arsip_tahunan',
            ]);

            // Penyatuan kelompok Kabupaten/Kota standar (id = 67 dan id = 102) ke id = 1 berdasarkan urutan 38 item
            $items1 = $db->table('m_baris_item')->where('id_kelompok', 1)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
            if (count($items1) === 38) {
                $db->table('m_baris_item')->where('id', $items1[37]->id)->update([
                    'nama_baris_ind' => 'Jawa Tengah',
                    'nama_baris_en'  => 'Jawa Tengah',
                ]);
                foreach ([67, 102] as $dg) {
                    $dgItems = $db->table('m_baris_item')->where('id_kelompok', $dg)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
                    if (count($dgItems) === 38) {
                        for ($idx = 0; $idx < 38; $idx++) {
                            $oldId = (int)$dgItems[$idx]->id;
                            $newId = (int)$items1[$idx]->id;
                            $db->query("UPDATE IGNORE t_nilai_dinamis SET id_baris_item = {$newId} WHERE id_baris_item = {$oldId};");
                            $db->table('t_nilai_dinamis')->where('id_baris_item', $oldId)->delete();
                        }
                        $db->table('m_indikator_tabel')->where('id_baris_kelompok', $dg)->update(['id_baris_kelompok' => 1]);
                        $db->table('m_baris_item')->where('id_kelompok', $dg)->delete();
                        $db->table('m_baris_kelompok')->where('id', $dg)->delete();
                    }
                }
            }

            // Penyatuan grup kembar identik 100% pada m_karakteristik_kelompok
            $allKk = $db->table('m_karakteristik_kelompok')->orderBy('id', 'ASC')->get()->getResult();
            $fingerprints = [];
            foreach ($allKk as $kk) {
                $items = $db->table('m_karakteristik_item')->where('id_kelompok', $kk->id)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
                if (empty($items)) continue;

                $fpParts = [];
                foreach ($items as $it) {
                    $fpParts[] = strtolower(trim($it->grup_induk_ind ?? '')) . '|' .
                                 strtolower(trim($it->grup_sub_ind ?? '')) . '|' .
                                 strtolower(trim($it->item_ind ?? '')) . '|' .
                                 $it->is_standalone;
                }
                $fp = md5(implode('##', $fpParts));

                if (!isset($fingerprints[$fp])) {
                    $fingerprints[$fp] = ['id' => $kk->id, 'items' => $items];
                } else {
                    $canon = $fingerprints[$fp];
                    $canonId = $canon['id'];
                    $canonItems = $canon['items'];

                    if (count($items) === count($canonItems)) {
                        for ($idx = 0; $idx < count($items); $idx++) {
                            $oldItemId = $items[$idx]->id;
                            $newItemId = $canonItems[$idx]->id;
                            $db->query("UPDATE IGNORE t_nilai_dinamis SET id_karakteristik_item = {$newItemId} WHERE id_karakteristik_item = {$oldItemId};");
                            $db->table('t_nilai_dinamis')->where('id_karakteristik_item', $oldItemId)->delete();
                        }
                        $db->table('m_indikator_tabel')->where('id_karakteristik_kelompok', $kk->id)->update(['id_karakteristik_kelompok' => $canonId]);
                        $db->table('m_karakteristik_item')->where('id_kelompok', $kk->id)->delete();
                        $db->table('m_karakteristik_kelompok')->where('id', $kk->id)->delete();
                    }
                }
            }

            // Hapus orphan groups di m_baris_kelompok & m_karakteristik_kelompok yang tidak dipakai indikator mana pun
            $unusedBaris = $db->query("SELECT bk.id FROM m_baris_kelompok bk LEFT JOIN m_indikator_tabel i ON i.id_baris_kelompok = bk.id WHERE i.id IS NULL")->getResult();
            foreach ($unusedBaris as $ub) {
                $hasCells = $db->query("SELECT COUNT(*) as cnt FROM t_nilai_dinamis nd JOIN m_baris_item bi ON bi.id = nd.id_baris_item WHERE bi.id_kelompok = {$ub->id}")->getRow()->cnt;
                if ((int)$hasCells === 0) {
                    $db->table('m_baris_item')->where('id_kelompok', $ub->id)->delete();
                    $db->table('m_baris_kelompok')->where('id', $ub->id)->delete();
                }
            }
            $unusedKarak = $db->query("SELECT kk.id FROM m_karakteristik_kelompok kk LEFT JOIN m_indikator_tabel i ON i.id_karakteristik_kelompok = kk.id WHERE i.id IS NULL")->getResult();
            foreach ($unusedKarak as $uk) {
                $hasCells = $db->query("SELECT COUNT(*) as cnt FROM t_nilai_dinamis nd JOIN m_karakteristik_item ki ON ki.id = nd.id_karakteristik_item WHERE ki.id_kelompok = {$uk->id}")->getRow()->cnt;
                if ((int)$hasCells === 0) {
                    $db->table('m_karakteristik_item')->where('id_kelompok', $uk->id)->delete();
                    $db->table('m_karakteristik_kelompok')->where('id', $uk->id)->delete();
                }
            }

            // Sinkronkan config_tampilan JSON awal pada m_indikator_tabel
            $allInds = $db->table('m_indikator_tabel')->get()->getResult();
            foreach ($allInds as $ind) {
                $cfg = [
                    'total_horizontal'        => ($ind->tipe_periode === 'tahunan_jumlah'),
                    'jenis_total_horizontal'  => 'sum',
                    'only_total_horizontal'   => false,
                    'group_total_horizontal'  => false,
                    'total_vertikal'          => ($ind->konfigurasi_total !== 'none'),
                    'jenis_total_vertikal'    => $ind->konfigurasi_total ?: 'default',
                    'label_total_kustom'      => $ind->label_total_kustom ?? '',
                    'group_by_region'         => true,
                    'prioritas_karakteristik' => ($ind->posisi_hirarki === 'kolom_atas'),
                    'portrait_mode'           => in_array($ind->posisi_hirarki, ['periode_baris', 'periode_baris_pisah'], true),
                    'portrait_split_col'      => ($ind->posisi_hirarki === 'periode_baris_pisah'),
                ];
                $db->table('m_indikator_tabel')->where('id', $ind->id)->update([
                    'config_tampilan' => json_encode($cfg),
                ]);
            }

            // Bersihkan baris duplikat di t_nilai_dinamis sebelum memasang Unique Key baru
            $db->query("
                DELETE n1 FROM t_nilai_dinamis n1
                INNER JOIN t_nilai_dinamis n2 
                WHERE n1.id < n2.id 
                  AND n1.id_indikator = n2.id_indikator 
                  AND n1.tahun_data = n2.tahun_data 
                  AND n1.id_baris_item = n2.id_baris_item 
                  AND n1.id_karakteristik_item = n2.id_karakteristik_item 
                  AND n1.id_periode_item = n2.id_periode_item;
            ");

            // Pasang Unique Key baru dan Index pada t_nilai_dinamis
            $idxUkMulti = $db->query("SHOW INDEX FROM t_nilai_dinamis WHERE Key_name = 'uk_cell_multiyear'")->getResult();
            if (empty($idxUkMulti)) {
                $db->query("ALTER TABLE t_nilai_dinamis ADD UNIQUE KEY uk_cell_multiyear (id_indikator, tahun_data, id_baris_item, id_karakteristik_item, id_periode_item);");
            }

            $idxIndTh = $db->query("SHOW INDEX FROM t_nilai_dinamis WHERE Key_name = 'idx_indikator_tahun'")->getResult();
            if (empty($idxIndTh)) {
                $db->query("ALTER TABLE t_nilai_dinamis ADD KEY idx_indikator_tahun (id_indikator, tahun_data);");
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        $forge = $this->forge;

        // Rollback foreign keys & indexes
        if ($db->tableExists('t_nilai_dinamis')) {
            $uk = $db->query("SHOW INDEX FROM t_nilai_dinamis WHERE Key_name = 'uk_cell_multiyear'")->getResult();
            if (!empty($uk)) {
                $db->query("ALTER TABLE t_nilai_dinamis DROP KEY uk_cell_multiyear;");
            }
            $idx = $db->query("SHOW INDEX FROM t_nilai_dinamis WHERE Key_name = 'idx_indikator_tahun'")->getResult();
            if (!empty($idx)) {
                $db->query("ALTER TABLE t_nilai_dinamis DROP KEY idx_indikator_tahun;");
            }
            if ($db->fieldExists('tahun_data', 't_nilai_dinamis')) {
                $forge->dropColumn('t_nilai_dinamis', 'tahun_data');
            }
            if ($db->fieldExists('jumlah_desimal', 't_nilai_dinamis')) {
                $forge->dropColumn('t_nilai_dinamis', 'jumlah_desimal');
            }
        }

        if ($db->tableExists('m_indikator_tabel')) {
            $fk = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'm_indikator_tabel' 
                  AND CONSTRAINT_NAME = 'fk_indikator_master_tabel'
            ")->getResult();
            if (!empty($fk)) {
                $db->query("ALTER TABLE m_indikator_tabel DROP FOREIGN KEY fk_indikator_master_tabel;");
            }
            $uk = $db->query("SHOW INDEX FROM m_indikator_tabel WHERE Key_name = 'uk_indikator_id_tabel'")->getResult();
            if (!empty($uk)) {
                $db->query("ALTER TABLE m_indikator_tabel DROP KEY uk_indikator_id_tabel;");
            }
            if ($db->fieldExists('id_tabel', 'm_indikator_tabel')) {
                $forge->dropColumn('m_indikator_tabel', 'id_tabel');
            }
            if ($db->fieldExists('tipe_periode', 'm_indikator_tabel')) {
                $forge->dropColumn('m_indikator_tabel', 'tipe_periode');
            }
            if ($db->fieldExists('tahun_tampil', 'm_indikator_tabel')) {
                $forge->dropColumn('m_indikator_tabel', 'tahun_tampil');
            }
            if ($db->fieldExists('config_tampilan', 'm_indikator_tabel')) {
                $forge->dropColumn('m_indikator_tabel', 'config_tampilan');
            }
        }

        if ($db->tableExists('m_konfigurasi_api')) {
            $forge->dropTable('m_konfigurasi_api', true);
        }

        if ($db->tableExists('m_list_tabel')) {
            if ($db->fieldExists('no_tabel', 'm_list_tabel')) {
                $forge->dropColumn('m_list_tabel', 'no_tabel');
            }
            if ($db->fieldExists('jenis_sumber', 'm_list_tabel')) {
                $forge->dropColumn('m_list_tabel', 'jenis_sumber');
            }
            if ($db->fieldExists('sumber_data', 'm_list_tabel')) {
                $forge->dropColumn('m_list_tabel', 'sumber_data');
            }
            if ($db->fieldExists('is_active', 'm_list_tabel')) {
                $forge->dropColumn('m_list_tabel', 'is_active');
            }
        }

        if ($db->tableExists('m_periode_kelompok')) {
            if ($db->fieldExists('kategori_periode', 'm_periode_kelompok')) {
                $forge->dropColumn('m_periode_kelompok', 'kategori_periode');
            }
            if ($db->fieldExists('kode_standar', 'm_periode_kelompok')) {
                $forge->dropColumn('m_periode_kelompok', 'kode_standar');
            }
        }

        if ($db->tableExists('m_periode_item')) {
            if ($db->fieldExists('tipe_item', 'm_periode_item')) {
                $forge->dropColumn('m_periode_item', 'tipe_item');
            }
        }
    }
}
