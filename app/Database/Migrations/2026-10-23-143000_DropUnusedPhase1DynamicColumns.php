<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUnusedPhase1DynamicColumns extends Migration
{
    public function up()
    {
        // 1. Drop format_nomor_baris & satuan di m_indikator_tabel
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('format_nomor_baris', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'format_nomor_baris');
            }
            if ($this->db->fieldExists('satuan', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'satuan');
            }
        }

        // 2. Drop keterangan di m_baris_kelompok, m_karakteristik_kelompok, m_periode_kelompok
        if ($this->db->tableExists('m_baris_kelompok') && $this->db->fieldExists('keterangan', 'm_baris_kelompok')) {
            $this->forge->dropColumn('m_baris_kelompok', 'keterangan');
        }

        if ($this->db->tableExists('m_karakteristik_kelompok') && $this->db->fieldExists('keterangan', 'm_karakteristik_kelompok')) {
            $this->forge->dropColumn('m_karakteristik_kelompok', 'keterangan');
        }

        if ($this->db->tableExists('m_periode_kelompok') && $this->db->fieldExists('keterangan', 'm_periode_kelompok')) {
            $this->forge->dropColumn('m_periode_kelompok', 'keterangan');
        }

        // 3. Drop parent_id di m_baris_item
        if ($this->db->tableExists('m_baris_item') && $this->db->fieldExists('parent_id', 'm_baris_item')) {
            try {
                $this->db->query("ALTER TABLE `m_baris_item` DROP INDEX `parent_id`");
            } catch (\Throwable $e) {}
            $this->forge->dropColumn('m_baris_item', 'parent_id');
        }
    }

    public function down()
    {
        // Rollback m_indikator_tabel
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('format_nomor_baris', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'format_nomor_baris' => [
                        'type'       => 'ENUM',
                        'constraint' => ['auto', 'none', 'reset_kategori'],
                        'default'    => 'none',
                        'null'       => true,
                        'after'      => 'label_total_kustom',
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

        // Rollback keterangan
        if ($this->db->tableExists('m_baris_kelompok') && !$this->db->fieldExists('keterangan', 'm_baris_kelompok')) {
            $this->forge->addColumn('m_baris_kelompok', [
                'keterangan' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }
        if ($this->db->tableExists('m_karakteristik_kelompok') && !$this->db->fieldExists('keterangan', 'm_karakteristik_kelompok')) {
            $this->forge->addColumn('m_karakteristik_kelompok', [
                'keterangan' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }
        if ($this->db->tableExists('m_periode_kelompok') && !$this->db->fieldExists('keterangan', 'm_periode_kelompok')) {
            $this->forge->addColumn('m_periode_kelompok', [
                'keterangan' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }

        // Rollback parent_id di m_baris_item
        if ($this->db->tableExists('m_baris_item') && !$this->db->fieldExists('parent_id', 'm_baris_item')) {
            $this->forge->addColumn('m_baris_item', [
                'parent_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'id_kelompok',
                ],
            ]);
            try {
                $this->db->query("ALTER TABLE `m_baris_item` ADD KEY `parent_id` (`parent_id`)");
            } catch (\Throwable $e) {}
        }
    }
}
