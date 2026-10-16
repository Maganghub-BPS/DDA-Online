<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKonfigurasiTotalToIndikatorTabel extends Migration
{
    public function up()
    {
        $fields = [
            'konfigurasi_total' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'default',
                'after'      => 'posisi_hirarki',
            ],
            'label_total_kustom' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'konfigurasi_total',
            ],
        ];
        $this->forge->addColumn('m_indikator_tabel', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('m_indikator_tabel', ['konfigurasi_total', 'label_total_kustom']);
    }
}
