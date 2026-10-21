<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTampilanBarisToIndikatorTabel extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('tampilan_baris', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'tampilan_baris' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 20,
                        'default'    => '1_kolom',
                        'after'      => 'format_nomor_baris',
                        'comment'    => '1_kolom=Standar 1 kolom berindentasi, 2_kolom=2 Kolom (Induk & Rincian)',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('tampilan_baris', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'tampilan_baris');
            }
        }
    }
}
