<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Service Generator Template Excel Resmi (.xlsx) untuk Tabel Dinamis DDA Online
 * Standar Publikasi BPS Provinsi Jawa Tengah (Sprint 4)
 */
class ExcelTemplateGenerator
{
    protected $db;
    protected $mTabelDinamis;

    public function __construct()
    {
        $this->db = \Config\Database::connect('default');
        $this->mTabelDinamis = new \App\Models\M_tabeldinamis();
    }

    /**
     * Generate berkas Spreadsheet untuk id_tahun_tabel tertentu
     * 
     * @param int $id_tahun_tabel
     * @return Spreadsheet
     * @throws \Exception
     */
    public function generate(int $id_tahun_tabel): Spreadsheet
    {
        // 1. Ambil data master tabel & tahun tabel
        $tabel = $this->db->table('t_tahun_tabel')
            ->select('t_tahun_tabel.id as id_tahun_tabel, t_tahun_tabel.tahun, t_tahun_tabel.no_tabel, 
                      t_tahun_tabel.periode_id, t_tahun_tabel.periode_en, t_tahun_tabel.is_dynamic, 
                      m_list_tabel.id as id_master_tabel, m_list_tabel.judul_ind, m_list_tabel.judul_en, 
                      m_list_tabel.id_unitkerja, m_unitkerja.unitkerja_ind, m_unitkerja.unitkerja_en')
            ->join('m_list_tabel', 't_tahun_tabel.id_tabel = m_list_tabel.id', 'left')
            ->join('m_unitkerja', 'm_list_tabel.id_unitkerja = m_unitkerja.id_unitkerja', 'left')
            ->where('t_tahun_tabel.id', $id_tahun_tabel)
            ->get()->getRow();

        if (!$tabel) {
            throw new \Exception("Tabel dengan ID {$id_tahun_tabel} tidak ditemukan.");
        }

        // 2. Ambil konfigurasi indikator tabel dinamis
        $indikator = $this->db->table('m_indikator_tabel')
            ->where('id_tahun_tabel', $id_tahun_tabel)
            ->get()->getRow();

        if (!$indikator) {
            throw new \Exception("Tabel ini belum memiliki konfigurasi dimensi matriks. Silakan rakit tabel dinamis terlebih dahulu di menu Generator Tabel.");
        }

        $id_karakteristik = (int)($indikator->id_karakteristik_kelompok ?? 0);
        $id_baris         = (int)($indikator->id_baris_kelompok ?? 0);
        $id_periode       = (int)($indikator->id_periode_kelompok ?? 0);
        $posisi_hirarki   = $indikator->posisi_hirarki ?? 'kolom_atas';
        $satuan           = trim((string)($indikator->satuan ?? ''));
        $desimal          = (int)($indikator->desimal ?? 2);
        
        // Footnote: ambil dari instansi/unit kerja, tanpa catatan
        $sumber = !empty($tabel->unitkerja_ind) ? $tabel->unitkerja_ind : 'BPS Provinsi Jawa Tengah';

        // 3. Ambil data dimensi
        $itemsKolom     = $id_karakteristik > 0 ? $this->mTabelDinamis->getKarakteristikItems($id_karakteristik) : [];
        $kolomStructure = $this->mTabelDinamis->buildColumnStructure($itemsKolom);
        
        $kelompokBaris  = $id_baris > 0 ? $this->mTabelDinamis->getBarisKelompok($id_baris) : null;
        $itemsBaris     = $id_baris > 0 ? $this->mTabelDinamis->getBarisItems($id_baris) : [];
        $kelompokPeriode = $id_periode > 0 ? $this->mTabelDinamis->getPeriodeKelompok($id_periode) : null;
        $itemsPeriode   = $id_periode > 0 ? $this->mTabelDinamis->getPeriodeItems($id_periode) : [];

        $dimCount = (!empty($itemsBaris) ? 1 : 0) + (!empty($itemsKolom) ? 1 : 0) + (!empty($itemsPeriode) ? 1 : 0);
        if ($dimCount < 2) {
            throw new \Exception("Konfigurasi dimensi tabel harus memiliki minimal 2 dimensi yang aktif.");
        }

        // Jika tabel tidak memiliki dimensi baris (misal runtun waktu makro),
        // posisi hirarki otomatis 'kolom_atas' (karena periode adalah sumbu baris vertikal di Kolom A)
        if (empty($itemsBaris)) {
            $posisi_hirarki = 'kolom_atas';
        }

        // 4. Inisialisasi Spreadsheet
        $spreadsheet = new Spreadsheet();
        
        // Metadata Dokumen
        $spreadsheet->getProperties()
            ->setCreator('BPS Provinsi Jawa Tengah')
            ->setLastModifiedBy('DDA Online System')
            ->setTitle('Template Pengisian DDA - ' . ($tabel->no_tabel ? $tabel->no_tabel . ' ' : '') . $tabel->judul_ind)
            ->setSubject('Template Tabel Dinamis Jawa Tengah Dalam Angka')
            ->setDescription('Berkas template pengisian data tabel dinamis DDA Online berstandar BPS.');

        // Sheet Utama: Data
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');
        $sheet->setShowGridLines(true);

        // Judul Periode Kolom Kedua (Jika Periode Baris Aktif atau sebagai sumbu baris)
        $titlePeriodeInd = !empty($kelompokPeriode->judul_periode_ind) ? $kelompokPeriode->judul_periode_ind : ($kelompokPeriode->nama_kelompok_ind ?? 'Tahun');
        $titlePeriodeEn  = !empty($kelompokPeriode->judul_periode_en) ? $kelompokPeriode->judul_periode_en : ($kelompokPeriode->nama_kelompok_en ?? 'Year');

        // Judul Baris Kolom Pertama
        if (empty($itemsBaris)) {
            $titleBarisInd = $titlePeriodeInd;
            $titleBarisEn  = $titlePeriodeEn;
        } else {
            $titleBarisInd = !empty($kelompokBaris->judul_baris_ind) ? $kelompokBaris->judul_baris_ind : ($kelompokBaris->nama_kelompok_ind ?? 'Kabupaten/Kota');
            $titleBarisInd = preg_replace('/^\d+\s+/', '', $titleBarisInd); // Bersihkan prefix angka
            $titleBarisEn  = !empty($kelompokBaris->judul_baris_en) ? $kelompokBaris->judul_baris_en : ($kelompokBaris->nama_kelompok_en ?? '');
            if (empty($titleBarisEn) || strtolower($titleBarisEn) === strtolower($titleBarisInd)) {
                $lower = strtolower($titleBarisInd);
                if (strpos($lower, 'kabupaten') !== false && strpos($lower, 'kota') !== false) {
                    $titleBarisEn = 'Regency/Municipality';
                } elseif (strpos($lower, 'kabupaten') !== false) {
                    $titleBarisEn = 'Regency';
                } elseif (strpos($lower, 'kota') !== false) {
                    $titleBarisEn = 'Municipality';
                } elseif (strpos($lower, 'kecamatan') !== false) {
                    $titleBarisEn = 'District';
                } elseif (strpos($lower, 'desa') !== false || strpos($lower, 'kelurahan') !== false) {
                    $titleBarisEn = 'Village';
                } else {
                    $titleBarisEn = 'Detail / Region';
                }
            }
        }

        // =====================================================================
        // =====================================================================
        // 5. STRUKTUR LEAF COLUMNS & HIERARKI HEADER
        // =====================================================================
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
                                'id'              => $firstChild ? (int)$firstChild->id : 0,
                                'item_ind'        => $sub['title_ind'],
                                'item_en'         => $sub['title_en'] ?? $sub['title_ind'],
                                'group_title_ind' => $grp['title_ind'],
                                'group_title_en'  => $grp['title_en'] ?? '',
                            ];
                        }
                    } else {
                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                        $flatKolom[] = (object)[
                            'id'              => $firstChild ? (int)$firstChild->id : 0,
                            'item_ind'        => $grp['title_ind'],
                            'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                            'group_title_ind' => null,
                            'group_title_en'  => null,
                        ];
                    }
                }
            } else {
                $flatKolom = [];
                foreach ($groups as $grp) {
                    $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                    $flatKolom[] = (object)[
                        'id'              => $firstChild ? (int)$firstChild->id : 0,
                        'item_ind'        => $grp['title_ind'],
                        'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                        'group_title_ind' => null,
                        'group_title_en'  => null,
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
                        'id'              => $firstChild ? (int)$firstChild->id : 0,
                        'item_ind'        => $grp['title_ind'],
                        'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                        'group_title_ind' => null,
                        'group_title_en'  => null,
                    ];
                }
            }
        }

        $hasKolom    = !empty($flatKolom);
        $hasPeriode  = !empty($itemsPeriode);
        $hasBaris    = !empty($itemsBaris);

        // Susun daftar kolom leaf (kolom paling bawah yang menampung sel angka)
        $leafColumns = [];

        if (!$hasBaris && $hasKolom && $hasPeriode) {
            // TABEL RUNTUN WAKTU MAKRO: Periode sebagai sumbu baris vertikal, Karakteristik sebagai kolom
            foreach ($flatKolom as $child) {
                $leafColumns[] = [
                    'group_title_ind' => $child->grup_induk_ind ?? ($child->group_title_ind ?? null),
                    'group_title_en'  => $child->grup_induk_en ?? ($child->group_title_en ?? null),
                    'item_id'         => (int)$child->id,
                    'item_ind'        => $child->item_ind,
                    'item_en'         => $child->item_en ?? '',
                    'periode_id'      => 0,
                    'periode_ind'     => '',
                    'periode_en'      => '',
                ];
            }
        } elseif (!$hasKolom && $hasPeriode) {
            // HANYA PERIODE
            foreach ($itemsPeriode as $p) {
                $leafColumns[] = [
                    'group_title_ind' => null,
                    'group_title_en'  => null,
                    'item_id'         => 0,
                    'item_ind'        => '',
                    'item_en'         => '',
                    'periode_id'      => (int)$p->id,
                    'periode_ind'     => $p->nama_periode_ind,
                    'periode_en'      => $p->nama_periode_en ?? '',
                ];
            }
        } elseif ($hasKolom && !$hasPeriode) {
            // HANYA KOLOM
            foreach ($flatKolom as $child) {
                $leafColumns[] = [
                    'group_title_ind' => $child->grup_induk_ind ?? ($child->group_title_ind ?? null),
                    'group_title_en'  => $child->grup_induk_en ?? ($child->group_title_en ?? null),
                    'item_id'         => (int)$child->id,
                    'item_ind'        => $child->item_ind,
                    'item_en'         => $child->item_en ?? '',
                    'periode_id'      => 0,
                    'periode_ind'     => '',
                    'periode_en'      => '',
                ];
            }
        } elseif ($hasKolom && $hasPeriode) {
            if ($posisi_hirarki === 'periode_baris') {
                // PERIODE MENJADI BARIS (PIVOT KE BARIS)
                foreach ($flatKolom as $child) {
                    $leafColumns[] = [
                        'group_title_ind' => $child->grup_induk_ind ?? ($child->group_title_ind ?? null),
                        'group_title_en'  => $child->grup_induk_en ?? ($child->group_title_en ?? null),
                        'item_id'         => (int)$child->id,
                        'item_ind'        => $child->item_ind,
                        'item_en'         => $child->item_en ?? '',
                        'periode_id'      => 0, // Ditentukan di row_items per baris periode
                        'periode_ind'     => '',
                        'periode_en'      => '',
                    ];
                }
            } elseif ($posisi_hirarki === 'periode_atas') {
                // PERIODE DI ATAS, KOLOM DI BAWAH
                foreach ($itemsPeriode as $p) {
                    foreach ($flatKolom as $child) {
                        $leafColumns[] = [
                            'group_title_ind' => $p->nama_periode_ind,
                            'group_title_en'  => $p->nama_periode_en ?? '',
                            'item_id'         => (int)$child->id,
                            'item_ind'        => $child->item_ind,
                            'item_en'         => $child->item_en ?? '',
                            'periode_id'      => (int)$p->id,
                            'periode_ind'     => $p->nama_periode_ind,
                            'periode_en'      => $p->nama_periode_en ?? '',
                        ];
                    }
                }
            } else {
                // KOLOM DI ATAS, PERIODE DI BAWAH
                foreach ($flatKolom as $child) {
                    foreach ($itemsPeriode as $p) {
                        $leafColumns[] = [
                            'group_title_ind' => $child->grup_induk_ind ?? ($child->group_title_ind ?? null),
                            'group_title_en'  => $child->grup_induk_en ?? ($child->group_title_en ?? null),
                            'item_id'         => (int)$child->id,
                            'item_ind'        => $child->item_ind,
                            'item_en'         => $child->item_en ?? '',
                            'periode_id'      => (int)$p->id,
                            'periode_ind'     => $p->nama_periode_ind,
                            'periode_en'      => $p->nama_periode_en ?? '',
                        ];
                    }
                }
            }
        }

        $totalLeafCols = count($leafColumns);
        $lastColIndex  = ($posisi_hirarki === 'periode_baris') ? (2 + $totalLeafCols) : (1 + $totalLeafCols);
        $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);

        // =====================================================================
        // 6. RENDER KOP TABEL BPS (HEADER DOKUMEN)
        // =====================================================================
        $row = 2;

        // Nomor Tabel
        $noTabelText = !empty($tabel->no_tabel) ? 'Tabel / Table ' . $tabel->no_tabel : 'Tabel / Table';
        $sheet->setCellValue("A{$row}", $noTabelText);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->setName('Arial');
        $row++;

        // Judul Bahasa Indonesia
        $judulIndText = $tabel->judul_ind;
        if (!empty($tabel->periode_id)) {
            $judulIndText .= ', ' . $tabel->periode_id;
        } elseif (!empty($tabel->tahun)) {
            $judulIndText .= ', ' . $tabel->tahun;
        }
        $sheet->setCellValue("A{$row}", $judulIndText);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11)->setName('Arial');
        $row++;

        // Judul Bahasa Inggris (Italic)
        if (!empty($tabel->judul_en)) {
            $judulEnText = $tabel->judul_en;
            if (!empty($tabel->periode_en)) {
                $judulEnText .= ', ' . $tabel->periode_en;
            } elseif (!empty($tabel->tahun)) {
                $judulEnText .= ', ' . $tabel->tahun;
            }
            $sheet->setCellValue("A{$row}", $judulEnText);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'))->setName('Arial');
            $row++;
        }

        // Satuan Data jika ada
        if (!empty($satuan)) {
            $sheet->setCellValue("A{$row}", "(" . $satuan . ")");
            $sheet->getStyle("A{$row}")->getFont()->setSize(9)->setItalic(true)->setName('Arial');
            $row++;
        }

        // Jarak kosong sebelum grid header
        $row++;
        $startHeaderRow = $row;

        // Hitung baris tingkatan header
        $headerDataRows = 1; // Default: 1 baris
        if ($hasKolom && $hasPeriode) {
            $headerDataRows = ($posisi_hirarki === 'periode_baris') ? count($activeLevels) : (count($activeLevels) + 1);
        } elseif ($hasKolom) {
            $headerDataRows = count($activeLevels);
        } elseif ($hasPeriode) {
            $headerDataRows = 1;
        }

        $numRow = $startHeaderRow + $headerDataRows; // Baris nomor kolom: (1), (2), (3) ...

        // =====================================================================
        // 7. RENDER GRID HEADER MULTI-LEVEL (WARNA ORANYE BPS JATENG)
        // =====================================================================
        // Warna oranye standar BPS: #EA580C
        $headerFillColor = 'EA580C';
        $headerNumColor  = 'C2410C';
        $fontColorWhite  = 'FFFFFF';

        // Kolom A: Judul Baris (Merge dari startHeaderRow sampai startHeaderRow + headerDataRows - 1)
        $sheet->mergeCells("A{$startHeaderRow}:A" . ($numRow - 1));
        $cellTitleBaris = $titleBarisInd . (!empty($titleBarisEn) ? "\n" . $titleBarisEn : "");
        $sheet->setCellValue("A{$startHeaderRow}", $cellTitleBaris);

        // Styling Kolom A Header
        $sheet->getStyle("A{$startHeaderRow}:A" . ($numRow - 1))->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => $fontColorWhite],
                'size'  => 10,
                'name'  => 'Arial'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $headerFillColor]
            ]
        ]);

        if ($posisi_hirarki === 'periode_baris') {
            // Kolom B: Judul Periode (Tahun / Year)
            $sheet->mergeCells("B{$startHeaderRow}:B" . ($numRow - 1));
            $cellTitlePeriode = $titlePeriodeInd . (!empty($titlePeriodeEn) ? "\n" . $titlePeriodeEn : "");
            $sheet->setCellValue("B{$startHeaderRow}", $cellTitlePeriode);

            $sheet->getStyle("B{$startHeaderRow}:B" . ($numRow - 1))->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => $fontColorWhite],
                    'size'  => 10,
                    'name'  => 'Arial'
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $headerFillColor]
                ]
            ]);
        }

        // Render Header Kolom Data (Mulai dari Kolom C jika periode_baris, atau Kolom B jika standar)
        $startDataColIndex = ($posisi_hirarki === 'periode_baris') ? 3 : 2;

        // Helper untuk merender tingkat kolom ke sel Excel
        $fillLevelCellsExcel = function(int $lvl, int $rowNum, int $startCol, int $multiplier) use ($sheet, $groups, $flatKolom, $maxDepth, $activeLevels): int {
            $curCol = $startCol;
            $hasL1 = in_array(1, $activeLevels);
            $hasL2 = in_array(2, $activeLevels);
            $hasL3 = in_array(3, $activeLevels);

            if ($maxDepth === 1) {
                if ($lvl === 1) {
                    foreach ($flatKolom as $item) {
                        $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                        $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $multiplier - 1);
                        if ($multiplier > 1) {
                            $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                        }
                        $text = $item->item_ind . (!empty($item->item_en) ? "\n" . $item->item_en : '');
                        $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                        $curCol += $multiplier;
                    }
                }
            } elseif ($maxDepth === 2) {
                if ($lvl === 1) {
                    foreach ($groups as $grp) {
                        if ($grp['type'] === 'group') {
                            $leafCount = $hasL2 ? count($grp['children']) : 1;
                            $span = $leafCount * $multiplier;
                            $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                            $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                            if ($span > 1) {
                                $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                            }
                            $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                            $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                            $curCol += $span;
                        } else {
                            $span = 1 * $multiplier;
                            $rowSpan = $hasL2 ? 2 : 1;
                            $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                            $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                            $toRow         = $rowNum + $rowSpan - 1;
                            if ($span > 1 || $rowSpan > 1) {
                                $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$toRow}");
                            }
                            $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                            $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                            $curCol += $span;
                        }
                    }
                } elseif ($lvl === 2) {
                    foreach ($groups as $grp) {
                        if ($grp['type'] === 'group') {
                            foreach ($grp['children'] as $child) {
                                $span = $multiplier;
                                $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                if ($span > 1) {
                                    $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                }
                                $text = $child->item_ind . (!empty($child->item_en) ? "\n" . $child->item_en : '');
                                $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                $curCol += $span;
                            }
                        } else {
                            if (!$hasL1) {
                                $span = $multiplier;
                                $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                if ($span > 1) {
                                    $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                }
                                $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                                $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                $curCol += $span;
                            }
                        }
                    }
                }
            } elseif ($maxDepth === 3) {
                if ($lvl === 1) {
                    foreach ($groups as $grp) {
                        if ($grp['type'] === 'group') {
                            if ($hasL3) {
                                $leafCount = count($grp['children']);
                            } elseif ($hasL2 && !empty($grp['subgroups'])) {
                                $leafCount = count($grp['subgroups']);
                            } else {
                                $leafCount = 1;
                            }
                            $span = $leafCount * $multiplier;
                            $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                            $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                            if ($span > 1) {
                                $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                            }
                            $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                            $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                            $curCol += $span;
                        } else {
                            $span = 1 * $multiplier;
                            $rowSpan = count($activeLevels);
                            $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                            $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                            $toRow         = $rowNum + $rowSpan - 1;
                            if ($span > 1 || $rowSpan > 1) {
                                $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$toRow}");
                            }
                            $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                            $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                            $curCol += $span;
                        }
                    }
                } elseif ($lvl === 2) {
                    foreach ($groups as $grp) {
                        if ($grp['type'] === 'group') {
                            if (!empty($grp['subgroups'])) {
                                foreach ($grp['subgroups'] as $sub) {
                                    $leafCount = $hasL3 ? count($sub['children']) : 1;
                                    $span = $leafCount * $multiplier;
                                    $rowSpan = ($sub['type'] === 'standalone' && $hasL3) ? 2 : 1;
                                    $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                    $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                    $toRow         = $rowNum + $rowSpan - 1;
                                    if ($span > 1 || $rowSpan > 1) {
                                        $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$toRow}");
                                    }
                                    $text = $sub['title_ind'] . (!empty($sub['title_en']) ? "\n" . $sub['title_en'] : '');
                                    $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                    $curCol += $span;
                                }
                            } else {
                                if ($hasL3) {
                                    foreach ($grp['children'] as $child) {
                                        $span = $multiplier;
                                        $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                        $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                        if ($span > 1) {
                                            $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                        }
                                        $text = $child->item_ind . (!empty($child->item_en) ? "\n" . $child->item_en : '');
                                        $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                        $curCol += $span;
                                    }
                                } else {
                                    $span = $multiplier;
                                    $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                    $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                    if ($span > 1) {
                                        $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                    }
                                    $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                                    $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                    $curCol += $span;
                                }
                            }
                        } else {
                            if (!$hasL1) {
                                $span = 1 * $multiplier;
                                $rowSpan = $hasL3 ? 2 : 1;
                                $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                $toRow         = $rowNum + $rowSpan - 1;
                                if ($span > 1 || $rowSpan > 1) {
                                    $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$toRow}");
                                }
                                $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                                $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                $curCol += $span;
                            }
                        }
                    }
                } elseif ($lvl === 3) {
                    foreach ($groups as $grp) {
                        if ($grp['type'] === 'group') {
                            if (!empty($grp['subgroups'])) {
                                foreach ($grp['subgroups'] as $sub) {
                                    if ($sub['type'] === 'standalone' && ($hasL2 || $hasL1)) {
                                        continue;
                                    }
                                    foreach ($sub['children'] as $child) {
                                        $span = $multiplier;
                                        $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                        $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                        if ($span > 1) {
                                            $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                        }
                                        $text = $child->item_ind . (!empty($child->item_en) ? "\n" . $child->item_en : '');
                                        $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                        $curCol += $span;
                                    }
                                }
                            } else {
                                if (!$hasL2 && !$hasL1) {
                                    foreach ($grp['children'] as $child) {
                                        $span = $multiplier;
                                        $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                        $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                        if ($span > 1) {
                                            $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                        }
                                        $text = $child->item_ind . (!empty($child->item_en) ? "\n" . $child->item_en : '');
                                        $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                        $curCol += $span;
                                    }
                                }
                            }
                        } else {
                            if (!$hasL1 && !$hasL2) {
                                $span = $multiplier;
                                $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                                $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $span - 1);
                                if ($span > 1) {
                                    $sheet->mergeCells("{$fromColLetter}{$rowNum}:{$toColLetter}{$rowNum}");
                                }
                                $text = $grp['title_ind'] . (!empty($grp['title_en']) ? "\n" . $grp['title_en'] : '');
                                $sheet->setCellValue("{$fromColLetter}{$rowNum}", $text);
                                $curCol += $span;
                            }
                        }
                    }
                }
            }
            return $curCol;
        };

        if (!$hasKolom && $hasPeriode) {
            // KASUS 1: HANYA PERIODE (1 Baris)
            $curCol = 2;
            foreach ($itemsPeriode as $p) {
                $pLetter = Coordinate::stringFromColumnIndex($curCol);
                $pText   = $p->nama_periode_ind . (!empty($p->nama_periode_en) ? "\n" . $p->nama_periode_en : '');
                $sheet->setCellValue("{$pLetter}{$startHeaderRow}", $pText);
                $curCol++;
            }
        } elseif ($hasKolom && !$hasPeriode) {
            // KASUS 2: HANYA KARAKTERISTIK KOLOM
            foreach ($activeLevels as $idx => $lvl) {
                $r = $startHeaderRow + $idx;
                $fillLevelCellsExcel($lvl, $r, 2, 1);
            }
        } elseif ($hasKolom && $hasPeriode) {
            // KASUS 3: KEDUANYA ADA
            if ($posisi_hirarki === 'periode_baris') {
                // Baris 1..N: Tingkat-tingkat Kolom mulai Kolom C (Index 3)
                foreach ($activeLevels as $idx => $lvl) {
                    $r = $startHeaderRow + $idx;
                    $fillLevelCellsExcel($lvl, $r, 3, 1);
                }
            } elseif ($posisi_hirarki === 'periode_atas') {
                // Baris 1: Periode Waktu
                $rPer = $startHeaderRow;
                $curCol = 2;
                $colsPerPeriode = count($flatKolom);
                foreach ($itemsPeriode as $p) {
                    $fromColLetter = Coordinate::stringFromColumnIndex($curCol);
                    $toColLetter   = Coordinate::stringFromColumnIndex($curCol + $colsPerPeriode - 1);
                    if ($colsPerPeriode > 1) {
                        $sheet->mergeCells("{$fromColLetter}{$rPer}:{$toColLetter}{$rPer}");
                    }
                    $pText = $p->nama_periode_ind . (!empty($p->nama_periode_en) ? "\n" . $p->nama_periode_en : '');
                    $sheet->setCellValue("{$fromColLetter}{$rPer}", $pText);
                    $curCol += $colsPerPeriode;
                }

                // Baris 2..N: Tingkat-tingkat Kolom di bawah setiap periode
                foreach ($activeLevels as $idx => $lvl) {
                    $r = $startHeaderRow + 1 + $idx;
                    $c = 2;
                    foreach ($itemsPeriode as $p) {
                        $c = $fillLevelCellsExcel($lvl, $r, $c, 1);
                    }
                }
            } else {
                // Baris 1..N: Tingkat-tingkat Kolom
                foreach ($activeLevels as $idx => $lvl) {
                    $r = $startHeaderRow + $idx;
                    $fillLevelCellsExcel($lvl, $r, 2, count($itemsPeriode));
                }

                // Baris Terbawah: Periode Waktu di bawah setiap kolom daun
                $rPer = $startHeaderRow + count($activeLevels);
                $c = 2;
                foreach ($flatKolom as $child) {
                    foreach ($itemsPeriode as $p) {
                        $pLetter = Coordinate::stringFromColumnIndex($c);
                        $pText   = $p->nama_periode_ind . (!empty($p->nama_periode_en) ? "\n" . $p->nama_periode_en : '');
                        $sheet->setCellValue("{$pLetter}{$rPer}", $pText);
                        $c++;
                    }
                }
            }
        }

        // Terapkan Styling Header Grid untuk Kolom Data s.d LastCol
        $startDataColLetter = Coordinate::stringFromColumnIndex($startDataColIndex);
        $sheet->getStyle("{$startDataColLetter}{$startHeaderRow}:{$lastColLetter}" . ($numRow - 1))->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => $fontColorWhite],
                'size'  => 9,
                'name'  => 'Arial'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $headerFillColor]
            ]
        ]);

        // =====================================================================
        // 8. RENDER BARIS NOMOR KOLOM BPS: (1), (2), (3) ...
        // =====================================================================
        $sheet->setCellValue("A{$numRow}", "(1)");
        for ($c = 2; $c <= $lastColIndex; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$colLetter}{$numRow}", "(" . $c . ")");
        }

        $sheet->getStyle("A{$numRow}:{$lastColLetter}{$numRow}")->applyFromArray([
            'font' => [
                'bold'   => false,
                'italic' => true,
                'color'  => ['rgb' => $fontColorWhite],
                'size'   => 8.5,
                'name'   => 'Arial'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $headerNumColor]
            ]
        ]);

        // Border Seluruh Header (Thin White Grid dalam, Black Outline luar)
        $sheet->getStyle("A{$startHeaderRow}:{$lastColLetter}{$numRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FFFFFF']
                ],
                'outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '9A3412']
                ]
            ]
        ]);

        // =====================================================================
        // 9. RENDER TBODY (BARIS DATA, KATEGORI, DAN SUBTOTAL / TOTAL)
        // =====================================================================
        $dataStartRow = $numRow + 1;
        $curRow = $dataStartRow;

        // Tentukan format desimal Excel
        $numFormat = '#,##0';
        if ($desimal > 0) {
            $numFormat .= '.' . str_repeat('0', $desimal);
        }

        $rowItemsMeta = [];

        // Hitung level maksimum pada seluruh kelompok baris
        $maxBarisGroupLevel = 0;
        foreach ($itemsBaris as $b) {
            $lvl = (int)($b->level_indentasi ?? 0);
            if ($lvl > $maxBarisGroupLevel) {
                $maxBarisGroupLevel = $lvl;
            }
        }

        $isParentBaris = function(int $idx, array $items) use ($maxBarisGroupLevel): bool {
            $cur = $items[$idx];
            $tipe = strtolower(trim($cur->tipe_baris ?? ''));

            // 1. Sesuai role badge di Master Dimensi
            if ($tipe === 'kategori') {
                return true;
            }
            if ($tipe === 'data' || $tipe === 'total' || $tipe === 'subtotal') {
                return false;
            }

            // 2. Fallback untuk data lawas jika belum terisi
            if ($maxBarisGroupLevel === 0) {
                return false;
            }

            $lowerNama = strtolower(trim($cur->nama_baris_ind ?? ($cur->item_ind ?? '')));
            if (
                $lowerNama === 'jumlah' || $lowerNama === 'total' || 
                strpos($lowerNama, 'jawa tengah') !== false || 
                strpos($lowerNama, 'produk domestik regional bruto') !== false || 
                $lowerNama === 'pdrb' || 
                strpos($lowerNama, 'gross regional domestic product') !== false || 
                $lowerNama === 'grdp'
            ) {
                if (strpos($lowerNama, 'curah hujan') === false && strpos($lowerNama, 'hari hujan') === false) {
                    return false;
                }
            }

            if ($lowerNama === 'kabupaten' || $lowerNama === 'kota') {
                return true;
            }

            return false;
        };

        if (!$hasBaris && $hasKolom && $hasPeriode) {
            // TABEL RUNTUN WAKTU MAKRO (TANPA BARIS): RENDER ITEM PERIODE SEBAGAI BARIS
            foreach ($itemsPeriode as $p) {
                $rowItemsMeta[] = [
                    'excel_row'  => $curRow,
                    'id_baris'   => 0,
                    'id_periode' => (int)$p->id,
                    'tipe'       => 'data',
                    'nama'       => $p->nama_periode_ind,
                ];

                // Tulis Kolom A (Label Periode/Tahun)
                $sheet->setCellValue("A{$curRow}", $p->nama_periode_ind);
                $sheet->getStyle("A{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A{$curRow}")->getFont()->setBold(true);

                // Styling baris data
                $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                    'font' => ['size' => 9.5, 'name' => 'Arial'],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E2E8F0']
                        ]
                    ]
                ]);

                for ($c = 2; $c <= $lastColIndex; $c++) {
                    $cLetter = Coordinate::stringFromColumnIndex($c);
                    $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()->setFormatCode($numFormat);
                    $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                }

                $curRow++;
            }
        } else {
            foreach ($itemsBaris as $bIdx => $baris) {
            $tipeBaris = strtolower(trim($baris->tipe_baris ?? ''));
            $namaBaris = $baris->nama_baris_ind ?? ($baris->item_ind ?? '');
            $lowerBaris = strtolower(trim($namaBaris));

            if (!$tipeBaris) {
                if (
                    $lowerBaris === 'jumlah' || $lowerBaris === 'total' || 
                    strpos($lowerBaris, 'jawa tengah') !== false || 
                    strpos($lowerBaris, 'produk domestik regional bruto') !== false || 
                    $lowerBaris === 'pdrb' || 
                    strpos($lowerBaris, 'gross regional domestic product') !== false || 
                    $lowerBaris === 'grdp'
                ) {
                    if (strpos($lowerBaris, 'curah hujan') === false && strpos($lowerBaris, 'hari hujan') === false) {
                        $tipeBaris = 'total';
                    }
                }
                if (!$tipeBaris) $tipeBaris = 'data';
            }

            $isParent = $isParentBaris($bIdx, $itemsBaris);
            if ($isParent) {
                $tipeBaris = 'kategori';
            }

            // Pengaturan Override Baris Total (Jawa Tengah vs Jumlah vs Sembunyikan vs Kustom)
            $cfgTotal = $indikator->konfigurasi_total ?? 'default';
            if ($tipeBaris === 'total') {
                if ($cfgTotal === 'none') {
                    continue; // Sembunyikan baris total dari template Excel
                }
                if ($cfgTotal === 'jawa_tengah') {
                    $namaBaris = 'Jawa Tengah';
                } elseif ($cfgTotal === 'jumlah') {
                    $namaBaris = 'Jumlah';
                } elseif ($cfgTotal === 'kustom' && !empty($indikator->label_total_kustom)) {
                    $namaBaris = $indikator->label_total_kustom;
                }
            }

            $totalTingkatBaris = $maxBarisGroupLevel + 1;
            $level     = (int)($baris->level_indentasi ?? 0);
            $isBold    = ((int)($baris->is_bold ?? 0) === 1 || $tipeBaris === 'total' || $tipeBaris === 'subtotal' || $tipeBaris === 'kategori' || ($totalTingkatBaris >= 2 && $level === 0));

            if ($posisi_hirarki === 'periode_baris') {
                if ($tipeBaris === 'kategori') {
                    $rowItemsMeta[] = [
                        'excel_row' => $curRow,
                        'id_baris'  => (int)$baris->id,
                        'id_periode'=> 0,
                        'tipe'      => 'kategori',
                        'nama'      => $namaBaris,
                    ];
                    $sheet->mergeCells("A{$curRow}:{$lastColLetter}{$curRow}");
                    $sheet->setCellValue("A{$curRow}", $namaBaris);
                    $sheet->getStyle("A{$curRow}")->getAlignment()->setIndent(max(0, $level * 2))->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A{$curRow}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9.5, 'name' => 'Arial'],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F1F5F9']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'CBD5E1']
                            ]
                        ]
                    ]);
                    $curRow++;
                } else {
                    $pCount = count($itemsPeriode);
                    foreach ($itemsPeriode as $pIdx => $p) {
                        $rowItemsMeta[] = [
                            'excel_row' => $curRow,
                            'id_baris'  => (int)$baris->id,
                            'id_periode'=> (int)$p->id,
                            'tipe'      => $tipeBaris,
                            'nama'      => $namaBaris . ' (' . $p->nama_periode_ind . ')',
                        ];

                        if ($pIdx === 0) {
                            $sheet->setCellValue("A{$curRow}", $namaBaris);
                            if ($pCount > 1) {
                                $sheet->mergeCells("A{$curRow}:A" . ($curRow + $pCount - 1));
                            }
                            $sheet->getStyle("A{$curRow}")->getAlignment()->setIndent(max(0, $level * 2))->setVertical(Alignment::VERTICAL_CENTER);
                            if ($isBold) {
                                $sheet->getStyle("A{$curRow}")->getFont()->setBold(true);
                            }
                        }

                        $sheet->setCellValue("B{$curRow}", $p->nama_periode_ind);
                        $sheet->getStyle("B{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                        if ($isBold) {
                            $sheet->getStyle("B{$curRow}")->getFont()->setBold(true);
                        }

                        if ($tipeBaris === 'total') {
                            $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                                'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC']
                                ],
                                'borders' => [
                                    'top' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color'       => ['rgb' => '475569']
                                    ],
                                    'bottom' => [
                                        'borderStyle' => Border::BORDER_DOUBLE,
                                        'color'       => ['rgb' => '0F172A']
                                    ],
                                    'inside' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color'       => ['rgb' => 'E2E8F0']
                                    ]
                                ]
                            ]);
                            for ($c = 3; $c <= $lastColIndex; $c++) {
                                $cLetter = Coordinate::stringFromColumnIndex($c);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()->setFormatCode($numFormat);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                            }
                        } elseif ($tipeBaris === 'subtotal') {
                            $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                                'font' => ['bold' => true, 'size' => 9.5, 'name' => 'Arial', 'color' => ['rgb' => '000000']],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F9A066']
                                ],
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color'       => ['rgb' => 'FFFFFF']
                                    ]
                                ]
                            ]);
                            for ($c = 3; $c <= $lastColIndex; $c++) {
                                $cLetter = Coordinate::stringFromColumnIndex($c);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()->setFormatCode($numFormat);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getFont()->setBold(true)->getColor()->setARGB('FF000000');
                            }
                        } else {
                            $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                                'font' => ['size' => 9.5, 'name' => 'Arial'],
                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' => Border::BORDER_THIN,
                                        'color'       => ['rgb' => 'E2E8F0']
                                    ]
                                ]
                            ]);
                            for ($c = 3; $c <= $lastColIndex; $c++) {
                                $cLetter = Coordinate::stringFromColumnIndex($c);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()->setFormatCode($numFormat);
                                $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
                            }
                        }
                        $curRow++;
                    }
                }
            } else {
                // Simpan metadata baris untuk hidden sheet
                $rowItemsMeta[] = [
                    'excel_row' => $curRow,
                    'id_baris'  => (int)$baris->id,
                    'tipe'      => $tipeBaris,
                    'nama'      => $namaBaris,
                ];

                // Tulis Kolom A (Label Baris)
                $sheet->setCellValue("A{$curRow}", $namaBaris);
                $sheet->getStyle("A{$curRow}")->getAlignment()->setIndent(max(0, $level * 2));
                $sheet->getStyle("A{$curRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                if ($isBold) {
                    $sheet->getStyle("A{$curRow}")->getFont()->setBold(true);
                }

                // Atur Style Baris Ini
                if ($tipeBaris === 'kategori') {
                    // Baris Kategori / Induk: Merge penuh seluruh kolom dan beri warna latar abu-abu lembut
                    $sheet->mergeCells("A{$curRow}:{$lastColLetter}{$curRow}");
                    $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9.5, 'name' => 'Arial'],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F1F5F9']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'CBD5E1']
                            ]
                        ]
                    ]);
                } elseif ($tipeBaris === 'total') {
                    // Baris Grand Total BPS: Tebal dengan Garis Bawah Ganda
                    $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8FAFC']
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '475569']
                            ],
                            'bottom' => [
                                'borderStyle' => Border::BORDER_DOUBLE,
                                'color'       => ['rgb' => '0F172A']
                            ],
                            'inside' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'E2E8F0']
                            ]
                        ]
                    ]);

                    // Sel data total: Format angka & alignment
                    for ($c = 2; $c <= $lastColIndex; $c++) {
                        $cLetter = Coordinate::stringFromColumnIndex($c);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()
                            ->setFormatCode($numFormat);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                } elseif ($tipeBaris === 'subtotal') {
                    // Baris Subtotal BPS: Peach Lembut (#F9A066), Font Bold Hitam, Border Putih (#FFFFFF)
                    $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9.5, 'name' => 'Arial', 'color' => ['rgb' => '000000']],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9A066']
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'FFFFFF']
                            ]
                        ]
                    ]);

                    // Sel data subtotal: Format angka & alignment & bold
                    for ($c = 2; $c <= $lastColIndex; $c++) {
                        $cLetter = Coordinate::stringFromColumnIndex($c);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()
                            ->setFormatCode($numFormat);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getFont()->setBold(true)->getColor()->setARGB('FF000000');
                    }
                } else {
                    // Baris Data Normal: Putih, Garis Halus
                    $sheet->getStyle("A{$curRow}:{$lastColLetter}{$curRow}")->applyFromArray([
                        'font' => ['size' => 9.5, 'name' => 'Arial'],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'E2E8F0']
                            ]
                        ]
                    ]);

                    // Format seluruh sel data angka (Kolom B s.d Akhir)
                    for ($c = 2; $c <= $lastColIndex; $c++) {
                        $cLetter = Coordinate::stringFromColumnIndex($c);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getNumberFormat()
                            ->setFormatCode($numFormat);
                        $sheet->getStyle("{$cLetter}{$curRow}")->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    }
                }

                $curRow++;
            }
        }
    }

        $dataEndRow = $curRow - 1;

        // =====================================================================
        // 10. RENDER FOOTNOTE (SUMBER DATA DARI INSTANSI, TANPA CATATAN)
        // =====================================================================
        $curRow++; // Spasi kosong
        $sheet->setCellValue("A{$curRow}", "Sumber/Source: " . $sumber);
        $sheet->getStyle("A{$curRow}")->applyFromArray([
            'font' => [
                'italic' => true,
                'size'   => 9,
                'color'  => ['rgb' => '475569'],
                'name'   => 'Arial'
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER
            ]
        ]);

        // =====================================================================
        // 11. ATUR LEBAR KOLOM (COLUMN WIDTHS)
        // =====================================================================
        // Kolom A: Lebar proporsional untuk nama wilayah/instansi
        $sheet->getColumnDimension('A')->setWidth(38);

        if ($posisi_hirarki === 'periode_baris') {
            $sheet->getColumnDimension('B')->setWidth(14);
            for ($c = 3; $c <= $lastColIndex; $c++) {
                $cLetter = Coordinate::stringFromColumnIndex($c);
                $sheet->getColumnDimension($cLetter)->setWidth(18);
            }
        } else {
            // Kolom Data (B s.d LastCol): Lebar ideal agar angka & header muat rapi
            for ($c = 2; $c <= $lastColIndex; $c++) {
                $cLetter = Coordinate::stringFromColumnIndex($c);
                $sheet->getColumnDimension($cLetter)->setWidth(18);
            }
        }

        // =====================================================================
        // 12. PROTEKSI LEMBAR KERJA (DINONAKTIFKAN)
        // Lembar kerja dibiarkan bebas tanpa proteksi agar OPD leluasa mengisi & menyalin data
        // =====================================================================
        $sheet->getProtection()->setSheet(false);

        // =====================================================================
        // 13. HIDDEN METADATA SHEET (__METADATA_DDA__)
        // Kunci Integritas untuk Parser & Upload di Sprint 5
        // =====================================================================
        $metaSheet = $spreadsheet->createSheet();
        $metaSheet->setTitle('__METADATA_DDA__');

        $metaSheet->setCellValue('A1', 'KEY');
        $metaSheet->setCellValue('B1', 'VALUE');

        $metaSheet->setCellValue('A2', 'VERSION');
        $metaSheet->setCellValue('B2', '1.0');

        $metaSheet->setCellValue('A3', 'ID_TAHUN_TABEL');
        $metaSheet->setCellValue('B3', $id_tahun_tabel);

        $metaSheet->setCellValue('A4', 'ID_INDIKATOR');
        $metaSheet->setCellValue('B4', $indikator->id);

        $metaSheet->setCellValue('A5', 'NO_TABEL');
        $metaSheet->setCellValue('B5', $tabel->no_tabel);

        $metaSheet->setCellValue('A6', 'TAHUN');
        $metaSheet->setCellValue('B6', $tabel->tahun);

        $metaSheet->setCellValue('A7', 'ID_KARAKTERISTIK_KELOMPOK');
        $metaSheet->setCellValue('B7', $id_karakteristik);

        $metaSheet->setCellValue('A8', 'ID_BARIS_KELOMPOK');
        $metaSheet->setCellValue('B8', $id_baris);

        $metaSheet->setCellValue('A9', 'ID_PERIODE_KELOMPOK');
        $metaSheet->setCellValue('B9', $id_periode);

        $dataStartCol = ($posisi_hirarki === 'periode_baris') ? 'C' : 'B';

        $metaSheet->setCellValue('A10', 'DATA_START_ROW');
        $metaSheet->setCellValue('B10', $dataStartRow);

        $metaSheet->setCellValue('A11', 'DATA_END_ROW');
        $metaSheet->setCellValue('B11', $dataEndRow);

        $metaSheet->setCellValue('A12', 'DATA_START_COL');
        $metaSheet->setCellValue('B12', $dataStartCol);

        $metaSheet->setCellValue('A13', 'DATA_END_COL');
        $metaSheet->setCellValue('B13', $lastColLetter);

        // Susun daftar leaf columns dengan mapping huruf kolom Excel
        $leafColumnsMeta = [];
        $colOffset = ($posisi_hirarki === 'periode_baris') ? 3 : 2;
        foreach ($leafColumns as $idx => $lCol) {
            $colLetter = Coordinate::stringFromColumnIndex($colOffset + $idx);
            $leafColumnsMeta[] = array_merge(['excel_col' => $colLetter], $lCol);
        }

        // Token Checksum Integritas
        $checksumPayload = $id_tahun_tabel . '|' . $indikator->id . '|' . $dataStartRow . '|' . $dataEndRow . '|' . count($leafColumns);
        $checksumHash = hash('sha256', $checksumPayload . '|BPS_JATENG_SECRET_SALT');

        $metaSheet->setCellValue('A14', 'CHECKSUM');
        $metaSheet->setCellValue('B14', $checksumHash);

        // JSON payload terstruktur untuk pembacaan instan di Sprint 5
        $metaPayload = [
            'id_tahun_tabel' => $id_tahun_tabel,
            'id_indikator'   => (int)$indikator->id,
            'tahun'          => (int)$tabel->tahun,
            'no_tabel'       => $tabel->no_tabel,
            'judul_ind'      => $tabel->judul_ind,
            'posisi_hirarki' => $posisi_hirarki,
            'tingkat_kolom'  => $tingkat_kolom,
            'data_start_row' => $dataStartRow,
            'data_end_row'   => $dataEndRow,
            'total_rows'     => !empty($itemsBaris) ? count($itemsBaris) : count($itemsPeriode),
            'total_cols'     => $totalLeafCols,
            'leaf_columns'   => $leafColumnsMeta,
            'row_items'      => $rowItemsMeta,
            'checksum'       => $checksumHash,
            'generated_at'   => date('Y-m-d H:i:s'),
        ];

        $metaSheet->setCellValue('A15', 'PAYLOAD_JSON');
        $metaSheet->setCellValue('B15', json_encode($metaPayload));

        // Sembunyikan lembar metadata secara mendalam (VERYHIDDEN)
        $metaSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

        // Pastikan sheet aktif saat dibuka adalah lembar "Data"
        $spreadsheet->setActiveSheetIndex(0);

        // Kunci kursor fokus (active cell) ke sel data pertama (misal: C9 atau B9) agar OPD bisa langsung mengetik
        $sheet->setSelectedCell($dataStartCol . $dataStartRow);

        // Bekukan panel agar header tabel & nama daerah di Kolom A (atau A & B) tetap terlihat saat scroll
        $sheet->freezePane($dataStartCol . $dataStartRow);

        return $spreadsheet;
    }

    /**
     * Download langsung berkas template Excel ke browser
     * 
     * @param int $id_tahun_tabel
     * @return void
     */
    public function download(int $id_tahun_tabel)
    {
        $spreadsheet = $this->generate($id_tahun_tabel);

        // Ambil info nama berkas
        $tabel = $this->db->table('t_tahun_tabel')
            ->select('t_tahun_tabel.tahun, t_tahun_tabel.no_tabel, m_list_tabel.judul_ind')
            ->join('m_list_tabel', 't_tahun_tabel.id_tabel = m_list_tabel.id', 'left')
            ->where('t_tahun_tabel.id', $id_tahun_tabel)
            ->get()->getRow();

        $rawTitle = ($tabel->no_tabel ? $tabel->no_tabel . ' ' : '') . ($tabel->judul_ind ?? 'Tabel_DDA');
        // Bersihkan karakter ilegal untuk nama file Windows/Linux
        $cleanTitle = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\r\\n]/', '_', $rawTitle);
        $cleanTitle = preg_replace('/\\s+/', '_', trim($cleanTitle));
        $filename   = 'Template_DDA_' . substr($cleanTitle, 0, 80) . '_' . ($tabel->tahun ?? date('Y')) . '.xlsx';

        // Set response header untuk streaming download .xlsx
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
