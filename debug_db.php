<?php
try {
    $db = new PDO("mysql:host=127.0.0.1;dbname=ddaonline", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['t_list_tabel', 't_api_data', 't_tabel_match'];
    
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Table $table: $count rows\n";
    }

    echo "\nSample from t_api_data (Satu Data Portal):\n";
    $samples = $db->query("SELECT judul FROM t_api_data LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($samples as $s) echo "- $s\n";

    echo "\nSample from t_list_tabel (DDA Online):\n";
    $samples = $db->query("SELECT judul_ind FROM t_list_tabel LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($samples as $s) echo "- $s\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
