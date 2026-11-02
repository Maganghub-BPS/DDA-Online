<?php
/**
 * PARTIAL COMPONENT: TABLE MATRIX DINAMIS (table_matrix_dinamis.php)
 * Engine Renderer Tabel Dinamis Multi-Tahun BPS Jawa Tengah
 * Digunakan serentak oleh Admin (v_tabel_dinamis.php) dan Portal Publik (frontend/v_tabel_dinamis.php)
 */

$cfgSatuan              = $indikator->satuan ?? '';
$cfgDesimal             = (int)($indikator->desimal ?? -1);
$cfgKolomNomor          = $indikator->kolom_nomor ?? 'sembunyikan';
$hasNoCol               = ($cfgKolomNomor === 'tampilkan');
$cfgTampilanBaris       = $indikator->tampilan_baris ?? '1_kolom';
$isBarisDuaKolom        = ($cfgTampilanBaris === '2_kolom');
$cfgFormatNomorBaris    = $indikator->format_nomor_baris ?? 'default';

$groupByRegion          = !empty($config_tampilan['group_by_region']);
$prioritasKarakteristik = !empty($config_tampilan['prioritas_karakteristik']);
$portraitMode           = !empty($config_tampilan['portrait_mode']);
$portraitSplitCol       = !empty($config_tampilan['portrait_split_col']);
$groupTotalHorizontal   = !empty($config_tampilan['group_total_horizontal']);
$totalHorizontal        = !empty($config_tampilan['total_horizontal']);
$jenisTotalHorizontal   = $config_tampilan['jenis_total_horizontal'] ?? 'sum';
$onlyTotalHorizontal    = !empty($config_tampilan['only_total_horizontal']);
$totalVertikal          = !empty($config_tampilan['total_vertikal']);
$jenisTotalVertikal     = $config_tampilan['jenis_total_vertikal'] ?? 'default';
$labelTotalKustom       = $config_tampilan['label_total_kustom'] ?? ($indikator->label_total_kustom ?? '');

// Deteksi Mode Khusus Runtun Waktu di Baris Total (Sesuai Format Publikasi Cetak BPS)
$isRuntunWaktuTotal     = ($totalVertikal && $jenisTotalVertikal === 'runtun_waktu' && empty($config_tampilan['portrait_mode']));
$runtunYears = [];
if ($isRuntunWaktuTotal) {
    if (!empty($config_tampilan['tahun_runtun_waktu']) && is_array($config_tampilan['tahun_runtun_waktu'])) {
        $runtunYears = array_values(array_unique(array_filter(array_map('intval', $config_tampilan['tahun_runtun_waktu']))));
    }
    if (empty($runtunYears) && !empty($available_years) && count($available_years) > 1) {
        $runtunYears = $available_years;
    }
    if (empty($runtunYears)) {
        $baseY = !empty($selected_years) ? (int)$selected_years[0] : (int)($tabel->tahun ?? date('Y'));
        $runtunYears = [$baseY, $baseY - 1, $baseY - 2, $baseY - 3, $baseY - 4];
    }
    rsort($runtunYears);
}

$tipePeriode            = $indikator->tipe_periode ?? 'tahunan';
$isTahunan              = in_array($tipePeriode, ['tahunan', 'tahunan_jumlah', 'tahunan_rata', 'tahunan_jumlah_rata', 'tahun_ajaran'], true);
$isAkademik             = ($tipePeriode === 'tahun_ajaran');
$isTablePreview         = !empty($isPreview);

// Pastikan selected_years terisi minimal 1 tahun
if (empty($selected_years)) {
    $selected_years = [(int)($tabel->tahun ?? date('Y'))];
}
sort($selected_years);

// Format label tahun
$formatTahunLabel = function(int $y) use ($isAkademik): string {
    return $isAkademik ? "{$y}/" . ($y + 1) : (string)$y;
};

// Flatten kolom karakteristik
$rawKolom = !empty($structureKolom['flat_items']) ? $structureKolom['flat_items'] : (!empty($itemsKolom) ? $itemsKolom : []);
$hasRealKolom = !empty($rawKolom);
$flatKolom = $rawKolom;
if (empty($flatKolom)) {
    $flatKolom = [(object)['id' => 0, 'item_ind' => 'Nilai', 'item_en' => 'Value', 'grup_induk_ind' => '', 'grup_sub_ind' => '', 'is_standalone' => 0]];
}

// Deteksi apakah Master Karakteristik sudah memiliki kolom Jumlah/Total bawaan
$isBuiltinTotalCol = function($kCol): bool {
    $lbl = strtolower(trim($kCol->item_ind ?? ''));
    return in_array($lbl, ['jumlah', 'total', 'rata-rata', 'average'], true);
};

$hasBuiltinTotalInFlat = false;
$hasNonTotalInFlat = false;
$hasGrupInduk = false;
$hasGrupSub = false;

foreach ($flatKolom as $kc) {
    if ($isBuiltinTotalCol($kc)) {
        $hasBuiltinTotalInFlat = true;
    } else {
        $hasNonTotalInFlat = true;
    }
    if (!empty(trim($kc->grup_induk_ind ?? ''))) {
        $hasGrupInduk = true;
    }
    if (!empty(trim($kc->grup_sub_ind ?? ''))) {
        $hasGrupSub = true;
    }
}
$skipBuiltinTotalInSum = ($hasBuiltinTotalInFlat && $hasNonTotalInFlat);
$kDepth = $hasGrupSub ? 3 : ($hasGrupInduk ? 2 : 1);

// Deteksi jika terdapat kolom dengan notasi piping multi-level '||' atau struktur berkedalaman > 3
$hasPipeInFlat = false;
foreach ($flatKolom as $kc) {
    if (strpos($kc->item_ind ?? '', '||') !== false) {
        $hasPipeInFlat = true;
        break;
    }
}
$structureDepth = (int)($structureKolom['max_depth'] ?? 0);
if ($structureDepth > $kDepth) {
    $kDepth = $structureDepth;
}

// Resolusi tingkat kolom yang aktif sesuai $indikator->tingkat_kolom
$rawTingkat = trim((string)($indikator->tingkat_kolom ?? 'semua'));
$activeTingkat = [];
if (empty($rawTingkat) || $rawTingkat === 'semua') {
    for ($l = 1; $l <= $kDepth; $l++) $activeTingkat[] = $l;
} elseif ($rawTingkat === 'sub_bab') {
    $activeTingkat = ($kDepth >= 3) ? [2, 3] : [2];
} elseif ($rawTingkat === 'bab') {
    $activeTingkat = [1];
} else {
    $parts = array_filter(array_map('trim', explode(',', $rawTingkat)));
    foreach ($parts as $p) {
        $pInt = (int)$p;
        if ($pInt >= 1 && $pInt <= $kDepth) {
            $activeTingkat[] = $pInt;
        }
    }
    if (empty($activeTingkat)) {
        for ($l = 1; $l <= $kDepth; $l++) $activeTingkat[] = $l;
    }
}
sort($activeTingkat);

