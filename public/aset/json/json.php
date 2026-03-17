<?php
mysql_connect("localhost", "root", "");
mysql_select_db("simurat");
$query = "SELECT d.batas_waktu,d.kpd_yth,s.isi_ringkas,s.no_surat from t_disposisi d inner join t_surat_masuk s on d.id_surat=s.id ";
$result = mysql_query($query) or die(mysql_error());

$arr = array();
while ($row = mysql_fetch_assoc($result)) {
    $temp = array(
        "date" => $row["batas_waktu"],       
        "title" => substr($row["kpd_yth"],4,strlen($row["kpd_yth"]))." ; ".$row["isi_ringkas"]." ;Surat Nomor : ".$row["no_surat"],
        "description" => $row["kpd_yth"]);

    array_push($arr, $temp);}
$data = json_encode($arr);
echo $data
?>