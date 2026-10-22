<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class IncreaseSimbolLengthInNilaiDinamis extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('t_nilai_dinamis')) {
            $this->forge->modifyColumn('t_nilai_dinamis', [
                'simbol' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Notasi statistik BPS / teks satuan kustom',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('t_nilai_dinamis')) {
            $this->forge->modifyColumn('t_nilai_dinamis', [
                'simbol' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Notasi statistik BPS: -, NA, e, dll.',
                ],
            ]);
        }
    }
}