// Helper perakit baris header bertingkat untuk 1 blok $flatKolom (disesuaikan dengan tingkat aktif)
$buildKarakteristikHeaderRows = function(array $cols, bool $addSubtotalCol = false) use ($kDepth, $activeTingkat, $structureKolom, $hasPipeInFlat): array {
    $n = count($cols);

    // KASUS MULTI-LEVEL DARI STRUCTURE KOLOM (TRIE HIERARKI PENUH: LEVEL > 3 ATAU MEMILIKI NOTASI PIPING '||')
    if ($kDepth > 3 || $hasPipeInFlat) {
        $trieHeaderRows = !empty($structureKolom['header_rows']) ? $structureKolom['header_rows'] : [];
        if (empty($trieHeaderRows)) {
            $mTabel = new \App\Models\M_tabeldinamis();
            $built = $mTabel->buildColumnStructure($cols);
            $trieHeaderRows = $built['header_rows'] ?? [];
        }
        if (!empty($trieHeaderRows)) {
            $trieRows = array_values($trieHeaderRows);
            if ($addSubtotalCol && !empty($trieRows)) {
                $trieRows[0][] = [
                    'ind'     => 'Jumlah',
                    'en'      => 'Total',
                    'colspan' => 1,
                    'rowspan' => count($trieRows),
                    'is_sub'  => true,
                ];
            }
            return $trieRows;
        }
    }

    // KASUS 1 TINGKAT
    if ($kDepth === 1 || empty($activeTingkat)) {
        $r0 = [];
        foreach ($cols as $c) {
            $r0[] = [
                'ind'     => $c->item_ind ?? '',
                'en'      => $c->item_en ?? '',
                'colspan' => 1,
                'rowspan' => 1,
                'is_sub'  => false,
            ];
        }
        if ($addSubtotalCol) {
            $r0[] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 1, 'is_sub' => true];
        }
        return [$r0];
    }

    // KASUS 2 TINGKAT
    if ($kDepth === 2) {
        $hasL1 = in_array(1, $activeTingkat, true);
        $hasL2 = in_array(2, $activeTingkat, true);

        // Jika HANYA Tingkat 2 yang aktif (Tingkat 1 di-uncheck):
        // Render HANYA 1 baris daun (leaf items), baris grup induk dihilangkan
        if (!$hasL1 && $hasL2) {
            $rLeaf = [];
            foreach ($cols as $c) {
                $rLeaf[] = [
                    'ind'     => $c->item_ind ?? '',
                    'en'      => $c->item_en ?? '',
                    'colspan' => 1,
                    'rowspan' => 1,
                    'is_sub'  => false,
                ];
            }
            if ($addSubtotalCol) {
                $rLeaf[] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 1, 'is_sub' => true];
            }
            return [$rLeaf];
        }

        // Jika HANYA Tingkat 1 yang aktif (Tingkat 2 di-uncheck):
        // Render HANYA 1 baris grup induk
        if ($hasL1 && !$hasL2) {
            $rG1 = [];
            $i = 0;
            while ($i < $n) {
                $c = $cols[$i];
                $g1 = trim($c->grup_induk_ind ?? '');
                $g1En = trim($c->grup_induk_en ?? '');
                if ($g1 === '' || !empty($c->is_standalone)) {
                    $rG1[] = [
                        'ind'     => $c->item_ind ?? '',
                        'en'      => $c->item_en ?? '',
                        'colspan' => 1,
                        'rowspan' => 1,
                        'is_sub'  => false,
                    ];
                    $i++;
                } else {
                    $span = 1;
                    while (($i + $span) < $n && trim($cols[$i + $span]->grup_induk_ind ?? '') === $g1 && empty($cols[$i + $span]->is_standalone)) {
                        $span++;
                    }
                    $rG1[] = [
                        'ind'     => $g1,
                        'en'      => $g1En,
                        'colspan' => 1,
                        'rowspan' => 1,
                        'is_sub'  => false,
                    ];
                    $i += $span;
                }
            }
            if ($addSubtotalCol) {
                $rG1[] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 1, 'is_sub' => true];
            }
            return [$rG1];
        }

        // Jika kedua tingkat aktif (L1 dan L2)
        $rows = [[], []];
        $i = 0;
        while ($i < $n) {
            $c = $cols[$i];
            $g1 = trim($c->grup_induk_ind ?? '');
            $g1En = trim($c->grup_induk_en ?? '');
            if ($g1 === '' || !empty($c->is_standalone)) {
                $rows[0][] = [
                    'ind'     => $c->item_ind ?? '',
                    'en'      => $c->item_en ?? '',
                    'colspan' => 1,
                    'rowspan' => 2,
                    'is_sub'  => false,
                ];
                $i++;
            } else {
                $span = 1;
                while (($i + $span) < $n && trim($cols[$i + $span]->grup_induk_ind ?? '') === $g1 && empty($cols[$i + $span]->is_standalone)) {
                    $span++;
                }
                $rows[0][] = [
                    'ind'     => $g1,
                    'en'      => $g1En,
                    'colspan' => $span,
                    'rowspan' => 1,
                    'is_sub'  => false,
                ];
                for ($j = 0; $j < $span; $j++) {
                    $leaf = $cols[$i + $j];
                    $rows[1][] = [
                        'ind'     => $leaf->item_ind ?? '',
                        'en'      => $leaf->item_en ?? '',
                        'colspan' => 1,
                        'rowspan' => 1,
                        'is_sub'  => false,
                    ];
                }
                $i += $span;
            }
        }
        if ($addSubtotalCol) {
            $rows[0][] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 2, 'is_sub' => true];
        }
        return $rows;
    }

    // KASUS 3 TINGKAT
    if ($kDepth === 3) {
        $hasL1 = in_array(1, $activeTingkat, true);
        $hasL2 = in_array(2, $activeTingkat, true);
        $hasL3 = in_array(3, $activeTingkat, true);

        // Jika hanya Tingkat 3 (hanya leaf items):
        if (!$hasL1 && !$hasL2 && $hasL3) {
            $rLeaf = [];
            foreach ($cols as $c) {
                $rLeaf[] = [
                    'ind'     => $c->item_ind ?? '',
                    'en'      => $c->item_en ?? '',
                    'colspan' => 1,
                    'rowspan' => 1,
                    'is_sub'  => false,
                ];
            }
            if ($addSubtotalCol) {
                $rLeaf[] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 1, 'is_sub' => true];
            }
            return [$rLeaf];
        }

        // Jika Tingkat 2 dan Tingkat 3 aktif (Tingkat 1 di-uncheck):
        if (!$hasL1 && $hasL2 && $hasL3) {
            $rows = [[], []];
            $i = 0;
            while ($i < $n) {
                $subC = $cols[$i];
                $g2 = trim($subC->grup_sub_ind ?? '');
                $g2En = trim($subC->grup_sub_en ?? '');
                if ($g2 === '') {
                    $rows[0][] = [
                        'ind'     => $subC->item_ind ?? '',
                        'en'      => $subC->item_en ?? '',
                        'colspan' => 1,
                        'rowspan' => 2,
                        'is_sub'  => false,
                    ];
                    $i++;
                } else {
                    $span2 = 1;
                    while (($i + $span2) < $n && trim($cols[$i + $span2]->grup_sub_ind ?? '') === $g2) {
                        $span2++;
                    }
                    $rows[0][] = [
                        'ind'     => $g2,
                        'en'      => $g2En,
                        'colspan' => $span2,
                        'rowspan' => 1,
                        'is_sub'  => false,
                    ];
                    for ($m = 0; $m < $span2; $m++) {
                        $leaf = $cols[$i + $m];
                        $rows[1][] = [
                            'ind'     => $leaf->item_ind ?? '',
                            'en'      => $leaf->item_en ?? '',
                            'colspan' => 1,
                            'rowspan' => 1,
                            'is_sub'  => false,
                        ];
                    }
                    $i += $span2;
                }
            }
            if ($addSubtotalCol) {
                $rows[0][] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 2, 'is_sub' => true];
            }
            return $rows;
        }

        // Standar 3 Tingkat lengkap:
        $rows = [[], [], []];
        $i = 0;
        while ($i < $n) {
            $c = $cols[$i];
            $g1 = trim($c->grup_induk_ind ?? '');
            $g1En = trim($c->grup_induk_en ?? '');
            if ($g1 === '' || !empty($c->is_standalone)) {
                $rows[0][] = [
                    'ind'     => $c->item_ind ?? '',
                    'en'      => $c->item_en ?? '',
                    'colspan' => 1,
                    'rowspan' => 3,
                    'is_sub'  => false,
                ];
                $i++;
            } else {
                $span1 = 1;
                while (($i + $span1) < $n && trim($cols[$i + $span1]->grup_induk_ind ?? '') === $g1 && empty($cols[$i + $span1]->is_standalone)) {
                    $span1++;
                }
                $rows[0][] = [
                    'ind'     => $g1,
                    'en'      => $g1En,
                    'colspan' => $span1,
                    'rowspan' => 1,
                    'is_sub'  => false,
                ];
                $k = $i;
                $end1 = $i + $span1;
                while ($k < $end1) {
                    $subC = $cols[$k];
                    $g2 = trim($subC->grup_sub_ind ?? '');
                    $g2En = trim($subC->grup_sub_en ?? '');
                    if ($g2 === '') {
                        $rows[1][] = [
                            'ind'     => $subC->item_ind ?? '',
                            'en'      => $subC->item_en ?? '',
                            'colspan' => 1,
                            'rowspan' => 2,
                            'is_sub'  => false,
                        ];
                        $k++;
                    } else {
                        $span2 = 1;
                        while (($k + $span2) < $end1 && trim($cols[$k + $span2]->grup_sub_ind ?? '') === $g2) {
                            $span2++;
                        }
                        $rows[1][] = [
                            'ind'     => $g2,
                            'en'      => $g2En,
                            'colspan' => $span2,
                            'rowspan' => 1,
                            'is_sub'  => false,
                        ];
                        for ($m = 0; $m < $span2; $m++) {
                            $leaf = $cols[$k + $m];
                            $rows[2][] = [
                                'ind'     => $leaf->item_ind ?? '',
                                'en'      => $leaf->item_en ?? '',
                                'colspan' => 1,
                                'rowspan' => 1,
                                'is_sub'  => false,
                            ];
                        }
                        $k += $span2;
                    }
                }
                $i += $span1;
            }
        }
        if ($addSubtotalCol) {
            $rows[0][] = ['ind' => 'Jumlah', 'en' => 'Total', 'colspan' => 1, 'rowspan' => 3, 'is_sub' => true];
        }
        return $rows;
    }

    return [];
};

// Sub-periode untuk periode non-tahunan (Bulanan, Triwulanan, Maret-September, dll)
$subPeriodeList = [];
if (!$isTahunan && !empty($itemsPeriode)) {
    $subPeriodeList = $itemsPeriode;
} else {
    $subPeriodeList = [(object)['id' => 0, 'nama_periode_ind' => '', 'nama_periode_en' => '']];
}

