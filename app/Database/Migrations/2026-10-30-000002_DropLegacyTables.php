<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $legacyTables = [
            't_mach',
            't_list_tabel',
            'm_kondef',
            't_api_data',
            't_dataportal',
            't_data_portal',
            't_tabel_match',
        ];

        foreach ($legacyTables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    public function down()
    {
        // Legacy tables are obsolete and intentionally discarded.
    }
}

