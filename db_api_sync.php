<?php
require 'vendor/autoload.php';
// Bootstrapping CI4 CLI
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

$tables = [
    't_api_data' => "CREATE TABLE IF NOT EXISTS `t_api_data` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_api` varchar(100) NOT NULL,
        `judul` text DEFAULT NULL,
        `tahun_data` varchar(10) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_api` (`id_api`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
    
    't_dataportal' => "CREATE TABLE IF NOT EXISTS `t_dataportal` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_portal` varchar(100) NOT NULL,
        `tahun_data` varchar(10) DEFAULT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_portal` (`id_portal`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
    
    't_tabel_match' => "CREATE TABLE IF NOT EXISTS `t_tabel_match` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_tabel` int(11) DEFAULT NULL,
        `id_api` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"
];

foreach ($tables as $name => $sql) {
    if (!$db->tableExists($name)) {
        echo "Creating table $name...\n";
        $db->query($sql);
    } else {
        echo "Table $name already exists.\n";
    }
}
