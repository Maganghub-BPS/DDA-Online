<?php
require 'vendor/autoload.php';
// Define FCPATH as the current directory
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Load the Bootstrap file
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

$db = \Config\Database::connect();
$query = $db->query("SELECT count(*) as total FROM t_tabel_match");
$row = $query->getRow();
echo "Total t_tabel_match: " . $row->total . "\n";

$query = $db->query("SELECT count(*) as total FROM t_api_data");
$row = $query->getRow();
echo "Total t_api_data: " . $row->total . "\n";
