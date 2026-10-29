<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKolomNomorToIndikatorTabel extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('kolom_nomor', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'kolom_nomor' => [
                        'type'       => 'ENUM',
                        'constraint' => ['tampilkan', 'sembunyikan'],
                        'default'    => 'sembunyikan',
                        'after'      => 'tampilan_baris',
                        'comment'    => 'tampilkan=Tampilkan kolom No. terpisah (1, 2, 3), sembunyikan=Tanpa kolom No terpisah (nomor menyatu di teks)',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('kolom_nomor', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'kolom_nomor');
            }
        }
    }
}
