<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixMissingColumnsIndikatorTabel extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('format_nomor_baris', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'format_nomor_baris' => [
                        'type'       => 'ENUM',
                        'constraint' => ['auto', 'none', 'reset_kategori'],
                        'default'    => 'auto',
                        'after'      => 'label_total_kustom',
                        'comment'    => 'auto=berurutan 1..N, none=tanpa nomor urut, reset_kategori=reset 1 per kategori',
                    ],
                ]);
            }

            if (!$this->db->fieldExists('satuan', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'satuan' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'tampilan_baris',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('format_nomor_baris', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'format_nomor_baris');
            }
            if ($this->db->fieldExists('satuan', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'satuan');
            }
        }
    }
}
