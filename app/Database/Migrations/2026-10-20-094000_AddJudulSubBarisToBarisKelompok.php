<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJudulSubBarisToBarisKelompok extends Migration
{
    public function up()
    {
        $fields = [
            'judul_sub_baris_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'judul_baris_en',
            ],
            'judul_sub_baris_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'judul_sub_baris_ind',
            ],
        ];
        $this->forge->addColumn('m_baris_kelompok', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('m_baris_kelompok', ['judul_sub_baris_ind', 'judul_sub_baris_en']);
    }
}
