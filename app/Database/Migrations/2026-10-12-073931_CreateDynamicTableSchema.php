<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDynamicTableSchema extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------------------
        // 1. MASTER KARAKTERISTIK KELOMPOK (Dimensi Kolom/Atribut)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kelompok_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'nama_kelompok_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('m_karakteristik_kelompok', true);

        // ---------------------------------------------------------------------
        // 2. MASTER KARAKTERISTIK ITEM (Sub-kolom & Notasi Induk || Anak)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            'grup_induk_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Induk level 1 jika menggunakan format Induk || Anak',
            ],
            'grup_induk_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'item_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'item_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'no_urut' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'is_standalone' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'comment'    => '1 jika kolom mandiri tanpa sub-kolom (rowspan 2), misal: Total',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_kelompok');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('m_karakteristik_item', true);

        // ---------------------------------------------------------------------
        // 3. MASTER JUDUL BARIS KELOMPOK (Dimensi Baris)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kelompok_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'nama_kelompok_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('m_baris_kelompok', true);

        // ---------------------------------------------------------------------
        // 4. MASTER JUDUL BARIS ITEM (Mendukung Hierarchical Tree Rows)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'comment'    => 'ID baris induk jika baris bertingkat',
            ],
            'no_urut' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'nama_baris_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'nama_baris_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'tipe_baris' => [
                'type'       => 'ENUM',
                'constraint' => ['kategori', 'data', 'subtotal', 'total'],
                'default'    => 'data',
                'comment'    => 'kategori=header pemisah, data=isi nilai, subtotal/total=agregat',
            ],
            'level_indentasi' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'default'    => 0,
                'comment'    => '0=rata kiri, 1=indent 1 level, 2=indent 2 level',
            ],
            'is_bold' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_kelompok');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('m_baris_item', true);

        // ---------------------------------------------------------------------
        // 5. MASTER PERIODE WAKTU KELOMPOK (Dimensi Waktu)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_kelompok_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'nama_kelompok_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('m_periode_kelompok', true);

        // ---------------------------------------------------------------------
        // 6. MASTER PERIODE WAKTU ITEM
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama_periode_ind' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'nama_periode_en' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'no_urut' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_kelompok');
        $this->forge->createTable('m_periode_item', true);

        // ---------------------------------------------------------------------
        // 7. MASTER INDIKATOR TABEL (Generator / Perakitan Matriks Tabel)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_tahun_tabel' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'comment'    => 'Relasi ke t_tahun_tabel.id',
            ],
            'no_tabel' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'judul_ind' => [
                'type' => 'TEXT',
            ],
            'judul_en' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'id_unitkerja' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'id_karakteristik_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'Karakteristik Utama (Level 1)',
            ],
            'id_sub_karakteristik_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Sub-Karakteristik Level 2 (opsional, jika simetris)',
            ],
            'id_baris_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_periode_kelompok' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'desimal' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'default'    => 0,
            ],
            'grafik_default' => [
                'type'       => 'ENUM',
                'constraint' => ['bar', 'line', 'pie'],
                'default'    => 'bar',
            ],
            'sumber' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'catatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_tahun_tabel');
        $this->forge->addKey('id_karakteristik_kelompok');
        $this->forge->addKey('id_sub_karakteristik_kelompok');
        $this->forge->addKey('id_baris_kelompok');
        $this->forge->addKey('id_periode_kelompok');
        $this->forge->createTable('m_indikator_tabel', true);

        // ---------------------------------------------------------------------
        // 8. NILAI DATA SEL DINAMIS (t_nilai_dinamis)
        // ---------------------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_indikator' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_baris_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_karakteristik_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'comment'    => 'ID Kolom Leaf Item (Kolom terujung)',
            ],
            'id_sub_karakteristik_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'comment'    => '0 jika 1-level atau menggunakan notasi leaf',
            ],
            'id_periode_item' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nilai' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,4',
                'null'       => true,
            ],
            'simbol' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'comment'    => 'Notasi statistik BPS: -, NA, e, dll.',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('id_indikator');
        $this->forge->addKey('id_baris_item');
        $this->forge->addKey('id_karakteristik_item');
        $this->forge->addKey('id_periode_item');
        $this->forge->addUniqueKey(
            ['id_indikator', 'id_baris_item', 'id_karakteristik_item', 'id_sub_karakteristik_item', 'id_periode_item'],
            'uk_cell_position'
        );
        $this->forge->createTable('t_nilai_dinamis', true);

        // ---------------------------------------------------------------------
        // 9. Tambah kolom penanda `is_dynamic` di `t_tahun_tabel` jika belum ada
        // ---------------------------------------------------------------------
        if (!$this->db->fieldExists('is_dynamic', 't_tahun_tabel')) {
            $this->forge->addColumn('t_tahun_tabel', [
                'is_dynamic' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'is_publish',
                    'comment'    => '1 jika tabel menggunakan engine dinamis internal',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_dynamic', 't_tahun_tabel')) {
            $this->forge->dropColumn('t_tahun_tabel', 'is_dynamic');
        }

        $this->forge->dropTable('t_nilai_dinamis', true);
        $this->forge->dropTable('m_indikator_tabel', true);
        $this->forge->dropTable('m_periode_item', true);
        $this->forge->dropTable('m_periode_kelompok', true);
        $this->forge->dropTable('m_baris_item', true);
        $this->forge->dropTable('m_baris_kelompok', true);
        $this->forge->dropTable('m_karakteristik_item', true);
        $this->forge->dropTable('m_karakteristik_kelompok', true);
    }
}
