<?php
$mysqli = new mysqli("localhost", "root", "", "ddaonline");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$res = $mysqli->query("SHOW TABLES");
echo "Tables:\n";
while ($row = $res->fetch_row()) {
    echo "- " . $row[0] . "\n";
}

$res = $mysqli->query("SELECT COUNT(*) FROM t_tabel_match");
if ($res) {
    $row = $res->fetch_row();
    echo "\nt_tabel_match count: " . $row[0] . "\n";
} else {
    echo "\nt_tabel_match table does not exist.\n";
}

$res = $mysqli->query("SELECT * FROM t_tabel_match LIMIT 5");
if ($res) {
    echo "Samples from t_tabel_match:\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}

$mysqli->close();
?>
