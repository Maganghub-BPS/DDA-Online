<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJudulPeriodeToPeriodeKelompok extends Migration
{
    public function up()
    {
        $fields = [
            'judul_periode_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => 'Tahun',
                'after'      => 'nama_kelompok_en',
            ],
            'judul_periode_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => 'Year',
                'after'      => 'judul_periode_ind',
            ],
        ];
        $this->forge->addColumn('m_periode_kelompok', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('m_periode_kelompok', ['judul_periode_ind', 'judul_periode_en']);
    }
}