// Deteksi Tabel Runtun Waktu Makro Tanpa Baris Wilayah (id_baris_kelompok = NULL)
$isMacroTimeSeries = empty($itemsBaris);
if ($isMacroTimeSeries) {
    $portraitMode = true;
    $portraitSplitCol = (!$isTahunan && !empty($itemsPeriode));
    $itemsBaris = [
        (object)[
            'id'              => 0,
            'nama_baris_ind'  => '',
            'nama_baris_en'   => '',
            'tipe_baris'      => 'data',
            'level_indentasi' => 0,
            'is_bold'         => 0,
        ]
    ];
}

// Susun daftar kombinasi (Tahun x Sub-Periode) untuk Portrait Mode
$portraitPeriods = [];
foreach ($selected_years as $yr) {
    $yrLabel = $formatTahunLabel($yr);
    foreach ($subPeriodeList as $sp) {
        $pId   = (int)($sp->id ?? 0);
        $pName = trim($sp->nama_periode_ind ?? '');
        $portraitPeriods[] = [
            'tahun'       => $yr,
            'tahun_label' => $yrLabel,
            'p_id'        => $pId,
            'p_label'     => $pName,
            'full_label'  => ($pName !== '' ? "{$yrLabel} - {$pName}" : $yrLabel),
        ];
    }
}

// Helper pemformat nomor baris (format_nomor_baris)
$formatRowLabel = function(string $rawLabel, int $seqNo, bool $isTotal = false) use ($cfgFormatNomorBaris): string {
    if ($isTotal || $rawLabel === '') return $rawLabel;
    if ($cfgFormatNomorBaris === 'default') return $rawLabel;

    $cleanName = preg_replace('/^(?:[IVXLCDM]+\.|[0-9]{1,4}[\.\)]?)\s+/i', '', trim($rawLabel));
    if ($cfgFormatNomorBaris === 'tanpa_nomor') {
        return $cleanName;
    }
    if ($cfgFormatNomorBaris === 'angka_2digit') {
        return str_pad((string)$seqNo, 2, '0', STR_PAD_LEFT) . '. ' . $cleanName;
    }
    if ($cfgFormatNomorBaris === 'angka_1digit') {
        return $seqNo . '. ' . $cleanName;
    }
    if ($cfgFormatNomorBaris === 'romawi_rupiah') {
        return str_pad((string)$seqNo, 2, '0', STR_PAD_LEFT) . '. ' . $cleanName;
    }
    return $rawLabel;
};

// =========================================================================
// 1. SUSUN DAFTAR LEAF COLUMNS (KOLOM DATA TERENDAH)
// =========================================================================
$leafColumns = [];
$addYearGroupSubtotal = ($groupTotalHorizontal && count($flatKolom) > 1 && !$hasBuiltinTotalInFlat);

if ($portraitMode) {
    // Mode Portrait: Kolom atas adalah Karakteristik
    foreach ($flatKolom as $kCol) {
        $leafColumns[] = [
            'col_key'          => 'k_' . $kCol->id,
            'tahun'            => null,
            'tahun_label'      => '',
            'item_id'          => (int)$kCol->id,
            'item_ind'         => $kCol->item_ind ?? 'Nilai',
            'item_en'          => $kCol->item_en ?? ($kCol->item_ind ?? 'Value'),
            'periode_id'       => 0,
            'periode_ind'      => '',
            'periode_en'       => '',
            'is_builtin_total' => $isBuiltinTotalCol($kCol),
            'is_group_total'   => false,
            'group_key'        => 'portrait_all',
            'is_grand_total'   => false,
            'is_hidden'        => $onlyTotalHorizontal,
        ];
    }
} elseif ($prioritasKarakteristik) {
    // Mode Karakteristik Â» Tahun
    foreach ($flatKolom as $kCol) {
        $kId  = (int)$kCol->id;
        $kInd = $kCol->item_ind ?? '';
        $kEn  = $kCol->item_en ?? $kInd;
        $isBTotal = $isBuiltinTotalCol($kCol);

        foreach ($selected_years as $yr) {
            $yrLabel = $formatTahunLabel($yr);
            foreach ($subPeriodeList as $sp) {
                $pId = (int)($sp->id ?? 0);
                $leafColumns[] = [
                    'col_key'          => "{$kId}_{$yr}_{$pId}",
                    'tahun'            => $yr,
                    'tahun_label'      => $yrLabel,
                    'item_id'          => $kId,
                    'item_ind'         => $kInd,
                    'item_en'          => $kEn,
                    'periode_id'       => $pId,
                    'periode_ind'      => $sp->nama_periode_ind ?? '',
                    'periode_en'       => $sp->nama_periode_en ?? '',
                    'is_builtin_total' => $isBTotal,
                    'is_group_total'   => false,
                    'group_key'        => 'k_' . $kId,
                    'is_grand_total'   => false,
                    'is_hidden'        => $onlyTotalHorizontal,
                ];
            }
        }

        if ($groupTotalHorizontal && count($selected_years) > 1) {
            $leafColumns[] = [
                'col_key'          => "subtotal_k_{$kId}",
                'tahun'            => null,
                'tahun_label'      => '',
                'item_id'          => $kId,
                'item_ind'         => 'Jumlah',
                'item_en'          => 'Total',
                'periode_id'       => 0,
                'periode_ind'      => '',
                'periode_en'       => '',
                'is_builtin_total' => false,
                'is_group_total'   => true,
                'group_key'        => 'k_' . $kId,
                'is_grand_total'   => false,
                'is_hidden'        => $onlyTotalHorizontal,
            ];
        }
    }
} else {
    // Mode Standar (Default): Tahun Â» Karakteristik
    foreach ($selected_years as $yr) {
        $yrLabel = $formatTahunLabel($yr);

        foreach ($subPeriodeList as $sp) {
            $pId  = (int)($sp->id ?? 0);
            $pInd = $sp->nama_periode_ind ?? '';
            $pEn  = $sp->nama_periode_en ?? '';
            $gKey = "y_{$yr}" . ($pId ? "_{$pId}" : '');

            foreach ($flatKolom as $kCol) {
                $kId  = (int)$kCol->id;
                $kInd = $kCol->item_ind ?? '';
                $kEn  = $kCol->item_en ?? $kInd;

                $leafColumns[] = [
                    'col_key'          => "{$yr}_{$pId}_{$kId}",
                    'tahun'            => $yr,
                    'tahun_label'      => $yrLabel,
                    'item_id'          => $kId,
                    'item_ind'         => $kInd,
                    'item_en'          => $kEn,
                    'periode_id'       => $pId,
                    'periode_ind'      => $pInd,
                    'periode_en'       => $pEn,
                    'is_builtin_total' => $isBuiltinTotalCol($kCol),
                    'is_group_total'   => false,
                    'group_key'        => $gKey,
                    'is_grand_total'   => false,
                    'is_hidden'        => $onlyTotalHorizontal,
                ];
            }

            if ($addYearGroupSubtotal) {
                $leafColumns[] = [
                    'col_key'          => "subtotal_y_{$yr}_{$pId}",
                    'tahun'            => $yr,
                    'tahun_label'      => $yrLabel,
                    'item_id'          => 0,
                    'item_ind'         => 'Jumlah',
                    'item_en'          => 'Total',
                    'periode_id'       => $pId,
                    'periode_ind'      => $pInd,
                    'periode_en'       => $pEn,
                    'is_builtin_total' => false,
                    'is_group_total'   => true,
                    'group_key'        => $gKey,
                    'is_grand_total'   => false,
                    'is_hidden'        => $onlyTotalHorizontal,
                ];
            }
        }
    }
}

// Tambahkan Kolom Total Horizontal (Baris) di paling kanan jika aktif
// Cegah duplikasi jika hanya 1 tahun terpilih, bukan portrait, dan sudah ada kolom Jumlah bawaan
$skipGrandHorizontal = (!$portraitMode && count($selected_years) === 1 && count($subPeriodeList) === 1 && $hasBuiltinTotalInFlat && $jenisTotalHorizontal === 'sum' && !$onlyTotalHorizontal);
if ($totalHorizontal && !$skipGrandHorizontal) {
    $leafColumns[] = [
        'col_key'          => 'grand_horizontal_total',
        'tahun'            => null,
        'tahun_label'      => '',
        'item_id'          => 0,
        'item_ind'         => ($jenisTotalHorizontal === 'avg' ? 'Rata-rata' : 'Jumlah'),
        'item_en'          => ($jenisTotalHorizontal === 'avg' ? 'Average' : 'Total'),
        'periode_id'       => 0,
        'periode_ind'      => '',
        'periode_en'       => '',
        'is_builtin_total' => false,
        'is_group_total'   => false,
        'group_key'        => 'grand_total',
        'is_grand_total'   => true,
        'is_hidden'        => false,
    ];
}

// Hitung kolom leaf yang terlihat
$visibleLeafColumns = array_values(array_filter($leafColumns, function($c) {
    return empty($c['is_hidden']);
}));

