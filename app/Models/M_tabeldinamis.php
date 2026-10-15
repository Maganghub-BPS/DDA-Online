<?php

namespace App\Models;

use CodeIgniter\Model;

class M_tabeldinamis extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_indikator_tabel';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'id_tahun_tabel',
        'no_tabel',
        'judul_ind',
        'judul_en',
        'id_unitkerja',
        'id_karakteristik_kelompok',
        'id_sub_karakteristik_kelompok',
        'id_baris_kelompok',
        'id_periode_kelompok',
        'posisi_hirarki',
        'tingkat_kolom',
        'konfigurasi_total',
        'label_total_kustom',
        'satuan',
        'desimal',
        'grafik_default',
        'sumber',
        'catatan',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps    = true;

    // =========================================================================
    // 1. DIMENSI KARAKTERISTIK (KOLOM & SUB-KOLOM ASIMETRIS)
    // =========================================================================

    public function getKarakteristikKelompokAll(): array
    {
        return $this->db->table('m_karakteristik_kelompok')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getKarakteristikKelompok(int $id): ?object
    {
        return $this->db->table('m_karakteristik_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function saveKarakteristikKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_karakteristik_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deleteKarakteristikKelompok(int $id): bool
    {
        $this->db->table('m_karakteristik_item')->where('id_kelompok', $id)->delete();
        return $this->db->table('m_karakteristik_kelompok')->where('id', $id)->delete();
    }

    public function getKarakteristikItems(int $id_kelompok): array
    {
        $items = $this->db->table('m_karakteristik_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        foreach ($items as $item) {
            $item->is_standalone = (int)($item->is_standalone ?? 0);
        }

        return $items;
    }

    public function syncKarakteristikItems(int $id_kelompok, array $items): bool
    {
        $builder = $this->db->table('m_karakteristik_item');
        $builder->where('id_kelompok', $id_kelompok)->delete();

        if (empty($items)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [];
        $no = 1;

        foreach ($items as $item) {
            $raw_ind = trim($item['item_ind'] ?? '');
            $raw_en  = trim($item['item_en'] ?? '');

            if ($raw_ind === '') {
                continue;
            }

            // Jika teks Indonesia mengandung pembatas dwibahasa '//'
            if (strpos($raw_ind, '//') !== false && empty($raw_en)) {
                $parts_lang = array_map('trim', explode('//', $raw_ind, 2));
                $raw_ind = $parts_lang[0];
                $raw_en  = $parts_lang[1] ?? '';
            }

            // Ekstraksi notasi Induk || Anak hingga 3 tingkatan (Tingkat 1 || Tingkat 2 || Tingkat 3)
            $grup_induk_ind = null;
            $grup_induk_en  = null;
            $grup_sub_ind   = null;
            $grup_sub_en    = null;
            $item_ind = $raw_ind;
            $item_en  = $raw_en !== '' ? $raw_en : $raw_ind;
            $is_standalone = 0;

            if (strpos($raw_ind, '||') !== false) {
                $parts_ind = array_map('trim', explode('||', $raw_ind));
                $parts_en  = (!empty($raw_en) && strpos($raw_en, '||') !== false) 
                    ? array_map('trim', explode('||', $raw_en)) 
                    : [];

                if (count($parts_ind) >= 3) {
                    $grup_induk_ind = $parts_ind[0];
                    $grup_sub_ind   = $parts_ind[1];
                    $item_ind       = $parts_ind[2];

                    $grup_induk_en  = $parts_en[0] ?? $grup_induk_ind;
                    $grup_sub_en    = $parts_en[1] ?? $grup_sub_ind;
                    $item_en        = $parts_en[2] ?? ($parts_en[0] ?? $item_ind);
                } elseif (count($parts_ind) === 2) {
                    $grup_induk_ind = $parts_ind[0];
                    $grup_sub_ind   = null;
                    $item_ind       = $parts_ind[1];

                    $grup_induk_en  = $parts_en[0] ?? $grup_induk_ind;
                    $grup_sub_en    = null;
                    $item_en        = $parts_en[1] ?? ($parts_en[0] ?? $item_ind);
                } else {
                    $item_ind       = $parts_ind[0];
                    $item_en        = $parts_en[0] ?? $item_ind;
                }
            } else {
                $lower_ind = strtolower($raw_ind);
                $lower_en  = strtolower($raw_en ?? '');
                if ($lower_ind === 'total' || $lower_ind === 'jumlah' || $lower_en === 'total') {
                    $is_standalone = 1;
                }
            }

            $insertData[] = [
                'id_kelompok'    => $id_kelompok,
                'parent_id'      => !empty($item['parent_id']) ? (int)$item['parent_id'] : null,
                'grup_induk_ind' => $grup_induk_ind,
                'grup_induk_en'  => $grup_induk_en,
                'grup_sub_ind'   => $grup_sub_ind,
                'grup_sub_en'    => $grup_sub_en,
                'item_ind'       => $item_ind,
                'item_en'        => $item_en,
                'no_urut'        => (int)($item['no_urut'] ?? $no),
                'is_standalone'  => $is_standalone,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
            $no++;
        }

        if (!empty($insertData)) {
            $builder->insertBatch($insertData);
        }

        return true;
    }

    public function buildColumnStructure(array $items): array
    {
        $hasGroupHeaders = false;
        $maxDepth = 1;

        // Cek kedalaman maksimum hirarki kolom (1, 2, atau 3)
        foreach ($items as $item) {
            if (!empty($item->grup_sub_ind)) {
                $maxDepth = 3;
                $hasGroupHeaders = true;
                break;
            } elseif (!empty($item->grup_induk_ind)) {
                $maxDepth = 2;
                $hasGroupHeaders = true;
            }
        }

        $groups = [];

        if ($maxDepth === 3) {
            foreach ($items as $item) {
                $l1 = $item->grup_induk_ind;
                $l2 = $item->grup_sub_ind;

                if (!empty($l1)) {
                    if (!isset($groups[$l1])) {
                        $groups[$l1] = [
                            'type'      => 'group',
                            'title_ind' => $l1,
                            'title_en'  => $item->grup_induk_en ?? $l1,
                            'subgroups' => [],
                            'children'  => [],
                        ];
                    }
                    $groups[$l1]['children'][] = $item;

                    $subKey = !empty($l2) ? $l2 : '__standalone_' . $item->id;
                    if (!isset($groups[$l1]['subgroups'][$subKey])) {
                        $groups[$l1]['subgroups'][$subKey] = [
                            'type'      => !empty($l2) ? 'subgroup' : 'standalone',
                            'title_ind' => !empty($l2) ? $l2 : $item->item_ind,
                            'title_en'  => !empty($l2) ? ($item->grup_sub_en ?? $l2) : ($item->item_en ?? $item->item_ind),
                            'children'  => [],
                        ];
                    }
                    $groups[$l1]['subgroups'][$subKey]['children'][] = $item;
                } else {
                    $groups['__standalone_' . $item->id] = [
                        'type'      => 'standalone',
                        'title_ind' => $item->item_ind,
                        'title_en'  => $item->item_en ?? $item->item_ind,
                        'subgroups' => [],
                        'children'  => [$item],
                    ];
                }
            }

            foreach ($groups as &$grp) {
                if (!empty($grp['subgroups'])) {
                    $grp['subgroups'] = array_values($grp['subgroups']);
                }
            }
            unset($grp);
        } else {
            // maxDepth 1 atau 2
            foreach ($items as $item) {
                $parentName = $item->grup_induk_ind ?: ($item->is_standalone ? '' : null);

                if (!empty($parentName)) {
                    if (!isset($groups[$parentName])) {
                        $groups[$parentName] = [
                            'type'      => 'group',
                            'title_ind' => $parentName,
                            'title_en'  => $item->grup_induk_en ?? $parentName,
                            'children'  => [],
                        ];
                    }
                    $groups[$parentName]['children'][] = $item;
                } else {
                    $groups['__standalone_' . $item->id] = [
                        'type'      => 'standalone',
                        'title_ind' => $item->item_ind,
                        'title_en'  => $item->item_en,
                        'item'      => $item,
                        'children'  => [$item],
                    ];
                }
            }
        }

        return [
            'max_depth'  => $maxDepth,
            'has_groups' => $hasGroupHeaders,
            'groups'     => array_values($groups),
            'flat_items' => $items,
        ];
    }

    // =========================================================================
    // 2. DIMENSI BARIS (BARIS BERTINGKAT / HIERARKIS & INDENTASI)
    // =========================================================================

    public function getBarisKelompokAll(): array
    {
        return $this->db->table('m_baris_kelompok')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getBarisKelompok(int $id): ?object
    {
        return $this->db->table('m_baris_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function saveBarisKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_baris_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deleteBarisKelompok(int $id): bool
    {
        $this->db->table('m_baris_item')->where('id_kelompok', $id)->delete();
        return $this->db->table('m_baris_kelompok')->where('id', $id)->delete();
    }

    public function getBarisItems(int $id_kelompok): array
    {
        return $this->db->table('m_baris_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function getBarisTree(int $id_kelompok): array
    {
        $items = $this->getBarisItems($id_kelompok);
        $lookup = [];
        $tree = [];

        foreach ($items as $item) {
            $item->children = [];
            $lookup[$item->id] = $item;
        }

        foreach ($items as $item) {
            if (!empty($item->parent_id) && isset($lookup[$item->parent_id])) {
                $lookup[$item->parent_id]->children[] = $item;
            } else {
                $tree[] = $item;
            }
        }

        return $tree;
    }

    public function syncBarisItems(int $id_kelompok, array $items): bool
    {
        $builder = $this->db->table('m_baris_item');
        $builder->where('id_kelompok', $id_kelompok)->delete();

        if (empty($items)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [];
        $maxLevel = 0;
        foreach ($items as $it) {
            $lvl = (int)($it['level_indentasi'] ?? ($it['level'] ?? 0));
            if ($lvl > $maxLevel) {
                $maxLevel = $lvl;
            }
        }

        $no = 1;
        foreach ($items as $item) {
            $nama_ind = trim($item['nama_baris_ind'] ?? '');
            $nama_en  = trim($item['nama_baris_en'] ?? '');

            if ($nama_ind === '') {
                continue;
            }

            $level_indentasi = (int)($item['level_indentasi'] ?? ($item['level'] ?? 0));
            $tipe_baris = $item['tipe_baris'] ?? '';
            $validTypes = ['kategori', 'data', 'subtotal', 'total'];
            
            // Evaluasi tipe baris cerdas & hormati penandaan is_total / tipe_baris dari pengguna
            $is_user_total = (!empty($item['is_total']) || $tipe_baris === 'total');
            $detectedTipe  = $this->detectTipeBaris($nama_ind, $level_indentasi);

            if ($is_user_total || $detectedTipe === 'total') {
                $tipe_baris = 'total';
            } elseif (!empty($tipe_baris) && in_array($tipe_baris, $validTypes, true)) {
                // Pertahankan tipe fungsional yang eksplisit dipilih pengguna (kategori, data, subtotal, total)
            } else {
                $tipe_baris = $detectedTipe;
            }

            // Jika seluruh kelompok flat (maxLevel == 0), tidak boleh ada tipe 'kategori'
            if ($maxLevel === 0 && $tipe_baris === 'kategori') {
                $tipe_baris = 'data';
            }

            $is_bold = !empty($item['is_bold']) ? 1 : ($tipe_baris === 'total' || $tipe_baris === 'kategori' ? 1 : 0);

            $insertData[] = [
                'id_kelompok'     => $id_kelompok,
                'parent_id'       => !empty($item['parent_id']) ? (int)$item['parent_id'] : null,
                'no_urut'         => (int)($item['no_urut'] ?? $no),
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => $nama_en !== '' ? $nama_en : null,
                'tipe_baris'      => $tipe_baris,
                'level_indentasi' => $level_indentasi,
                'is_bold'         => $is_bold,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
            $no++;
        }

        if (!empty($insertData)) {
            $builder->insertBatch($insertData);
        }

        return true;
    }

    public function detectTipeBaris(string $namaInd, int $indent = 0, bool $hasChildren = false): string
    {
        $clean = trim(preg_replace('/^(\(?[A-Za-z0-9]+\)[\.\)]?|[A-Za-z0-9]+[\.\)]|[IVXLCDMivxlcdm]+[\.\)]|[–\-\•])\s+/', '', $namaInd));
        $lower = strtolower($clean);

        // 1. Cek pengecualian nama indikator/variabel statistik: "jumlah curah hujan", "jumlah hari hujan", dsb.
        if (strpos($lower, 'curah hujan') !== false || strpos($lower, 'hari hujan') !== false) {
            return 'data';
        }
        if (preg_match('/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i', $lower)) {
            return 'data';
        }

        // 2. Grand Total: Jawa Tengah, Produk Domestik Regional Bruto (PDRB), Jumlah, Total, dsb.
        $totalTerms = [
            'jumlah', 'total', 'grand total', 'grandtotal', 
            'jumlah total', 'jumlah seluruhnya', 'total seluruhnya', 
            'produk domestik regional bruto', 'pdrb', 
            'gross regional domestic product', 'grdp', 'all'
        ];
        $base = trim(explode('/', explode('(', $lower)[0])[0]);

        if (
            $lower === 'jawa tengah' || 
            strpos($lower, 'jawa tengah') === 0 || 
            $lower === 'provinsi jawa tengah' || 
            $lower === 'prov. jawa tengah' || 
            $lower === 'central java' ||
            strpos($lower, 'produk domestik regional bruto') === 0 ||
            strpos($lower, 'pdrb') === 0 ||
            strpos($lower, 'gross regional domestic product') === 0 ||
            strpos($lower, 'grdp') === 0 ||
            in_array($lower, $totalTerms, true) || 
            in_array($base, $totalTerms, true) ||
            preg_match('/^(jumlah|total|grand\s*total)\s*(\/|\/\/)\s*(total|grand\s*total|jumlah)$/i', $lower)
        ) {
            return 'total';
        }

        // 3. Subtotal
        if ($base === 'subtotal' || $base === 'sub total' || $base === 'sub-total' || strpos($lower, 'subtotal') === 0 || strpos($lower, 'sub total') === 0) {
            return 'subtotal';
        }

        // 4. Kategori (Pembatas Bab/Kategori - khusus pembatas wilayah Kabupaten/Kota atau banner judul murni)
        if ($lower === 'kabupaten' || $lower === 'kota') {
            return 'kategori';
        }

        return 'data';
    }

    /**
     * Sinkronisasi item baris dari array tree manager (menjaga flag is_total yang dipilih pengguna)
     */
    public function syncBarisFromTreeItems(int $id_kelompok, array $treeItems): bool
    {
        $items = [];
        $no = 1;

        foreach ($treeItems as $it) {
            $nama_ind = trim($it['ind'] ?? ($it['nama_baris_ind'] ?? ''));
            $nama_en  = trim($it['en'] ?? ($it['nama_baris_en'] ?? ''));
            if ($nama_ind === '') {
                continue;
            }

            $lvl = (int)($it['level'] ?? ($it['level_indentasi'] ?? 0));
            $is_total = !empty($it['is_total']) || (($it['tipe_baris'] ?? '') === 'total');

            $items[] = [
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => !empty($nama_en) ? $nama_en : $nama_ind,
                'level_indentasi' => $lvl,
                'tipe_baris'      => $is_total ? 'total' : ($it['tipe_baris'] ?? 'data'),
                'is_total'        => $is_total ? 1 : 0,
                'no_urut'         => $no++,
            ];
        }

        return $this->syncBarisItems($id_kelompok, $items);
    }

    public function parseBarisFromText(int $id_kelompok, string $text, string $text_en = ''): bool
    {
        $lines_ind = preg_split('/\r\n|\r|\n/', trim($text));
        $lines_en  = !empty($text_en) ? preg_split('/\r\n|\r|\n/', trim($text_en)) : [];
        $items = [];
        $no = 1;

        foreach ($lines_ind as $idx => $line) {
            $trimmed_ind = trim($line);
            if ($trimmed_ind === '') {
                continue;
            }

            $trimmed_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';

            // Jika baris Indonesia mengandung separator '//'
            if (strpos($trimmed_ind, '//') !== false && empty($trimmed_en)) {
                $parts = array_map('trim', explode('//', $trimmed_ind, 2));
                $trimmed_ind = $parts[0];
                $trimmed_en  = $parts[1] ?? '';
            }

            $indent = 0;
            $nama_ind = $trimmed_ind;
            $nama_en  = $trimmed_en;

            // Hitung indentasi pada teks Indonesia
            if (strpos($trimmed_ind, '----') === 0) {
                $indent = 2;
                $nama_ind = trim(substr($trimmed_ind, 4));
            } elseif (strpos($trimmed_ind, '--') === 0) {
                $indent = 1;
                $nama_ind = trim(substr($trimmed_ind, 2));
            } elseif (preg_match('/^(\s+)/', $line, $matches)) {
                $spaceCount = strlen($matches[1]);
                $indent = $spaceCount >= 4 ? 2 : 1;
            }

            // Bersihkan indentasi pada teks English jika ada
            if (strpos($nama_en, '----') === 0) {
                $nama_en = trim(substr($nama_en, 4));
            } elseif (strpos($nama_en, '--') === 0) {
                $nama_en = trim(substr($nama_en, 2));
            }

            $items[] = [
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => !empty($nama_en) ? $nama_en : $nama_ind,
                'level_indentasi' => $indent,
                'no_urut'         => $no++,
            ];
        }

        return $this->syncBarisItems($id_kelompok, $items);
    }

    // =========================================================================
    // 3. DIMENSI PERIODE WAKTU (TAHUN / BULAN)
    // =========================================================================

    public function getPeriodeKelompokAll(): array
    {
        return $this->db->table('m_periode_kelompok')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getPeriodeKelompok(int $id): ?object
    {
        return $this->db->table('m_periode_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function savePeriodeKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_periode_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deletePeriodeKelompok(int $id): bool
    {
        $this->db->table('m_periode_item')->where('id_kelompok', $id)->delete();
        return $this->db->table('m_periode_kelompok')->where('id', $id)->delete();
    }

    public function getPeriodeItems(int $id_kelompok): array
    {
        return $this->db->table('m_periode_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function syncPeriodeItems(int $id_kelompok, array $items): bool
    {
        $builder = $this->db->table('m_periode_item');
        $builder->where('id_kelompok', $id_kelompok)->delete();

        if (empty($items)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [];
        $no = 1;

        foreach ($items as $item) {
            $nama_ind = trim($item['nama_periode_ind'] ?? '');
            $nama_en  = trim($item['nama_periode_en'] ?? '');

            if ($nama_ind === '') {
                continue;
            }

            $insertData[] = [
                'id_kelompok'      => $id_kelompok,
                'nama_periode_ind' => $nama_ind,
                'nama_periode_en'  => $nama_en !== '' ? $nama_en : null,
                'no_urut'          => (int)($item['no_urut'] ?? $no),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $no++;
        }

        if (!empty($insertData)) {
            $builder->insertBatch($insertData);
        }

        return true;
    }

    public function generatePeriodeYearRange(int $id_kelompok, int $startYear, int $endYear, string $order = 'ASC'): bool
    {
        $years = range($startYear, $endYear);
        if (strtoupper($order) === 'DESC') {
            rsort($years);
        } else {
            sort($years);
        }

        $items = [];
        $no = 1;
        foreach ($years as $year) {
            $items[] = [
                'nama_periode_ind' => (string)$year,
                'nama_periode_en'  => (string)$year,
                'no_urut'          => $no++,
            ];
        }

        return $this->syncPeriodeItems($id_kelompok, $items);
    }

    public function parsePeriodeFromText(int $id_kelompok, string $text_ind, string $text_en = ''): bool
    {
        $splitPattern = '/\r\n|\r|\n/';
        if (strpos($text_ind, "\n") === false && strpos($text_ind, ',') !== false) {
            $splitPattern = '/,/';
        }

        $lines_ind = preg_split($splitPattern, trim($text_ind));
        $lines_en  = !empty($text_en) ? preg_split($splitPattern, trim($text_en)) : [];
        $items = [];
        $no = 1;

        foreach ($lines_ind as $idx => $part) {
            $trimmed_ind = trim($part);
            if ($trimmed_ind === '') {
                continue;
            }

            $trimmed_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';

            // Cek jika teks Indonesia mengandung pemisah dwibahasa '//'
            if (strpos($trimmed_ind, '//') !== false && empty($trimmed_en)) {
                $parts_lang = array_map('trim', explode('//', $trimmed_ind, 2));
                $trimmed_ind = $parts_lang[0];
                $trimmed_en  = $parts_lang[1] ?? '';
            }

            $items[] = [
                'nama_periode_ind' => $trimmed_ind,
                'nama_periode_en'  => $trimmed_en !== '' ? $trimmed_en : $trimmed_ind,
                'no_urut'          => $no++,
            ];
        }

        return $this->syncPeriodeItems($id_kelompok, $items);
    }

    // =========================================================================
    // 4. PERAKITAN INDIKATOR TABEL (GENERATOR UTAMA)
    // =========================================================================

    public function getIndikatorAll(): array
    {
        return $this->db->table('m_indikator_tabel')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getIndikator(int $id): ?object
    {
        return $this->find($id);
    }

    public function getTableFullConfig(int $id_indikator): ?object
    {
        $indikator = $this->find($id_indikator);
        if (!$indikator) {
            return null;
        }

        // 1. Dimensi Kolom (Karakteristik)
        $indikator->karakteristik_kelompok = $this->getKarakteristikKelompok((int)$indikator->id_karakteristik_kelompok);
        $indikator->kolom_items = $this->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
        $indikator->kolom_structure = $this->buildColumnStructure($indikator->kolom_items);

        // Sub-Karakteristik jika ada
        $indikator->sub_karakteristik_kelompok = null;
        $indikator->sub_kolom_items = [];
        if (!empty($indikator->id_sub_karakteristik_kelompok)) {
            $indikator->sub_karakteristik_kelompok = $this->getKarakteristikKelompok((int)$indikator->id_sub_karakteristik_kelompok);
            $indikator->sub_kolom_items = $this->getKarakteristikItems((int)$indikator->id_sub_karakteristik_kelompok);
        }

        // 2. Dimensi Baris
        $indikator->baris_kelompok = $this->getBarisKelompok((int)$indikator->id_baris_kelompok);
        $indikator->baris_items = $this->getBarisItems((int)$indikator->id_baris_kelompok);

        // 3. Dimensi Periode
        $indikator->periode_kelompok = $this->getPeriodeKelompok((int)$indikator->id_periode_kelompok);
        $indikator->periode_items = $this->getPeriodeItems((int)$indikator->id_periode_kelompok);

        // 4. Detail relasi tabel DDA (t_tahun_tabel)
        $indikator->tahun_tabel = null;
        if (!empty($indikator->id_tahun_tabel)) {
            $indikator->tahun_tabel = $this->db->table('t_tahun_tabel')
                ->where('id', $indikator->id_tahun_tabel)
                ->get()
                ->getRow();
        }

        return $indikator;
    }

    public function getByTahunTabel(int $id_tahun_tabel): ?object
    {
        $indikator = $this->where('id_tahun_tabel', $id_tahun_tabel)->first();
        if ($indikator) {
            return $this->getTableFullConfig((int)$indikator->id);
        }
        return null;
    }

    public function linkToTahunTabel(int $id_indikator, int $id_tahun_tabel): bool
    {
        $this->update($id_indikator, ['id_tahun_tabel' => $id_tahun_tabel]);
        $this->db->table('t_tahun_tabel')
            ->where('id', $id_tahun_tabel)
            ->update(['is_dynamic' => 1]);

        return true;
    }

    // =========================================================================
    // 5. NILAI DATA MATRIKS DINAMIS (t_nilai_dinamis)
    // =========================================================================

    public function getMatrixLookup(int $id_indikator): array
    {
        $rows = $this->db->table('t_nilai_dinamis')
            ->where('id_indikator', $id_indikator)
            ->get()
            ->getResult();

        $lookup = [];
        foreach ($rows as $row) {
            $subId = (int)($row->id_sub_karakteristik_item ?? 0);
            $key = "{$row->id_baris_item}_{$row->id_karakteristik_item}_{$subId}_{$row->id_periode_item}";
            $lookup[$key] = [
                'id'     => $row->id,
                'nilai'  => $row->nilai !== null ? (float)$row->nilai : null,
                'simbol' => $row->simbol,
            ];
        }

        return $lookup;
    }

    public function batchUpsertNilai(int $id_indikator, array $cells): int
    {
        if (empty($cells)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $chunks = array_chunk($cells, 500);
        $totalAffected = 0;

        foreach ($chunks as $chunk) {
            $values = [];
            $params = [];

            foreach ($chunk as $c) {
                $idBaris = (int)$c['id_baris_item'];
                $idKarakteristik = (int)$c['id_karakteristik_item'];
                $idSub = (int)($c['id_sub_karakteristik_item'] ?? 0);
                $idPeriode = (int)$c['id_periode_item'];

                $nilai = isset($c['nilai']) && $c['nilai'] !== '' && $c['nilai'] !== null ? (float)$c['nilai'] : null;
                $simbol = !empty($c['simbol']) ? trim($c['simbol']) : null;

                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params[] = $id_indikator;
                $params[] = $idBaris;
                $params[] = $idKarakteristik;
                $params[] = $idSub;
                $params[] = $idPeriode;
                $params[] = $nilai;
                $params[] = $simbol;
                $params[] = $now;
                $params[] = $now;
            }

            $sql = "INSERT INTO t_nilai_dinamis 
                    (id_indikator, id_baris_item, id_karakteristik_item, id_sub_karakteristik_item, id_periode_item, nilai, simbol, created_at, updated_at)
                    VALUES " . implode(', ', $values) . "
                    ON DUPLICATE KEY UPDATE 
                        nilai = VALUES(nilai),
                        simbol = VALUES(simbol),
                        updated_at = VALUES(updated_at)";

            $this->db->query($sql, $params);
            $totalAffected += count($chunk);
        }

        return $totalAffected;
    }

    public function clearNilai(int $id_indikator): bool
    {
        return $this->db->table('t_nilai_dinamis')
            ->where('id_indikator', $id_indikator)
            ->delete();
    }
}
