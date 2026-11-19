<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMappingTabelTahunan extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('t_mapping_tabel_tahunan')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_tabel' => [
                'type'     => 'INT',
                'unsigned' => false,
                'null'     => false,
                'comment'  => 'FK ke m_list_tabel.id (identitas abadi tabel lintas tahun)',
            ],
            'tahun_publikasi' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => false,
                'comment'  => 'Tahun edisi buku PDF DDA (mis. 2026, 2027)',
            ],
            'no_tabel_pdf' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'Nomor tabel di buku PDF tahun publikasi tersebut',
            ],
            'judul_pdf' => [
                'type'       => 'VARCHAR',
                'constraint' => 1000,
                'null'       => true,
            ],
            'halaman_pdf' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'skor_judul' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'status_match' => [
                'type'       => 'ENUM',
                'constraint' => ['matched', 'unmatched'],
                'default'    => 'matched',
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
        $this->forge->addUniqueKey(['id_tabel', 'tahun_publikasi'], 'uk_mapping_tabel_tahun');
        $this->forge->addKey(['tahun_publikasi', 'no_tabel_pdf'], false, false, 'idx_tahun_no_pdf');
        $this->forge->addForeignKey('id_tabel', 'm_list_tabel', 'id', 'CASCADE', 'CASCADE', 'fk_mapping_tahunan_tabel');
        $this->forge->createTable('t_mapping_tabel_tahunan', true);
    }

    public function down()
    {
        $this->forge->dropTable('t_mapping_tabel_tahunan', true);
    }
}