// Judul Baris Utama
if ($isMacroTimeSeries) {
    $cleanTitleBarisInd = !empty($kelompokPeriode->judul_periode_ind) ? $kelompokPeriode->judul_periode_ind : 'Tahun';
    $titleBarisEn       = !empty($kelompokPeriode->judul_periode_en) ? $kelompokPeriode->judul_periode_en : 'Year';
} else {
    $titleBarisInd      = !empty($kelompokBaris->judul_baris_ind) ? $kelompokBaris->judul_baris_ind : ($kelompokBaris->nama_kelompok_ind ?? 'Kabupaten/Kota');
    $titleBarisEn       = !empty($kelompokBaris->judul_baris_en) ? $kelompokBaris->judul_baris_en : ($kelompokBaris->nama_kelompok_en ?? '');
    $cleanTitleBarisInd = preg_replace('/^\d+\s+/', '', $titleBarisInd);
}

$cleanTitleSubInd = $kelompokBaris->judul_sub_baris_ind ?? 'Rincian';
$cleanTitleSubEn  = $kelompokBaris->judul_sub_baris_en ?? 'Details';

// Pre-build Karakteristik header rows
$kHeaderRowsPortrait = $buildKarakteristikHeaderRows($flatKolom, false);
$kHeaderRowsPerPeriod = $hasRealKolom ? $buildKarakteristikHeaderRows($flatKolom, $addYearGroupSubtotal) : [];
?>

