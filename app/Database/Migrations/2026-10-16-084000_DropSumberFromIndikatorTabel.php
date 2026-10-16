<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropSumberFromIndikatorTabel extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('sumber', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'sumber');
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('sumber', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'sumber' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'desimal',
                    ],
                ]);
            }
        }
    }
}
