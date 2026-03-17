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
    $segments = $request->getUri()->getSegments();
    $current_offset = isset($segments[$uri_segment-1]) ? (int)$segments[$uri_segment-1] : 0;
    
    $html = '<ul class="pagination">';
    $total_pages = ceil($total_row / $per_page);
    for ($i = 0; $i < $total_pages; $i++) {
        $offset = $i * $per_page;
        $active = ($offset == $current_offset) ? 'class="active disabled"' : '';
        $html .= '<li ' . $active . '><a href="' . $url . '/' . $offset . '">' . ($i + 1) . '</a></li>';
    }
    $html .= '</ul>';
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
