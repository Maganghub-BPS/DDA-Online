<?php

function gli($tabel, $field_kunci, $pad) {
	$db = \Config\Database::connect();
	$tabel_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabel);
	$field_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field_kunci);
	$nama = $db->query("SELECT MAX(`$field_clean`) AS last FROM `$tabel_clean`")->getRow();
	$data = (intval($nama->last ?? 0)) + 1;
	$last = str_pad((string)$data, (int)$pad, '0', STR_PAD_LEFT);
	return $last;
}

function gval($tabel, $field_kunci, $diambil, $where) {
	$db = \Config\Database::connect();
	$tabel_clean   = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabel);
	$field_clean   = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field_kunci);
	$diambil_clean = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$diambil);
	$nama = $db->query("SELECT `$diambil_clean` FROM `$tabel_clean` WHERE `$field_clean` = ?", [$where])->getRow();
	$data = empty($nama) ? "-" : ($nama->$diambil_clean ?? "-");
	return $data;
}

function konversi_level($id) {
	if ($id == "1") {
		echo "Admin Super";
	} else {
		echo "Admin Pos";
	}
}

function _page($total_row, $per_page, $uri_segment, $url) {
    if ($total_row <= $per_page) return '';
    
    $request = \Config\Services::request();
    $current_offset = (int)(segment_safe($uri_segment) ?? 0);
    
    $total_pages = ceil($total_row / $per_page);
    $current_page = floor($current_offset / $per_page) + 1;
    
    $html = '<nav class="d-flex justify-content-center"><ul class="pagination pagination-sm gap-1 mb-0">';
    
    // First & Previous
    if ($current_page > 1) {
        $prev_offset = ($current_page - 2) * $per_page;
        $html .= '<li class="page-item"><a class="page-link border-0 bg-gray-100 text-secondary border-radius-md" href="' . $url . '/0"><i class="bi bi-chevron-double-left"></i></a></li>';
        $html .= '<li class="page-item"><a class="page-link border-0 bg-gray-100 text-secondary border-radius-md" href="' . $url . '/' . $prev_offset . '"><i class="bi bi-chevron-left"></i></a></li>';
    }

    // Numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $start + 4);
    if ($end - $start < 4) $start = max(1, $end - 4);

    for ($i = $start; $i <= $end; $i++) {
        $offset = ($i - 1) * $per_page;
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link border-0 border-radius-md bg-primary-orange text-white fw-bold px-3">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link border-0 bg-gray-100 text-secondary border-radius-md fw-bold px-3" href="' . $url . '/' . $offset . '">' . $i . '</a></li>';
        }
    }

    // Next & Last
    if ($current_page < $total_pages) {
        $next_offset = $current_page * $per_page;
        $last_offset = ($total_pages - 1) * $per_page;
        $html .= '<li class="page-item"><a class="page-link border-0 bg-gray-100 text-secondary border-radius-md" href="' . $url . '/' . $next_offset . '"><i class="bi bi-chevron-right"></i></a></li>';
        $html .= '<li class="page-item"><a class="page-link border-0 bg-gray-100 text-secondary border-radius-md" href="' . $url . '/' . $last_offset . '"><i class="bi bi-chevron-double-right"></i></a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

function tgl_jam_sql ($tgl) {
    if (empty($tgl)) return "";
	$pc_satu	= explode(" ", $tgl);
	if (count($pc_satu) < 2) {	
		$tgl1		= $pc_satu[0];
		$jam1		= "";
	} else {
		$jam1		= $pc_satu[1];
		$tgl1		= $pc_satu[0];
	}
	
	$pc_dua		= explode("-", $tgl1);
	if(count($pc_dua) < 3) return $tgl;
	$tgl		= $pc_dua[2];
	$bln		= $pc_dua[1];
	$thn		= $pc_dua[0];
	
	if ($bln == "01") { $bln_txt = "Jan"; }  
	else if ($bln == "02") { $bln_txt = "Feb"; }  
	else if ($bln == "03") { $bln_txt = "Mar"; }  
	else if ($bln == "04") { $bln_txt = "Apr"; }  
	else if ($bln == "05") { $bln_txt = "Mei"; }  
	else if ($bln == "06") { $bln_txt = "Jun"; }  
	else if ($bln == "07") { $bln_txt = "Jul"; }  
	else if ($bln == "08") { $bln_txt = "Ags"; }  
	else if ($bln == "09") { $bln_txt = "Sep"; }  
	else if ($bln == "10") { $bln_txt = "Okt"; }  
	else if ($bln == "11") { $bln_txt = "Nov"; }  
	else if ($bln == "12") { $bln_txt = "Des"; }  	
	else { $bln_txt = ""; }
	
	return $tgl." ".$bln_txt." ".$thn."  ".$jam1;
}

function segment_safe($n) {
    try {
        $request = \Config\Services::request();
        $segments = $request->getUri()->getSegments();
        return isset($segments[$n-1]) ? $segments[$n-1] : null;
    } catch (\Exception $e) {
        return null; // fallback
    }
}

function _print_pdf($file, $data) {
    // mock
}

/**
 * Merakit judul tabel dinamis secara cerdas dengan akhiran tahun/periode
 * 
 * @param string $baseTitle
 * @param array $selectedYears
 * @param string $lang 'id' atau 'en'
 * @param string $tipePeriode 'tahunan', 'tahun_ajaran', dll.
 * @return string
 */
function formatDynamicTableTitle(string $baseTitle, array $selectedYears, string $lang = 'id', string $tipePeriode = 'tahunan'): string
{
    // 1. Bersihkan akhiran tahun lama jika ada
    $title = preg_replace('/,?\s*(?:Tahun\s+|Year\s+)?(?:19|20)\d{2}(?:\s*[\–\-\/]\s*(?:19|20)\d{2})?\s*$/iu', '', $baseTitle);
    $title = preg_replace('/,?\s*(?:19|20)\d{2}(?:,\s*(?:19|20)\d{2})*(?:\s*(?:dan|and|&)\s*(?:19|20)\d{2})?\s*$/iu', '', $title);
    $title = trim(rtrim($title, ','));

    // 2. Normalisasi dan urutkan tahun
    $years = [];
    foreach ($selectedYears as $y) {
        $yi = (int)$y;
        if ($yi > 1900 && $yi < 2100) {
            $years[] = $yi;
        }
    }
    $years = array_values(array_unique($years));
    sort($years);

    if (empty($years)) {
        return $title;
    }

    $isEn = (strtolower($lang) === 'en');
    $conj = $isEn ? ' and ' : ' dan ';
    $count = count($years);

    // Format per item (misal YYYY atau YYYY/YYYY+1)
    $isAkademik = ($tipePeriode === 'tahun_ajaran');
    $formatYearItem = function (int $y) use ($isAkademik): string {
        return $isAkademik ? "{$y}/" . ($y + 1) : (string)$y;
    };

    if ($count === 1) {
        $yearStr = $formatYearItem($years[0]);
    } elseif ($count === 2) {
        $yearStr = $formatYearItem($years[0]) . $conj . $formatYearItem($years[1]);
    } else {
        // Cek apakah tahun berurutan penuh tanpa loncat
        $isConsecutive = true;
        for ($i = 1; $i < $count; $i++) {
            if ($years[$i] !== $years[$i - 1] + 1) {
                $isConsecutive = false;
                break;
            }
        }

        if ($isConsecutive) {
            $yearStr = $formatYearItem($years[0]) . '–' . $formatYearItem(end($years));
        } else {
            // Loncat-loncat: list dipisah koma dengan konjungsi di ujung
            $lastYear = array_pop($years);
            $formattedList = array_map($formatYearItem, $years);
            $oxfordConj = $isEn ? ', and ' : ', dan ';
            $yearStr = implode(', ', $formattedList) . $oxfordConj . $formatYearItem($lastYear);
        }
    }

    // Jika judul mengandung placeholder [PERIODE] / [periode]
    if (stripos($title, '[PERIODE]') !== false) {
        return str_ireplace('[PERIODE]', $yearStr, $title);
    }

    return $title . ', ' . $yearStr;
}

/**
 * Format nilai sel tabel dinamis sesuai presisi desimal & simbol statistik BPS
 * 
 * @param mixed $nilai
 * @param mixed $simbol
 * @param int $settingDesimal Konfigurasi indikator: -1 (otomatis), 0, 1, 2, 3
 * @param int|null $cellDesimal Presisi desimal asli sel Excel jika ada
 * @return string
 */
function formatDynamicCellValue($nilai, $simbol = null, int $settingDesimal = -1, ?int $cellDesimal = null): string
{
    if ($simbol !== null && $simbol !== '') {
        return (string)$simbol;
    }

    if ($nilai === null || $nilai === '') {
        return '-';
    }

    $num = (float)$nilai;

    // Presisi ditentukan eksplisit (0, 1, 2, 3)
    if ($settingDesimal >= 0) {
        return number_format($num, $settingDesimal, ',', '.');
    }

    // Otomatis: gunakan desimal dari file Excel jika tersedia
    if ($cellDesimal !== null && $cellDesimal >= 0) {
        return number_format($num, $cellDesimal, ',', '.');
    }

    // Deteksi desimal signifikan dari nilai numerik
    $valStr = (string)$nilai;
    if (strpos($valStr, '.') !== false) {
        $parts = explode('.', $valStr);
        $decimals = strlen(rtrim($parts[1], '0'));
        return number_format($num, $decimals, ',', '.');
    }

    return number_format($num, 0, ',', '.');
}

if (!function_exists('formatInstansiName')) {
    /**
     * Format nama instansi khusus untuk tampilan frontend (menambahkan prefix BPS untuk 4 tim BPS)
     *
     * @param string|null $name
     * @param string $id
     * @return string
     */
    function formatInstansiName($name, $id = ''): string
    {
        if (empty($name)) {
            return (string)$name;
        }

        $nameStr = trim((string)$name);
        $idLower = strtolower(trim((string)$id));
        $nameLower = strtolower($nameStr);

        // Jika sudah mengandung kata BPS, tidak perlu diubah lagi
        if (stripos($nameStr, 'BPS') !== false) {
            return $nameStr;
        }

        // Pengecualian: jangan format jika OPD seperti Dinas Sosial
        if (strpos($nameLower, 'dinas') !== false) {
            return $nameStr;
        }

        $bps_ids = ['nerwilis', 'distribusi', 'produksi', 'sosial'];
        $isBpsTeam = in_array($idLower, $bps_ids) ||
                     strpos($nameLower, 'tim neraca') !== false ||
                     strpos($nameLower, 'tim statistik distribusi') !== false ||
                     strpos($nameLower, 'tim statistik produksi') !== false ||
                     strpos($nameLower, 'tim statistik sosial') !== false;

        if ($isBpsTeam) {
            return 'BPS (' . $nameStr . ')';
        }

        return $nameStr;
    }
}

