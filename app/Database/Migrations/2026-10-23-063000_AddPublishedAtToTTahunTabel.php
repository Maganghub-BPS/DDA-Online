<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublishedAtToTTahunTabel extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('published_at', 't_tahun_tabel')) {
            $fields = [
                'published_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'is_publish',
                ],
            ];
            $this->forge->addColumn('t_tahun_tabel', $fields);

            // Inisialisasi awal published_at untuk tabel yang sudah is_publish = 1
            // agar data existing tetap konsisten dan terurut berdasarkan ID
            $this->db->query("
                UPDATE t_tahun_tabel 
                SET published_at = DATE_ADD('2026-01-01 00:00:00', INTERVAL id MINUTE) 
                WHERE is_publish = 1 AND published_at IS NULL
            ");

            // Tambahkan index untuk optimasi query sorting 'Tabel Terbaru'
            $this->db->query("CREATE INDEX idx_published_at_desc ON t_tahun_tabel (is_publish, published_at)");
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('published_at', 't_tahun_tabel')) {
            $this->forge->dropColumn('t_tahun_tabel', 'published_at');
        }
    }
}
