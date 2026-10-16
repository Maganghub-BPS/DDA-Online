<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeBarisKelompokNullableInIndikatorTabel extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            $this->forge->modifyColumn('m_indikator_tabel', [
                'id_baris_kelompok' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            $this->forge->modifyColumn('m_indikator_tabel', [
                'id_baris_kelompok' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
            ]);
        }
    }
}
