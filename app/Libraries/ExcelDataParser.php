<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Service Parser & Validasi Berkas Excel Walidata (.xlsx) untuk Tabel Dinamis DDA Online
 * Standar Publikasi BPS Provinsi Jawa Tengah (Sprint 5)
 */
class ExcelDataParser
{
    protected $db;
    protected $mTabelDinamis;
    const SECRET_SALT = 'BPS_JATENG_SECRET_SALT';

    public function __construct()
    {
        $this->db = \Config\Database::connect('default');
        $this->mTabelDinamis = new \App\Models\M_tabeldinamis();
    }

    /**
     * Parse berkas Excel yang diunggah dan ekstrak data sel matriks
     * 
     * @param string $filePath Path file fisik di server (temp name)
     * @param int $expectedIdTahunTabel ID tabel tahunan yang diharapkan
     * @return array Hasil parsing terstruktur
     * @throws \Exception
     */
    public function parse(string $filePath, int $expectedIdTahunTabel): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Berkas Excel tidak ditemukan di server.");
        }

        // 1. Muat Berkas Excel menggunakan PhpSpreadsheet
        try {
            $reader = IOFactory::createReader('Xlsx');
            // Hanya baca data, jangan format formula agar cepat & hemat memori
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable $e) {
            throw new \Exception("Gagal membaca berkas Excel. Pastikan berkas berformat .xlsx yang valid. Error: " . $e->getMessage());
        }

        // 2. Verifikasi Lembar Kerja Tersembunyi __METADATA_DDA__
        $metaSheet = $spreadsheet->getSheetByName('__METADATA_DDA__');
        if (!$metaSheet) {
            throw new \Exception("Berkas ini tidak memiliki lembar metadata resmi (__METADATA_DDA__). Pastikan Anda mengunggah berkas template resmi yang diunduh dari sistem DDA Online.");
        }

        // 3. Ekstraksi Informasi Kunci dari Metadata
        $payloadRaw = $metaSheet->getCell('B15')->getValue();
        $metaPayload = null;

        if (!empty($payloadRaw)) {
            $metaPayload = json_decode($payloadRaw, true);
        }

        // Fallback jika cell B15 kosong atau tidak valid JSON: baca manual baris per baris
        if (empty($metaPayload) || !is_array($metaPayload)) {
            $metaPayload = [
                'id_tahun_tabel' => (int)$metaSheet->getCell('B3')->getValue(),
                'id_indikator'   => (int)$metaSheet->getCell('B4')->getValue(),
                'data_start_row' => (int)$metaSheet->getCell('B10')->getValue(),
                'data_end_row'   => (int)$metaSheet->getCell('B11')->getValue(),
                'checksum'       => (string)$metaSheet->getCell('B14')->getValue(),
            ];
        }

        $idTahunTabelExcel = (int)($metaPayload['id_tahun_tabel'] ?? 0);
        $idIndikatorExcel  = (int)($metaPayload['id_indikator'] ?? 0);

        // 4. Validasi Kesesuaian Target Tabel
        if ($idTahunTabelExcel !== $expectedIdTahunTabel) {
            // Ambil info nama tabel yang salah diunggah untuk pesan error yang ramah pengguna
            $infoSalah = $this->db->table('t_tahun_tabel')
                ->select('t_tahun_tabel.no_tabel, m_list_tabel.judul_ind')
                ->join('m_list_tabel', 't_tahun_tabel.id_tabel = m_list_tabel.id', 'left')
                ->where('t_tahun_tabel.id', $idTahunTabelExcel)
                ->get()->getRow();

            $namaTabelSalah = $infoSalah ? ($infoSalah->no_tabel ? 'Tabel ' . $infoSalah->no_tabel . ' ' : '') . $infoSalah->judul_ind : "ID {$idTahunTabelExcel}";

            throw new \Exception("Berkas Excel yang diunggah tidak cocok! Berkas ini merupakan template untuk: [{$namaTabelSalah}]. Mohon unggah berkas template yang sesuai.");
        }

        // 5. Validasi Konfigurasi Indikator di Database
        $indikator = $this->db->table('m_indikator_tabel')
            ->where('id_tahun_tabel', $expectedIdTahunTabel)
            ->get()->getRow();

        if (!$indikator) {
            throw new \Exception("Konfigurasi dimensi tabel ini tidak ditemukan di sistem.");
        }

        if ((int)$indikator->id !== $idIndikatorExcel) {
            throw new \Exception("Konfigurasi indikator pada berkas Excel sudah kedaluwarsa karena struktur dimensi tabel telah diperbarui ulang oleh Admin BPS. Silakan unduh template Excel terbaru.");
        }

        // 6. Ambil Lembar Data Utama ('Data')
        $dataSheet = $spreadsheet->getSheetByName('Data') ?: $spreadsheet->getSheet(0);
        if (!$dataSheet) {
            throw new \Exception("Lembar kerja 'Data' tidak ditemukan pada berkas Excel.");
        }

        // 7. Ambil Metadata Leaf Columns & Row Items
        $leafColumns = $metaPayload['leaf_columns'] ?? [];
        $rowItems    = $metaPayload['row_items'] ?? [];

        // Jika metadata JSON tidak lengkap di B15, bangun kembali dari struktur database aktif
        if (empty($leafColumns) || empty($rowItems)) {
            $id_karakteristik = (int)($indikator->id_karakteristik_kelompok ?? 0);
            $id_baris         = (int)($indikator->id_baris_kelompok ?? 0);
            $id_periode       = (int)($indikator->id_periode_kelompok ?? 0);
            $posisi_hirarki   = $indikator->posisi_hirarki ?? 'kolom_atas';

            $itemsKolom     = $id_karakteristik > 0 ? $this->mTabelDinamis->getKarakteristikItems($id_karakteristik) : [];
            $kolomStructure = $this->mTabelDinamis->buildColumnStructure($itemsKolom);
            $itemsBaris     = $id_baris > 0 ? $this->mTabelDinamis->getBarisItems($id_baris) : [];
            $itemsPeriode   = $id_periode > 0 ? $this->mTabelDinamis->getPeriodeItems($id_periode) : [];

            $rawHasGroups   = !empty($kolomStructure['has_groups']);
            $maxDepth       = !empty($kolomStructure['max_depth']) ? (int)$kolomStructure['max_depth'] : (!empty($kolomStructure['has_groups']) ? 2 : 1);
            $tingkat_kolom  = $indikator->tingkat_kolom ?? 'semua';

            $parseActiveLevels = function(?string $val, int $depth): array {
                if (empty($val) || $val === 'semua') {
                    return ($depth === 3) ? [1, 2, 3] : (($depth === 2) ? [1, 2] : [1]);
                }
                if ($val === 'sub_bab') {
                    return ($depth === 3) ? [2, 3] : [2];
                }
                if ($val === 'bab') {
                    return [1];
                }
                $parts = array_filter(array_map('intval', explode(',', $val)));
                $parts = array_values(array_filter($parts, function($l) use ($depth) {
                    return $l >= 1 && $l <= $depth;
                }));
                return !empty($parts) ? $parts : (($depth === 3) ? [1, 2, 3] : (($depth === 2) ? [1, 2] : [1]));
            };

            $activeLevels = $parseActiveLevels($tingkat_kolom, $maxDepth);
            sort($activeLevels);

            $groups         = $kolomStructure['groups'] ?? [];
            $rawFlatKolom   = $kolomStructure['flat_items'] ?? $itemsKolom;

            // Tentukan flatKolom yang digunakan berdasarkan tingkat aktif terendah
            $flatKolom = $rawFlatKolom;
            if (!empty($kolomStructure) && $maxDepth === 3) {
                if (in_array(3, $activeLevels)) {
                    $flatKolom = $rawFlatKolom;
                } elseif (in_array(2, $activeLevels)) {
                    $flatKolom = [];
                    foreach ($groups as $grp) {
                        if (!empty($grp['subgroups'])) {
                            foreach ($grp['subgroups'] as $sub) {
                                $firstChild = !empty($sub['children']) ? $sub['children'][0] : null;
                                $flatKolom[] = (object)[
                                    'id'       => $firstChild ? (int)$firstChild->id : 0,
                                    'item_ind' => $sub['title_ind'],
                                    'item_en'  => $sub['title_en'] ?? $sub['title_ind'],
                                ];
                            }
                        } else {
                            $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                            $flatKolom[] = (object)[
                                'id'       => $firstChild ? (int)$firstChild->id : 0,
                                'item_ind' => $grp['title_ind'],
                                'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                            ];
                        }
                    }
                } else {
                    $flatKolom = [];
                    foreach ($groups as $grp) {
                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                        $flatKolom[] = (object)[
                            'id'       => $firstChild ? (int)$firstChild->id : 0,
                            'item_ind' => $grp['title_ind'],
                            'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                        ];
                    }
                }
            } elseif (!empty($kolomStructure) && $maxDepth === 2) {
                if (in_array(2, $activeLevels)) {
                    $flatKolom = $rawFlatKolom;
                } else {
                    $flatKolom = [];
                    foreach ($groups as $grp) {
                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                        $flatKolom[] = (object)[
                            'id'       => $firstChild ? (int)$firstChild->id : 0,
                            'item_ind' => $grp['title_ind'],
                            'item_en'  => $grp['title_en'] ?? $grp['title_ind'],
                        ];
                    }
                }
            }

            $hasKolom    = !empty($flatKolom);
            $hasPeriode  = !empty($itemsPeriode);
            $colIdx      = ($posisi_hirarki === 'periode_baris') ? 3 : 2; // Mulai C jika periode_baris, atau B jika kolom biasa

            // Hitung level maksimum pada seluruh kelompok baris
            $maxBarisGroupLevel = 0;
            foreach ($itemsBaris as $b) {
                $lvl = (int)($b->level_indentasi ?? 0);
                if ($lvl > $maxBarisGroupLevel) {
                    $maxBarisGroupLevel = $lvl;
                }
            }

            $isParentBaris = function(int $idx, array $items) use ($maxBarisGroupLevel): bool {
                // Jika seluruh kelompok baris ber-level 0 (daftar flat), tidak ada baris yang merupakan parent banner!
                if ($maxBarisGroupLevel === 0) {
                    return false;
                }

                $cur = $items[$idx];
                $tipe = $cur->tipe_baris ?? 'data';
                $lowerNama = strtolower(trim($cur->nama_baris_ind ?? ''));
                if ($tipe !== 'total') {
                    if (
                        $lowerNama === 'jumlah' || $lowerNama === 'total' || 
                        strpos($lowerNama, 'jawa tengah') !== false || 
                        strpos($lowerNama, 'produk domestik regional bruto') !== false || 
                        $lowerNama === 'pdrb' || 
                        strpos($lowerNama, 'gross regional domestic product') !== false || 
                        $lowerNama === 'grdp'
                    ) {
                        if (strpos($lowerNama, 'curah hujan') === false && strpos($lowerNama, 'hari hujan') === false) {
                            $tipe = 'total';
                        }
                    }
                }
                if ($tipe === 'total' || $tipe === 'subtotal') {
                    return false;
                }

                $curLevel = (int)($cur->level_indentasi ?? 0);
                $hasChild = false;
                if ($idx + 1 < count($items)) {
                    $next = $items[$idx + 1];
                    $nextLevel = (int)($next->level_indentasi ?? 0);
                    if ($nextLevel > $curLevel) {
                        $hasChild = true;
                    }
                }

                if ($hasChild) {
                    return true;
                }

                if ($tipe === 'kategori' && ($lowerNama === 'kabupaten' || $lowerNama === 'kota')) {
                    return true;
                }

                return false;
            };

            if ($posisi_hirarki === 'periode_baris') {
                if ($hasKolom) {
                    foreach ($flatKolom as $ch) {
                        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                        $leafColumns[] = [
                            'excel_col'  => $colLetter,
                            'item_id'    => (int)$ch->id,
                            'periode_id' => 0,
                        ];
                        $colIdx++;
                    }
                } else {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $leafColumns[] = [
                        'excel_col'  => $colLetter,
                        'item_id'    => 0,
                        'periode_id' => 0,
                    ];
                    $colIdx++;
                }

                $startR = (int)($metaPayload['data_start_row'] ?? 7);
                $currR  = $startR;
                foreach ($itemsBaris as $bIdx => $bItem) {
                    $tipe = $bItem->tipe_baris ?? 'data';
                    $lowerBaris = strtolower(trim($bItem->nama_baris_ind ?? ''));
                    if ($tipe !== 'total') {
                        if (
                            $lowerBaris === 'jumlah' || $lowerBaris === 'total' || 
                            strpos($lowerBaris, 'jawa tengah') !== false || 
                            strpos($lowerBaris, 'produk domestik regional bruto') !== false || 
                            $lowerBaris === 'pdrb' || 
                            strpos($lowerBaris, 'gross regional domestic product') !== false || 
                            $lowerBaris === 'grdp'
                        ) {
                            if (strpos($lowerBaris, 'curah hujan') === false && strpos($lowerBaris, 'hari hujan') === false) {
                                $tipe = 'total';
                            }
                        }
                    }
                    if ($isParentBaris($bIdx, $itemsBaris)) {
                        $tipe = 'kategori';
                    }

                    $cfgTotal = $indikator->konfigurasi_total ?? 'default';
                    if ($tipe === 'total' && $cfgTotal === 'none') {
                        continue;
                    }

                    if ($tipe === 'kategori') {
                        $rowItems[] = [
                            'excel_row'  => $currR,
                            'id_baris'   => (int)$bItem->id,
                            'id_periode' => 0,
                            'tipe'       => 'kategori',
                            'nama'       => $bItem->nama_baris_ind ?? '',
                        ];
                        $currR++;
                    } else {
                        if ($hasPeriode) {
                            foreach ($itemsPeriode as $p) {
                                $rowItems[] = [
                                    'excel_row'  => $currR,
                                    'id_baris'   => (int)$bItem->id,
                                    'id_periode' => (int)$p->id,
                                    'tipe'       => $tipe,
                                    'nama'       => ($bItem->nama_baris_ind ?? '') . ' (' . ($p->nama_periode_ind ?? '') . ')',
                                ];
                                $currR++;
                            }
                        } else {
                            $rowItems[] = [
                                'excel_row'  => $currR,
                                'id_baris'   => (int)$bItem->id,
                                'id_periode' => 0,
                                'tipe'       => $tipe,
                                'nama'       => $bItem->nama_baris_ind ?? '',
                            ];
                            $currR++;
                        }
                    }
                }
            } elseif (!$hasKolom && $hasPeriode) {
                foreach ($itemsPeriode as $p) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $leafColumns[] = [
                        'excel_col'  => $colLetter,
                        'item_id'    => 0,
                        'periode_id' => (int)$p->id,
                    ];
                    $colIdx++;
                }
            } elseif ($hasKolom && !$hasPeriode) {
                foreach ($flatKolom as $ch) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $leafColumns[] = [
                        'excel_col'  => $colLetter,
                        'item_id'    => (int)$ch->id,
                        'periode_id' => 0,
                    ];
                    $colIdx++;
                }
            } elseif ($hasKolom && $hasPeriode) {
                if ($posisi_hirarki === 'periode_atas') {
                    foreach ($itemsPeriode as $p) {
                        foreach ($flatKolom as $ch) {
                            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                            $leafColumns[] = [
                                'excel_col'  => $colLetter,
                                'item_id'    => (int)$ch->id,
                                'periode_id' => (int)$p->id,
                            ];
                            $colIdx++;
                        }
                    }
                } else {
                    foreach ($flatKolom as $ch) {
                        foreach ($itemsPeriode as $p) {
                            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                            $leafColumns[] = [
                                'excel_col'  => $colLetter,
                                'item_id'    => (int)$ch->id,
                                'periode_id' => (int)$p->id,
                            ];
                            $colIdx++;
                        }
                    }
                }
            }

            if ($posisi_hirarki !== 'periode_baris') {
                $startR = (int)($metaPayload['data_start_row'] ?? 7);
                $currR  = $startR;
                foreach ($itemsBaris as $bIdx => $bItem) {
                    $tipe = $bItem->tipe_baris ?? 'data';
                    $lowerBaris = strtolower(trim($bItem->nama_baris_ind ?? ''));
                    if ($tipe !== 'total') {
                        if (
                            $lowerBaris === 'jumlah' || $lowerBaris === 'total' || 
                            strpos($lowerBaris, 'jawa tengah') !== false || 
                            strpos($lowerBaris, 'produk domestik regional bruto') !== false || 
                            $lowerBaris === 'pdrb' || 
                            strpos($lowerBaris, 'gross regional domestic product') !== false || 
                            $lowerBaris === 'grdp'
                        ) {
                            if (strpos($lowerBaris, 'curah hujan') === false && strpos($lowerBaris, 'hari hujan') === false) {
                                $tipe = 'total';
                            }
                        }
                    }
                    if ($isParentBaris($bIdx, $itemsBaris)) {
                        $tipe = 'kategori';
                    }

                    $cfgTotal = $indikator->konfigurasi_total ?? 'default';
                    if ($tipe === 'total' && $cfgTotal === 'none') {
                        continue;
                    }

                    $rowItems[] = [
                        'excel_row' => $currR,
                        'id_baris'  => (int)$bItem->id,
                        'tipe'      => $tipe,
                        'nama'      => $bItem->nama_baris_ind ?? '',
                    ];
                    $currR++;
                }
            }
        }

        // 8. Ekstraksi Sel Data dari Lembar 'Data'
        $cellsToUpsert   = [];
        $totalCells      = 0;
        $filledCells     = 0;
        $emptyCells      = 0;
        $symbolCells     = 0;
        $skippedCatRows  = 0;

        foreach ($rowItems as $rItem) {
            $excelRow = (int)$rItem['excel_row'];
            $idBaris  = (int)$rItem['id_baris'];
            $tipe     = $rItem['tipe'] ?? 'data';

            // Lewati baris bertipe 'kategori' karena merupakan judul kelompok tanpa nilai data
            if ($tipe === 'kategori') {
                $skippedCatRows++;
                continue;
            }

            foreach ($leafColumns as $colItem) {
                $colLetter       = $colItem['excel_col'];
                $idKarakteristik = (int)$colItem['item_id'];
                $idPeriode       = (int)($rItem['id_periode'] ?? ($colItem['periode_id'] ?? 0));

                $cellCoord = "{$colLetter}{$excelRow}";
                $cell = $dataSheet->getCell($cellCoord);

                // Dapatkan nilai sel (jika berupa formula, ambil nilai hasil perhitungannya)
                try {
                    $rawVal = $cell ? $cell->getCalculatedValue() : null;
                } catch (\Throwable $e) {
                    $rawVal = $cell ? $cell->getValue() : null;
                }

                $sanitized = $this->sanitizeValue($rawVal);
                $totalCells++;

                if ($sanitized['nilai'] !== null || $sanitized['simbol'] !== null) {
                    $filledCells++;
                    if ($sanitized['simbol'] !== null && $sanitized['nilai'] === null) {
                        $symbolCells++;
                    }
                } else {
                    $emptyCells++;
                }

                $cellsToUpsert[] = [
                    'id_baris_item'             => $idBaris,
                    'id_karakteristik_item'     => $idKarakteristik,
                    'id_sub_karakteristik_item' => 0,
                    'id_periode_item'           => $idPeriode,
                    'nilai'                     => $sanitized['nilai'],
                    'simbol'                    => $sanitized['simbol'],
                ];
            }
        }

        return [
            'status'            => 'success',
            'id_tahun_tabel'    => $expectedIdTahunTabel,
            'id_indikator'      => (int)$indikator->id,
            'total_cells'       => $totalCells,
            'filled_cells'      => $filledCells,
            'empty_cells'       => $emptyCells,
            'symbol_cells'      => $symbolCells,
            'skipped_cat_rows'  => $skippedCatRows,
            'cells_data'        => $cellsToUpsert,
        ];
    }

    /**
     * Sanitasi dan pemisahan nilai sel antara angka desimal dan simbol statistik BPS
     * 
     * @param mixed $rawValue
     * @return array ['nilai' => float|null, 'simbol' => string|null]
     */
    protected function sanitizeValue($rawValue): array
    {
        if ($rawValue === null || $rawValue === '') {
            return ['nilai' => null, 'simbol' => null];
        }

        // Jika sudah berupa float / int dari PhpSpreadsheet
        if (is_numeric($rawValue)) {
            return [
                'nilai'  => (float)$rawValue,
                'simbol' => null
            ];
        }

        $str = trim((string)$rawValue);
        if ($str === '') {
            return ['nilai' => null, 'simbol' => null];
        }

        // Deteksi Simbol Statistik Resmi BPS
        // '-' atau '–' (En Dash) atau '—' (Em Dash): Tidak ada data / strip
        if ($str === '-' || $str === '–' || $str === '—' || $str === '−') {
            return ['nilai' => null, 'simbol' => '-'];
        }

        // 'NA' / 'N/A': Data belum tersedia
        if (strcasecmp($str, 'NA') === 0 || strcasecmp($str, 'N/A') === 0) {
            return ['nilai' => null, 'simbol' => 'NA'];
        }

        // Teks dengan angka estimasi e.g. "125e" atau "e125"
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*e$/i', $str, $m)) {
            $numClean = str_replace(',', '.', str_replace('.', '', $m[1]));
            return ['nilai' => (float)$numClean, 'simbol' => 'e'];
        }

        // Normalisasi format desimal Indonesia: '1.234,56' -> '1234.56' atau '12,5' -> '12.5'
        $cleaned = $str;
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Ada titik dan koma: titik adalah ribuan, koma adalah desimal
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (strpos($cleaned, ',') !== false) {
            // Hanya ada koma: koma berfungsi sebagai desimal
            $cleaned = str_replace(',', '.', $cleaned);
        }

        // Bersihkan karakter spasi atau non-breaking space
        $cleaned = preg_replace('/\s+/', '', $cleaned);

        if (is_numeric($cleaned)) {
            return [
                'nilai'  => (float)$cleaned,
                'simbol' => null
            ];
        }

        // Jika bukan angka, simpan sebagai simbol teks terbatas (maks 10 char)
        return [
            'nilai'  => null,
            'simbol' => substr($str, 0, 10)
        ];
    }
}
