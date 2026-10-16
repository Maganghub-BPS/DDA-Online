<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUnusedDynamicColumns extends Migration
{
    public function up()
    {
        // 1. Drop id_sub_karakteristik_item di t_nilai_dinamis & rebuild Unique Index
        if ($this->db->tableExists('t_nilai_dinamis')) {
            // Drop unique key uk_cell_position lama
            try {
                $this->db->query("ALTER TABLE `t_nilai_dinamis` DROP INDEX `uk_cell_position`");
            } catch (\Throwable $e) {}

            // Drop kolom id_sub_karakteristik_item
            if ($this->db->fieldExists('id_sub_karakteristik_item', 't_nilai_dinamis')) {
                $this->forge->dropColumn('t_nilai_dinamis', 'id_sub_karakteristik_item');
            }

            // Buat ulang unique key 4 kolom yang ramping dan presisi
            try {
                $this->db->query("ALTER TABLE `t_nilai_dinamis` ADD UNIQUE KEY `uk_cell_position` (`id_indikator`, `id_baris_item`, `id_karakteristik_item`, `id_periode_item`)");
            } catch (\Throwable $e) {}
        }

        // 2. Drop id_sub_karakteristik_kelompok & grafik_default di m_indikator_tabel
        if ($this->db->tableExists('m_indikator_tabel')) {
            if ($this->db->fieldExists('id_sub_karakteristik_kelompok', 'm_indikator_tabel')) {
                try {
                    $this->db->query("ALTER TABLE `m_indikator_tabel` DROP INDEX `id_sub_karakteristik_kelompok`");
                } catch (\Throwable $e) {}
                $this->forge->dropColumn('m_indikator_tabel', 'id_sub_karakteristik_kelompok');
            }

            if ($this->db->fieldExists('grafik_default', 'm_indikator_tabel')) {
                $this->forge->dropColumn('m_indikator_tabel', 'grafik_default');
            }
        }

        // 3. Drop parent_id di m_karakteristik_item
        if ($this->db->tableExists('m_karakteristik_item')) {
            if ($this->db->fieldExists('parent_id', 'm_karakteristik_item')) {
                try {
                    $this->db->query("ALTER TABLE `m_karakteristik_item` DROP INDEX `parent_id`");
                } catch (\Throwable $e) {}
                $this->forge->dropColumn('m_karakteristik_item', 'parent_id');
            }
        }
    }

    public function down()
    {
        // Rollback m_karakteristik_item
        if ($this->db->tableExists('m_karakteristik_item') && !$this->db->fieldExists('parent_id', 'm_karakteristik_item')) {
            $this->forge->addColumn('m_karakteristik_item', [
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
                $this->db->query("ALTER TABLE `m_karakteristik_item` ADD KEY `parent_id` (`parent_id`)");
            } catch (\Throwable $e) {}
        }

        // Rollback m_indikator_tabel
        if ($this->db->tableExists('m_indikator_tabel')) {
            if (!$this->db->fieldExists('id_sub_karakteristik_kelompok', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'id_sub_karakteristik_kelompok' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'id_karakteristik_kelompok',
                    ],
                ]);
                try {
                    $this->db->query("ALTER TABLE `m_indikator_tabel` ADD KEY `id_sub_karakteristik_kelompok` (`id_sub_karakteristik_kelompok`)");
                } catch (\Throwable $e) {}
            }

            if (!$this->db->fieldExists('grafik_default', 'm_indikator_tabel')) {
                $this->forge->addColumn('m_indikator_tabel', [
                    'grafik_default' => [
                        'type'       => 'ENUM',
                        'constraint' => ['bar', 'line', 'pie'],
                        'default'    => 'bar',
                        'after'      => 'desimal',
                    ],
                ]);
            }
        }

        // Rollback t_nilai_dinamis
        if ($this->db->tableExists('t_nilai_dinamis') && !$this->db->fieldExists('id_sub_karakteristik_item', 't_nilai_dinamis')) {
            try {
                $this->db->query("ALTER TABLE `t_nilai_dinamis` DROP INDEX `uk_cell_position`");
            } catch (\Throwable $e) {}

            $this->forge->addColumn('t_nilai_dinamis', [
                'id_sub_karakteristik_item' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'id_karakteristik_item',
                ],
            ]);

            try {
                $this->db->query("ALTER TABLE `t_nilai_dinamis` ADD UNIQUE KEY `uk_cell_position` (`id_indikator`, `id_baris_item`, `id_karakteristik_item`, `id_sub_karakteristik_item`, `id_periode_item`)");
            } catch (\Throwable $e) {}
        }
    }
}