<div class="table-responsive-dda">
    <table class="<?= $isTablePreview ? 'table table-bordered table-sm w-100' : 'main-table dda-table-item w-100'; ?>" id="<?= esc($matrix_dom_id ?? 'table-dinamis-matrix'); ?>" style="border-collapse: collapse; margin-bottom: 0;">
        <thead <?= $isTablePreview ? 'class="bg-white text-dark"' : 'style="background: #FF6D1F; color: #ffffff;"'; ?>>
            <?php if ($portraitMode): 
                $totalPortraitHeaderRows = $onlyTotalHorizontal ? 1 : count($kHeaderRowsPortrait);
            ?>
                <!-- ==================== HEADER MODE PORTRAIT ==================== -->
                <?php for ($rIdx = 0; $rIdx < $totalPortraitHeaderRows; $rIdx++): ?>
                    <tr>
                        <?php if ($rIdx === 0): ?>
                            <?php if ($hasNoCol): ?>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-nomor text-center" style="width: 45px; border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 6px;">
                                    <div>No.</div>
                                </th>
                            <?php endif; ?>

                            <?php if ($isMacroTimeSeries && $portraitSplitCol): ?>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 110px;">
                                    <div>Tahun</div>
                                    <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;">Year</div>
                                </th>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-periode" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 130px;">
                                    <div>Periode</div>
                                    <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;">Period</div>
                                </th>
                            <?php elseif ($portraitSplitCol): ?>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 170px;">
                                    <div><?= esc($cleanTitleBarisInd); ?></div>
                                    <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                                </th>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-periode" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 110px;">
                                    <div>Tahun / Periode</div>
                                    <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;">Year / Period</div>
                                </th>
                            <?php else: ?>
                                <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 180px;">
                                    <div><?= esc($cleanTitleBarisInd); ?></div>
                                    <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                                </th>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!$onlyTotalHorizontal): ?>
                            <?php foreach ($kHeaderRowsPortrait[$rIdx] as $hCell): ?>
                                <th colspan="<?= $hCell['colspan']; ?>" rowspan="<?= $hCell['rowspan']; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 95px;">
                                    <div><?= esc($hCell['ind']); ?></div>
                                    <?php if (!empty($hCell['en'])): ?>
                                        <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($hCell['en']); ?></div>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($rIdx === 0 && $totalHorizontal && !$skipGrandHorizontal): ?>
                            <th rowspan="<?= $totalPortraitHeaderRows; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 14px; background: #FF6D1F; min-width: 110px;">
                                <div class="fw-bold"><?= ($jenisTotalHorizontal === 'avg' ? 'Rata-rata' : 'Jumlah'); ?></div>
                                <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= ($jenisTotalHorizontal === 'avg' ? 'Average' : 'Total'); ?></div>
                            </th>
                        <?php endif; ?>
                    </tr>
                <?php endfor; ?>

            <?php elseif ($isTahunan && !$prioritasKarakteristik): 
                $totalLeftRowSpan = 1 + (($onlyTotalHorizontal || !$hasRealKolom) ? 0 : count($kHeaderRowsPerPeriod));
                $kColCountPerYear = $hasRealKolom ? (count($flatKolom) + ($addYearGroupSubtotal ? 1 : 0)) : 1;
            ?>
                <!-- ==================== HEADER STANDAR: TAHUN Â» KARAKTERISTIK (MULTI-LEVEL) ==================== -->
                <tr>
                    <?php if ($hasNoCol): ?>
                        <th rowspan="<?= $totalLeftRowSpan; ?>" class="col-nomor text-center" style="width: 45px; border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 6px;">
                            <div>No.</div>
                        </th>
                    <?php endif; ?>

                    <?php if ($isBarisDuaKolom): ?>
                        <th rowspan="<?= $totalLeftRowSpan; ?>" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 160px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                        <th rowspan="<?= $totalLeftRowSpan; ?>" class="col-rincian" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 140px;">
                            <div><?= esc($cleanTitleSubInd); ?></div>
                            <?php if ($cleanTitleSubEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($cleanTitleSubEn); ?></div><?php endif; ?>
                        </th>
                    <?php else: ?>
                        <th rowspan="<?= $totalLeftRowSpan; ?>" <?= ($isRuntunWaktuTotal ? 'colspan="2"' : ''); ?> class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 200px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                    <?php endif; ?>

                    <?php if (!$onlyTotalHorizontal): ?>
                        <?php foreach ($selected_years as $yr): 
                            $yrLabel = $formatTahunLabel($yr);
                        ?>
                            <th colspan="<?= $kColCountPerYear; ?>" rowspan="<?= ($hasRealKolom ? 1 : $totalLeftRowSpan); ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px;">
                                <div class="fw-bold" style="font-size: 13px;"><?= $hasRealKolom ? ('Tahun ' . esc($yrLabel)) : esc($yrLabel); ?></div>
                            </th>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($totalHorizontal && !$skipGrandHorizontal): ?>
                        <th rowspan="<?= $totalLeftRowSpan; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 14px; background: #FF6D1F; min-width: 110px;">
                            <div class="fw-bold"><?= ($jenisTotalHorizontal === 'avg' ? 'Rata-rata' : 'Jumlah'); ?></div>
                            <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= ($jenisTotalHorizontal === 'avg' ? 'Average' : 'Total'); ?></div>
                        </th>
                    <?php endif; ?>
                </tr>

                <!-- Baris Header Karakteristik (1 s.d. 3 Tingkat) -->
                <?php if (!$onlyTotalHorizontal && $hasRealKolom && !empty($kHeaderRowsPerPeriod)): ?>
                    <?php foreach ($kHeaderRowsPerPeriod as $kRowCells): ?>
                        <tr>
                            <?php foreach ($selected_years as $yr): ?>
                                <?php foreach ($kRowCells as $hCell): ?>
                                    <th colspan="<?= $hCell['colspan']; ?>" rowspan="<?= $hCell['rowspan']; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 6px 10px; font-size: 12px; min-width: 95px; <?= (!empty($hCell['is_sub']) ? 'background: #0f355c;' : ''); ?>">
                                        <div><?= esc($hCell['ind']); ?></div>
                                        <?php if (!empty($hCell['en'])): ?>
                                            <div class="header-subtitle" style="font-size: 10.5px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($hCell['en']); ?></div>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php elseif ($isTahunan && $prioritasKarakteristik): ?>
                <!-- ==================== HEADER 2 BARIS: KARAKTERISTIK Â» TAHUN ==================== -->
                <tr>
                    <?php if ($hasNoCol): ?>
                        <th rowspan="2" class="col-nomor text-center" style="width: 45px; border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 6px;">
                            <div>No.</div>
                        </th>
                    <?php endif; ?>

                    <?php if ($isBarisDuaKolom): ?>
                        <th rowspan="2" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 160px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                        <th rowspan="2" class="col-rincian" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 140px;">
                            <div><?= esc($cleanTitleSubInd); ?></div>
                            <?php if ($cleanTitleSubEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($cleanTitleSubEn); ?></div><?php endif; ?>
                        </th>
                    <?php else: ?>
                        <th rowspan="2" <?= ($isRuntunWaktuTotal ? 'colspan="2"' : ''); ?> class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 200px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                    <?php endif; ?>

                    <?php if (!$onlyTotalHorizontal && $hasRealKolom && !empty($kHeaderRowsPerPeriod)): ?>
                        <?php 
                        $yrCountPerK = count($selected_years) + ($groupTotalHorizontal && count($selected_years) > 1 ? 1 : 0);
                        foreach ($flatKolom as $kCol): 
                        ?>
                            <th colspan="<?= $yrCountPerK; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px;">
                                <div class="fw-bold" style="font-size: 13px;"><?= esc($kCol->item_ind); ?></div>
                                <?php if (!empty($kCol->item_en)): ?>
                                    <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($kCol->item_en); ?></div>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($totalHorizontal && !$skipGrandHorizontal): ?>
                        <th rowspan="2" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 14px; background: #FF6D1F; min-width: 110px;">
                            <div class="fw-bold"><?= ($jenisTotalHorizontal === 'avg' ? 'Rata-rata' : 'Jumlah'); ?></div>
                            <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= ($jenisTotalHorizontal === 'avg' ? 'Average' : 'Total'); ?></div>
                        </th>
                    <?php endif; ?>
                </tr>

                <!-- Baris Header 2: Tahun -->
                <?php if (!$onlyTotalHorizontal && $hasRealKolom && !empty($kHeaderRowsPerPeriod)): ?>
                    <tr>
                        <?php foreach ($flatKolom as $kCol): ?>
                            <?php foreach ($selected_years as $yr): ?>
                                <th class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 6px 10px; font-size: 12px; min-width: 95px;">
                                    <div><?= esc($formatTahunLabel($yr)); ?></div>
                                </th>
                            <?php endforeach; ?>
                            <?php if ($groupTotalHorizontal && count($selected_years) > 1): ?>
                                <th class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 6px 10px; font-size: 12px; background: #0f355c; min-width: 95px;">
                                    <div>Jumlah</div>
                                    <div class="header-subtitle" style="font-size: 10.5px; font-weight: normal; font-style: italic; opacity: 0.85;">Total</div>
                                </th>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>

            <?php else: 
                $totalSubLeftRowSpan = 2 + ($onlyTotalHorizontal ? 0 : count($kHeaderRowsPerPeriod));
                $kColsPerSubPeriode = count($flatKolom) + ($addYearGroupSubtotal ? 1 : 0);
                $colsPerYear = count($subPeriodeList) * $kColsPerSubPeriode;
            ?>
                <!-- ==================== HEADER SUB-TAHUNAN (TAHUN Â» SUB-PERIODE Â» KARAKTERISTIK) ==================== -->
                <tr>
                    <?php if ($hasNoCol): ?>
                        <th rowspan="<?= $totalSubLeftRowSpan; ?>" class="col-nomor text-center" style="width: 45px; border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 6px;">
                            <div>No.</div>
                        </th>
                    <?php endif; ?>

                    <?php if ($isBarisDuaKolom): ?>
                        <th rowspan="<?= $totalSubLeftRowSpan; ?>" class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 160px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                        <th rowspan="<?= $totalSubLeftRowSpan; ?>" class="col-rincian" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 140px;">
                            <div><?= esc($cleanTitleSubInd); ?></div>
                            <?php if ($cleanTitleSubEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($cleanTitleSubEn); ?></div><?php endif; ?>
                        </th>
                    <?php else: ?>
                        <th rowspan="<?= $totalSubLeftRowSpan; ?>" <?= ($isRuntunWaktuTotal ? 'colspan="2"' : ''); ?> class="col-wilayah" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px; min-width: 200px;">
                            <div><?= esc($cleanTitleBarisInd); ?></div>
                            <?php if ($titleBarisEn): ?><div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($titleBarisEn); ?></div><?php endif; ?>
                        </th>
                    <?php endif; ?>

                    <?php if (!$onlyTotalHorizontal): ?>
                        <?php foreach ($selected_years as $yr): ?>
                            <th colspan="<?= $colsPerYear; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 12px;">
                                <div class="fw-bold" style="font-size: 13px;">Tahun <?= esc($formatTahunLabel($yr)); ?></div>
                            </th>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($totalHorizontal && !$skipGrandHorizontal): ?>
                        <th rowspan="<?= $totalSubLeftRowSpan; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 8px 14px; background: #FF6D1F; min-width: 110px;">
                            <div class="fw-bold"><?= ($jenisTotalHorizontal === 'avg' ? 'Rata-rata' : 'Jumlah'); ?></div>
                            <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= ($jenisTotalHorizontal === 'avg' ? 'Average' : 'Total'); ?></div>
                        </th>
                    <?php endif; ?>
                </tr>

                <?php if (!$onlyTotalHorizontal && $hasRealKolom && !empty($kHeaderRowsPerPeriod)): ?>
                    <!-- Baris Header 2: Sub-Periode -->
                    <tr>
                        <?php foreach ($selected_years as $yr): ?>
                            <?php foreach ($subPeriodeList as $sp): ?>
                                <th colspan="<?= $kColsPerSubPeriode; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 6px 10px; font-size: 12px;">
                                    <div><?= esc($sp->nama_periode_ind); ?></div>
                                    <?php if (!empty($sp->nama_periode_en)): ?>
                                        <div class="header-subtitle" style="font-size: 10.5px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($sp->nama_periode_en); ?></div>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Baris Header 3+: Karakteristik -->
                    <?php foreach ($kHeaderRowsPerPeriod as $kRowCells): ?>
                        <tr>
                            <?php foreach ($selected_years as $yr): ?>
                                <?php foreach ($subPeriodeList as $sp): ?>
                                    <?php foreach ($kRowCells as $hCell): ?>
                                        <th colspan="<?= $hCell['colspan']; ?>" rowspan="<?= $hCell['rowspan']; ?>" class="text-center" style="border: 1px solid rgba(255,255,255,0.25); vertical-align: middle; padding: 6px 8px; font-size: 11.5px; min-width: 90px; <?= (!empty($hCell['is_sub']) ? 'background: #0f355c;' : ''); ?>">
                                            <div><?= esc($hCell['ind']); ?></div>
                                            <?php if (!empty($hCell['en'])): ?>
                                                <div class="header-subtitle" style="font-size: 10px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($hCell['en']); ?></div>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Baris Nomor Urut Kolom: (1), (2), (3)... -->
            <tr class="num-row" style="background: #f9a066; color: #000000; font-size: 10px; font-weight: bold;">
                <?php 
                $numColIdx = 1;
                if ($hasNoCol): ?>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                <?php endif; ?>

                <?php if ($portraitMode && $portraitSplitCol): ?>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                <?php elseif ($isBarisDuaKolom && !$portraitMode): ?>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                <?php else: ?>
                    <?php if ($isRuntunWaktuTotal): ?>
                        <th colspan="2" class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                    <?php else: ?>
                        <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                    <?php endif; ?>
                <?php endif; ?>

                <?php foreach ($visibleLeafColumns as $vlCol): ?>
                    <th class="text-center" style="border: 1px solid #ffffff; padding: 2px 4px;">(<?= $numColIdx++; ?>)</th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php
            // Inisialisasi akumulator vertikal untuk auto-SUM Grand Total
            $colVerticalSums   = array_fill(0, count($visibleLeafColumns), 0.0);
            $colVerticalCounts = array_fill(0, count($visibleLeafColumns), 0);
            $hasDbTotalRow     = false;
            $dbTotalRowObj     = null;
            $rowNo             = 1;

            if ($portraitMode):
                // ==================== TBODY PORTRAIT MODE (TERMASUK MACRO TIME-SERIES) ====================
                foreach ($itemsBaris as $bIdx => $baris):
                    $tipeBaris  = strtolower(trim($baris->tipe_baris ?? 'data'));
                    $namaBaris  = $baris->nama_baris_ind ?? '';
                    $lowerBaris = strtolower($namaBaris);

                    if ($tipeBaris === 'total' || $lowerBaris === 'jawa tengah' || $lowerBaris === 'jumlah') {
                        $dbTotalRowObj = $baris;
                        continue; // dirender di blok total bawah
                    }

                    // Cek pembatas wilayah (Kabupaten / Kota)
                    if (!$isMacroTimeSeries && ($tipeBaris === 'kategori' || $lowerBaris === 'kabupaten' || $lowerBaris === 'kota')) {
                        if ($groupByRegion): ?>
                            <tr class="category-row" style="background: #eef2f7; font-weight: bold;">
                                <td colspan="<?= ($hasNoCol ? 1 : 0) + ($portraitSplitCol ? 2 : 1) + count($visibleLeafColumns); ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1;">
                                    <?= esc($namaBaris); ?>
                                </td>
                            </tr>
                        <?php endif;
                        continue;
                    }

                    $formattedBarisName = $formatRowLabel($namaBaris, $rowNo, false);
                    $pCount = count($portraitPeriods);

                    foreach ($portraitPeriods as $pIdx => $pp):
                        $yr         = $pp['tahun'];
                        $pId        = $pp['p_id'];
                        $yrLabel    = $pp['tahun_label'];
                        $pLabel     = $pp['p_label'];
                        $fullLabel  = $pp['full_label'];

                        // Pre-pass: hitung nilai per kolom dan akumulator baris
                        $rowNumericVals = [];
                        foreach ($leafColumns as $lCol) {
                            if (!empty($lCol['is_grand_total']) || !empty($lCol['is_group_total'])) continue;
                            if ($skipBuiltinTotalInSum && !empty($lCol['is_builtin_total'])) continue;
                            $kId = (int)$lCol['item_id'];
                            $cData = $nilaiMap[$yr][$baris->id][$kId][$pId] ?? null;
                            $v = $cData['nilai'] ?? null;
                            if ($v !== null && $v !== '') {
                                $rowNumericVals[] = (float)$v;
                            }
                        }
                        $portraitRowClass = (($rowNo + $pIdx) % 2 === 1 ? 'row-odd' : 'row-even');
                        $portraitRowBg    = (($rowNo + $pIdx) % 2 === 1 ? '#fde5ce' : '#fef3e6');
                    ?>
                        <tr class="<?= $portraitRowClass; ?>" style="background: <?= $portraitRowBg; ?>; color: #000000;">
                            <?php if ($hasNoCol): ?>
                                <?php if ($isMacroTimeSeries): ?>
                                    <td class="text-center text-muted" style="border: 1px solid #ffffff; vertical-align: middle; font-size: 11px;">
                                        <?= ($pIdx + 1); ?>
                                    </td>
                                <?php elseif ($pIdx === 0): ?>
                                    <td rowspan="<?= $pCount; ?>" class="text-center text-muted" style="border: 1px solid #ffffff; vertical-align: middle; font-size: 11px;">
                                        <?= $rowNo; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($isMacroTimeSeries && $portraitSplitCol): ?>
                                <td class="fw-semibold text-dark text-center" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px; font-size: 12px;">
                                    <?= esc($yrLabel); ?>
                                </td>
                                <td class="text-secondary" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px;">
                                    <?= esc($pLabel); ?>
                                </td>
                            <?php elseif ($isMacroTimeSeries): ?>
                                <td class="fw-semibold text-dark" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px;">
                                    <?= esc($fullLabel); ?>
                                </td>
                            <?php elseif ($portraitSplitCol): ?>
                                <?php if ($pIdx === 0): ?>
                                    <td rowspan="<?= $pCount; ?>" class="fw-semibold text-dark" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px; font-size: 12px;">
                                        <?= esc($formattedBarisName); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="text-center text-secondary" style="border: 1px solid #ffffff; padding: 6px 8px; font-size: 11.5px;">
                                    <?= esc($fullLabel); ?>
                                </td>
                            <?php else: ?>
                                <td class="text-dark" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px;">
                                    <?= esc($formattedBarisName); ?> <span class="text-muted small ms-1">(<?= esc($fullLabel); ?>)</span>
                                </td>
                            <?php endif; ?>

                            <?php 
                            foreach ($visibleLeafColumns as $cIdx => $lCol):
                                if (!empty($lCol['is_grand_total'])):
                                    $rowAgg = null;
                                    if (!empty($rowNumericVals)) {
                                        $rowAgg = ($jenisTotalHorizontal === 'avg') 
                                            ? (array_sum($rowNumericVals) / count($rowNumericVals))
                                            : array_sum($rowNumericVals);
                                        $colVerticalSums[$cIdx] += (float)$rowAgg;
                                        $colVerticalCounts[$cIdx]++;
                                    }
                                    $displayCell = function_exists('formatDynamicCellValue')
                                        ? formatDynamicCellValue($rowAgg, null, $cfgDesimal)
                                        : ($rowAgg !== null ? number_format($rowAgg, 2, ',', '.') : '-');
                                ?>
                                    <td class="text-end fw-bold" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px; background: #fed7aa; color: #9a3412;">
                                        <?= $displayCell; ?>
                                    </td>
                                <?php else:
                                    $kId      = (int)$lCol['item_id'];
                                    $cellData = $nilaiMap[$yr][$baris->id][$kId][$pId] ?? null;
                                    $val      = $cellData['nilai'] ?? null;
                                    $simb     = $cellData['simbol'] ?? null;
                                    $dec      = $cellData['jumlah_desimal'] ?? null;

                                    if ($val !== null && $val !== '') {
                                        $colVerticalSums[$cIdx] += (float)$val;
                                        $colVerticalCounts[$cIdx]++;
                                    }

                                    $displayCell = function_exists('formatDynamicCellValue')
                                        ? formatDynamicCellValue($val, $simb, $cfgDesimal, $dec)
                                        : ($simb ?: ($val !== null ? number_format((float)$val, 2, ',', '.') : '-'));
                                ?>
                                    <td class="text-end" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px;">
                                        <?= $displayCell; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php $rowNo++; ?>
                <?php endforeach; ?>

            <?php else: ?>
                <!-- ==================== TBODY STANDAR / LANDSCAPE ==================== -->
                <?php
                // Deteksi Multi-Kolom Baris (format pemisah '||')
                $isMultiBaris = (strpos($cleanTitleBarisInd, '||') !== false);
                if (!$isMultiBaris && !empty($itemsBaris)) {
                    foreach ($itemsBaris as $ib) {
                        if (strpos($ib->nama_baris_ind ?? '', '||') !== false) {
                            $isMultiBaris = true;
                            break;
                        }
                    }
                }

                // Pre-hitung rowspan untuk Multi-Kolom Baris (||)
                $col1RowspanMap = [];
                $col1SkipSet    = [];
                if ($isMultiBaris) {
                    $nRows = count($itemsBaris);
                    $i = 0;
                    while ($i < $nRows) {
                        $p1 = trim(explode('||', $itemsBaris[$i]->nama_baris_ind ?? '')[0] ?? '');
                        $span = 1;
                        while (($i + $span) < $nRows) {
                            $nextP1 = trim(explode('||', $itemsBaris[$i + $span]->nama_baris_ind ?? '')[0] ?? '');
                            if ($nextP1 !== '' && $nextP1 === $p1) {
                                $span++;
                            } else {
                                break;
                            }
                        }
                        if ($span > 1) {
                            $col1RowspanMap[$i] = $span;
                            for ($k = $i + 1; $k < $i + $span; $k++) {
                                $col1SkipSet[$k] = true;
                            }
                        }
                        $i += $span;
                    }
                }

                // Pre-hitung jumlah anak untuk mode 2 kolom hierarkis (Induk Komoditi & Rincian Satuan/Provinsi)
                $childCountMap = [];
                if ($isBarisDuaKolom && !$isMultiBaris) {
                    $bCount = count($itemsBaris);
                    for ($i = 0; $i < $bCount; $i++) {
                        $lvl = (int)($itemsBaris[$i]->level_indentasi ?? 0);
                        if ($lvl === 0) {
                            $cnt = 0;
                            for ($j = $i + 1; $j < $bCount; $j++) {
                                $nextLvl = (int)($itemsBaris[$j]->level_indentasi ?? 0);
                                if ($nextLvl > 0) {
                                    $cnt++;
                                } else {
                                    break;
                                }
                            }
                            if ($cnt > 0) {
                                $childCountMap[$i] = $cnt;
                            }
                        }
                    }
                }

                $currentParentInd        = '';
                $currentParentEn         = '';
                $currentParentRowspan    = 1;
                $isFirstChildOfParent    = false;
                $isJustRenderedFirstChild = false;

                foreach ($itemsBaris as $bIdx => $baris):
                    $tipeBaris   = strtolower(trim($baris->tipe_baris ?? 'data'));
                    $namaBaris   = $baris->nama_baris_ind ?? '';
                    $namaBarisEn = $baris->nama_baris_en ?? '';
                    $lowerBaris  = strtolower($namaBaris);
                    $isTotalRow  = ($tipeBaris === 'total' || $lowerBaris === 'jawa tengah' || $lowerBaris === 'jumlah');

                    // Baris Total bawaan DB
                    if ($isTotalRow) {
                        $dbTotalRowObj = $baris;
                        if (!$totalVertikal) continue;
                        if ($isRuntunWaktuTotal) {
                            // Akan dirender di blok runtun waktu bawah
                            continue;
                        }
                        $hasDbTotalRow = true;
                    }

                    // Baris Kategori Pembatas Wilayah
                    if ($tipeBaris === 'kategori' || $lowerBaris === 'kabupaten' || $lowerBaris === 'kota') {
                        if ($groupByRegion): ?>
                            <tr class="category-row" style="background: #fde5ce; font-weight: bold; color: #000000;">
                                <td colspan="<?= ($hasNoCol ? 1 : 0) + ($isBarisDuaKolom ? 2 : 1) + count($visibleLeafColumns); ?>" style="padding: 10px 15px; border: 1px solid #ffffff; font-size: 12px;">
                                    <?= esc($namaBaris); ?>
                                </td>
                            </tr>
                        <?php endif;
                        continue;
                    }

                    // Resolusi label baris
                    $displayBarisLabel = $formatRowLabel($namaBaris, $rowNo, $isTotalRow);
                    if ($isTotalRow) {
                        if ($jenisTotalVertikal === 'jawa_tengah') {
                            $displayBarisLabel = 'Jawa Tengah';
                        } elseif ($jenisTotalVertikal === 'jumlah') {
                            $displayBarisLabel = 'Jumlah';
                        } elseif ($jenisTotalVertikal === 'kustom' && !empty($labelTotalKustom)) {
                            $displayBarisLabel = $labelTotalKustom;
                        }
                    }

                    $indentLevel   = (int)($baris->level_indentasi ?? 0);
                    $indentPadding = 12 + ($indentLevel * 16);
                    $isBoldRow     = $isTotalRow || !empty($baris->is_bold) || ($tipeBaris === 'subtotal');
                    $rowClass      = $isTotalRow ? 'row-total' : ($rowNo % 2 === 1 ? 'row-odd' : 'row-even');
                    $rowBg         = $isTotalRow ? '#FF6D1F' : ($rowNo % 2 === 1 ? '#fde5ce' : '#fef3e6');
                    $rowColor      = $isTotalRow ? '#ffffff' : '#000000';
                    $rowFontWeight = $isBoldRow ? 'bold' : 'normal';

                    // Mode 2 Kolom Hierarkis: Induk Level 0 yang memiliki anak TIDAK membuat <tr> tersendiri
                    if ($isBarisDuaKolom && !$isMultiBaris) {
                        $hasChildren = !empty($childCountMap[$bIdx]);
                        if ($indentLevel === 0 && $hasChildren) {
                            $currentParentInd        = $displayBarisLabel;
                            $currentParentEn         = $namaBarisEn;
                            $currentParentRowspan    = $childCountMap[$bIdx];
                            $isFirstChildOfParent    = true;
                            $isJustRenderedFirstChild = false;
                            continue;
                        }
                    }

                    // Pre-pass: hitung group subtotal ($groupSums) dan grand horizontal total ($rowNumericVals) dari seluruh $leafColumns
                    $rowNumericVals = [];
                    $groupSums      = [];
                    $groupCounts    = [];

                    foreach ($leafColumns as $lCol) {
                        if (!empty($lCol['is_grand_total']) || !empty($lCol['is_group_total'])) continue;
                        $yr   = $lCol['tahun'];
                        $kId  = (int)$lCol['item_id'];
                        $pId  = (int)$lCol['periode_id'];
                        $gKey = $lCol['group_key'];

                        $cellData = $nilaiMap[$yr][$baris->id][$kId][$pId] ?? null;
                        $val      = $cellData['nilai'] ?? null;

                        if ($val !== null && $val !== '') {
                            if (!($skipBuiltinTotalInSum && !empty($lCol['is_builtin_total']))) {
                                $rowNumericVals[] = (float)$val;
                                $groupSums[$gKey]   = ($groupSums[$gKey] ?? 0.0) + (float)$val;
                                $groupCounts[$gKey] = ($groupCounts[$gKey] ?? 0) + 1;
                            }
                        }
                    }
                ?>
                    <tr class="<?= $rowClass; ?>" style="background: <?= $rowBg; ?>; font-weight: <?= $rowFontWeight; ?>; color: <?= $rowColor; ?>; <?= ($isTotalRow ? 'border-top: 2px solid #ffffff;' : ''); ?>">
                        <?php if ($hasNoCol): ?>
                            <?php if ($isBarisDuaKolom && !$isMultiBaris): ?>
                                <?php if ($indentLevel > 0): ?>
                                    <?php if ($isFirstChildOfParent): ?>
                                        <td rowspan="<?= $currentParentRowspan; ?>" class="text-center" style="border: 1px solid #ffffff; vertical-align: middle; font-size: 11px; padding: 6px 4px; <?= ($isTotalRow ? 'color: #ffffff;' : 'color: #64748b;'); ?>">
                                            <?= $rowNo; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <td class="text-center" style="border: 1px solid #ffffff; vertical-align: middle; font-size: 11px; padding: 6px 4px; <?= ($isTotalRow ? 'color: #ffffff;' : 'color: #64748b;'); ?>">
                                        <?= $isTotalRow ? '' : $rowNo; ?>
                                    </td>
                                <?php endif; ?>
                            <?php else: ?>
                                <td class="text-center" style="border: 1px solid #ffffff; vertical-align: middle; font-size: 11px; padding: 6px 4px; <?= ($isTotalRow ? 'color: #ffffff;' : 'color: #64748b;'); ?>">
                                    <?= $isTotalRow ? '' : $rowNo; ?>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($isBarisDuaKolom): ?>
                            <?php if ($isMultiBaris): 
                                $barisParts = explode('||', $displayBarisLabel);
                                $bCol1 = trim($barisParts[0] ?? $displayBarisLabel);
                                $bCol2 = trim($barisParts[1] ?? '');
                                $rSpan = $col1RowspanMap[$bIdx] ?? 1;
                                $isSkipCol1 = isset($col1SkipSet[$bIdx]);
                            ?>
                                <?php if (!$isSkipCol1): ?>
                                    <td rowspan="<?= $rSpan; ?>" class="col-wilayah" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px 6px <?= $indentPadding; ?>px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important;' : ''); ?>">
                                        <?= esc($bCol1); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="col-rincian" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important;' : ''); ?>">
                                    <?= esc($bCol2); ?>
                                </td>
                            <?php else: ?>
                                <?php if ($indentLevel > 0): ?>
                                    <?php if ($isFirstChildOfParent): 
                                        $isJustRenderedFirstChild = true;
                                    ?>
                                        <td rowspan="<?= $currentParentRowspan; ?>" class="col-wilayah fw-bold" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px; font-size: 12px;">
                                            <div><?= esc($currentParentInd); ?></div>
                                            <?php if (!empty($currentParentEn) && strcasecmp(trim($currentParentEn), trim($currentParentInd)) !== 0): ?>
                                                <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($currentParentEn); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <?php $isFirstChildOfParent = false; ?>
                                    <?php endif; ?>
                                    <td class="col-rincian text-center" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important;' : ''); ?>">
                                        <div><?= esc($displayBarisLabel); ?></div>
                                        <?php if (!empty($namaBarisEn) && strcasecmp(trim($namaBarisEn), trim($displayBarisLabel)) !== 0): ?>
                                            <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($namaBarisEn); ?></div>
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td colspan="2" class="col-wilayah" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px 6px <?= $indentPadding; ?>px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important;' : ''); ?>">
                                        <div><?= esc($displayBarisLabel); ?></div>
                                        <?php if (!empty($namaBarisEn) && strcasecmp(trim($namaBarisEn), trim($displayBarisLabel)) !== 0): ?>
                                            <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85;"><?= esc($namaBarisEn); ?></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <td <?= ($isRuntunWaktuTotal ? 'colspan="2"' : ''); ?> class="col-wilayah" style="border: 1px solid #ffffff; vertical-align: middle; padding: 6px 12px 6px <?= $indentPadding; ?>px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important;' : ''); ?>">
                                <div><?= esc($displayBarisLabel); ?></div>
                                <?php if (!empty($namaBarisEn) && strcasecmp(trim($namaBarisEn), trim($displayBarisLabel)) !== 0): ?>
                                    <div class="header-subtitle" style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.85; <?= ($isTotalRow ? 'color: #ffffff;' : ''); ?>"><?= esc($namaBarisEn); ?></div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <?php 
                        foreach ($visibleLeafColumns as $cIdx => $lCol):
                            if (!empty($lCol['is_grand_total'])):
                                $rowAgg = null;
                                if (!empty($rowNumericVals)) {
                                    $rowAgg = ($jenisTotalHorizontal === 'avg') 
                                        ? (array_sum($rowNumericVals) / count($rowNumericVals))
                                        : array_sum($rowNumericVals);
                                } elseif ($isTotalRow && $colVerticalCounts[$cIdx] > 0) {
                                    $rowAgg = $colVerticalSums[$cIdx];
                                }
                                if (!$isTotalRow && $rowAgg !== null) {
                                    $colVerticalSums[$cIdx] += (float)$rowAgg;
                                    $colVerticalCounts[$cIdx]++;
                                }
                                $displayCell = function_exists('formatDynamicCellValue')
                                    ? formatDynamicCellValue($rowAgg, null, $cfgDesimal)
                                    : ($rowAgg !== null ? number_format($rowAgg, 2, ',', '.') : '-');
                            ?>
                                <td class="text-end fw-bold" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px; <?= ($isTotalRow ? 'background: #FF6D1F; color: #ffffff !important;' : 'background: #fed7aa; color: #9a3412;'); ?>">
                                    <?= $displayCell; ?>
                                </td>
                            <?php elseif (!empty($lCol['is_group_total'])):
                                $gKey = $lCol['group_key'];
                                $gVal = (!empty($groupCounts[$gKey])) ? $groupSums[$gKey] : null;
                                if ($isTotalRow && $gVal === null && $colVerticalCounts[$cIdx] > 0) {
                                    $gVal = $colVerticalSums[$cIdx];
                                }
                                if (!$isTotalRow && $gVal !== null) {
                                    $colVerticalSums[$cIdx] += (float)$gVal;
                                    $colVerticalCounts[$cIdx]++;
                                }
                                $displayCell = function_exists('formatDynamicCellValue')
                                    ? formatDynamicCellValue($gVal, null, $cfgDesimal)
                                    : ($gVal !== null ? number_format($gVal, 2, ',', '.') : '-');
                            ?>
                                <td class="text-end fw-semibold" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px; <?= ($isTotalRow ? 'background: #FF6D1F; color: #ffffff !important;' : 'background: #fde5ce; color: #000000;'); ?>">
                                    <?= $displayCell; ?>
                                </td>
                            <?php else:
                                $yr  = $lCol['tahun'];
                                $kId = (int)$lCol['item_id'];
                                $pId = (int)$lCol['periode_id'];

                                $cellData = $nilaiMap[$yr][$baris->id][$kId][$pId] ?? null;
                                $val      = $cellData['nilai'] ?? null;
                                $simb     = $cellData['simbol'] ?? null;
                                $dec      = $cellData['jumlah_desimal'] ?? null;

                                // Jika baris Total DB kosong pada tahun ini, fallback ke penjumlahan vertikal otomatis!
                                if ($isTotalRow && ($val === null || $val === '') && empty($simb) && $colVerticalCounts[$cIdx] > 0) {
                                    $val = $colVerticalSums[$cIdx];
                                }

                                if (!$isTotalRow && $val !== null && $val !== '') {
                                    $colVerticalSums[$cIdx] += (float)$val;
                                    $colVerticalCounts[$cIdx]++;
                                }

                                $displayCell = function_exists('formatDynamicCellValue')
                                    ? formatDynamicCellValue($val, $simb, $cfgDesimal, $dec)
                                    : ($simb ?: ($val !== null ? number_format((float)$val, 2, ',', '.') : '-'));
                            ?>
                                <td class="text-end" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px; <?= ($isTotalRow ? 'color: #ffffff !important; font-weight: bold;' : ''); ?>">
                                    <?= $displayCell; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                    <?php 
                    if (!$isTotalRow) {
                        if ($isBarisDuaKolom && !$isMultiBaris) {
                            if ($indentLevel === 0 || $isJustRenderedFirstChild) {
                                $rowNo++;
                            }
                        } else {
                            $rowNo++;
                        }
                    }
                    $isJustRenderedFirstChild = false;
                    ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php // =========================================================================
            // MODE KHUSUS RUNTUN WAKTU MULTI-TAHUN DI BARIS BAWAH (SESUAI DOKUMEN BPS)
            // =========================================================================
            if ($isRuntunWaktuTotal && !empty($runtunYears)):
                $hasDbTotalRow = true;
                if (!$dbTotalRowObj) {
                    foreach ($itemsBaris as $bCand) {
                        $cName = strtolower(trim($bCand->nama_baris_ind ?? ''));
                        if ($cName === 'jawa tengah' || $cName === 'jumlah' || ($bCand->tipe_baris ?? '') === 'total') {
                            $dbTotalRowObj = $bCand;
                            break;
                        }
                    }
                }

                $rCount = count($runtunYears);
                $totalLabelInd = 'Jumlah';
                $totalLabelEn  = 'Total';
                if (!empty($labelTotalKustom)) {
                    $totalLabelInd = $labelTotalKustom;
                    $totalLabelEn  = '';
                } elseif ($dbTotalRowObj) {
                    if (!empty($dbTotalRowObj->nama_baris_ind)) {
                        $totalLabelInd = $dbTotalRowObj->nama_baris_ind;
                    }
                    if (!empty($dbTotalRowObj->nama_baris_en)) {
                        $totalLabelEn = $dbTotalRowObj->nama_baris_en;
                    }
                }
                $isBilingualTotal = !empty($totalLabelEn) && (strtolower(trim($totalLabelInd)) !== strtolower(trim($totalLabelEn)));

                foreach ($runtunYears as $rIdx => $hYr):
                    $hYrLabel = $formatTahunLabel($hYr);
            ?>
                <tr class="grand-total-row row-total" style="background: #FF6D1F; color: #ffffff; font-weight: bold; border-top: 2px solid #ffffff;">
                    <?php if ($hasNoCol): ?>
                        <?php if ($rIdx === 0): ?>
                            <td rowspan="<?= $rCount; ?>" class="text-center" style="border: 1px solid #ffffff; padding: 6px; vertical-align: middle; background: #FF6D1F; color: #ffffff;"></td>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($rIdx === 0): ?>
                        <td rowspan="<?= $rCount; ?>" class="fw-bold td-freeze-col" style="border: 1px solid #ffffff; padding: 8px 12px; font-size: 12px; color: #ffffff; vertical-align: middle; background: #FF6D1F;">
                            <div><?= esc($totalLabelInd); ?><?php if ($isBilingualTotal): ?> <span style="font-size: 11px; font-weight: normal; font-style: italic; opacity: 0.9;">/ <?= esc($totalLabelEn); ?></span><?php endif; ?></div>
                        </td>
                    <?php endif; ?>

                    <td class="text-center fw-bold" style="border: 1px solid #ffffff; padding: 6px 10px; font-size: 12px; color: #ffffff; vertical-align: middle; background: #FF6D1F; width: 80px;">
                        <?= esc($hYrLabel); ?>
                    </td>

                    <?php foreach ($visibleLeafColumns as $cIdx => $lCol):
                        $kId = (int)$lCol['item_id'];
                        $pId = (int)$lCol['periode_id'];
                        $bId = $dbTotalRowObj ? (int)$dbTotalRowObj->id : 0;
                        $cellData = $nilaiMap[$hYr][$bId][$kId][$pId] ?? null;
                        $val  = $cellData['nilai'] ?? null;
                        $simb = $cellData['simbol'] ?? null;
                        $dec  = $cellData['jumlah_desimal'] ?? null;
                        $displayCell = function_exists('formatDynamicCellValue')
                            ? formatDynamicCellValue($val, $simb, $cfgDesimal, $dec)
                            : ($simb ?: ($val !== null ? number_format((float)$val, 2, ',', '.') : '-'));
                    ?>
                        <td class="text-end fw-bold" style="border: 1px solid #ffffff; padding: 6px 12px; font-size: 12px; color: #ffffff; background: #FF6D1F;">
                            <?= $displayCell; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php 
                endforeach;
            endif;

            // =========================================================================
            // GRAND TOTAL VERTIKAL OTOMATIS: JIKA DIAKTIFKAN DAN BELUM ADA ROW TOTAL DB
            // =========================================================================
            if ($totalVertikal && !$hasDbTotalRow && !$isMacroTimeSeries):
                if ($jenisTotalVertikal === 'jawa_tengah') {
                    $grandLabel = 'Jawa Tengah';
                } elseif ($jenisTotalVertikal === 'jumlah') {
                    $grandLabel = 'Jumlah';
                } elseif ($jenisTotalVertikal === 'kustom' && !empty($labelTotalKustom)) {
                    $grandLabel = $labelTotalKustom;
                } elseif ($dbTotalRowObj && !empty($dbTotalRowObj->nama_baris_ind)) {
                    $grandLabel = $dbTotalRowObj->nama_baris_ind;
                } else {
                    $grandLabel = 'Jumlah';
                }
            ?>
                <tr class="grand-total-row row-total" style="background: #FF6D1F; color: #ffffff; font-weight: bold; border-top: 2px solid #ffffff;">
                    <?php if ($hasNoCol): ?>
                        <td class="text-center" style="border: 1px solid #ffffff; padding: 8px 6px; color: #ffffff;"></td>
                    <?php endif; ?>

                    <?php if ($portraitMode && $portraitSplitCol): ?>
                        <td colspan="2" class="fw-bold" style="border: 1px solid #ffffff; padding: 8px 12px; font-size: 12px; color: #ffffff;">
                            <?= esc($grandLabel); ?>
                        </td>
                    <?php elseif ($isBarisDuaKolom && !$portraitMode): ?>
                        <td colspan="2" class="fw-bold" style="border: 1px solid #ffffff; padding: 8px 12px; font-size: 12px; color: #ffffff;">
                            <?= esc($grandLabel); ?>
                        </td>
                    <?php else: ?>
                        <td class="fw-bold" style="border: 1px solid #ffffff; padding: 8px 12px; font-size: 12px; color: #ffffff;">
                            <?= esc($grandLabel); ?>
                        </td>
                    <?php endif; ?>

                    <?php foreach ($visibleLeafColumns as $cIdx => $lCol): 
                        $cVal = ($colVerticalCounts[$cIdx] > 0) ? $colVerticalSums[$cIdx] : null;
                        $displayCell = function_exists('formatDynamicCellValue')
                            ? formatDynamicCellValue($cVal, null, $cfgDesimal)
                            : ($cVal !== null ? number_format($cVal, 2, ',', '.') : '-');
                    ?>
                        <td class="text-end fw-bold" style="border: 1px solid #ffffff; padding: 8px 12px; font-size: 12px; color: #ffffff; background: #FF6D1F;">
                            <?= $displayCell; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
