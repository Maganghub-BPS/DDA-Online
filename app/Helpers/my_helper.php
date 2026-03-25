<?php

function gli($tabel, $field_kunci, $pad) {
	$db = \Config\Database::connect();
	$nama = $db->query("SELECT max($field_kunci) AS last FROM $tabel")->getRow();
	$data = (intval($nama->last)) + 1;
	$last = str_pad($data, $pad, '0', STR_PAD_LEFT);
	return $last;
}

function gval($tabel, $field_kunci, $diambil, $where) {
	$db = \Config\Database::connect();	
	$nama = $db->query("SELECT $diambil FROM $tabel WHERE $field_kunci = '$where'")->getRow();
	$data = empty($nama) ? "-" : $nama->$diambil;
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
