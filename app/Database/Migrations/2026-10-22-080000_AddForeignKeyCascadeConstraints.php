<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForeignKeyCascadeConstraints extends Migration
{
    public function up()
    {
        // 1. Bersihkan record orphan yang mungkin ada sebelum menambahkan constraint
        $this->db->query("DELETE FROM t_nilai_dinamis WHERE id_indikator NOT IN (SELECT id FROM m_indikator_tabel)");
        $this->db->query("DELETE FROM m_indikator_tabel WHERE id_tahun_tabel IS NOT NULL AND id_tahun_tabel NOT IN (SELECT id FROM t_tahun_tabel)");

        // 2. Foreign Key: m_indikator_tabel -> t_tahun_tabel (CASCADE)
        $checkFk1 = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'm_indikator_tabel' AND CONSTRAINT_NAME = 'fk_indikator_tahun_tabel'")->getRow();
        if (!$checkFk1) {
            $this->db->query("ALTER TABLE m_indikator_tabel ADD CONSTRAINT fk_indikator_tahun_tabel FOREIGN KEY (id_tahun_tabel) REFERENCES t_tahun_tabel(id) ON DELETE CASCADE");
        }

        // 3. Foreign Key: t_nilai_dinamis -> m_indikator_tabel (CASCADE)
        $checkFk2 = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 't_nilai_dinamis' AND CONSTRAINT_NAME = 'fk_nilai_indikator'")->getRow();
        if (!$checkFk2) {
            $this->db->query("ALTER TABLE t_nilai_dinamis ADD CONSTRAINT fk_nilai_indikator FOREIGN KEY (id_indikator) REFERENCES m_indikator_tabel(id) ON DELETE CASCADE");
        }

        // 4. Foreign Key: m_karakteristik_item -> m_karakteristik_kelompok (CASCADE)
        $checkFk3 = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'm_karakteristik_item' AND CONSTRAINT_NAME = 'fk_karakteristik_kelompok'")->getRow();
        if (!$checkFk3) {
            $this->db->query("ALTER TABLE m_karakteristik_item ADD CONSTRAINT fk_karakteristik_kelompok FOREIGN KEY (id_kelompok) REFERENCES m_karakteristik_kelompok(id) ON DELETE CASCADE");
        }

        // 5. Foreign Key: m_baris_item -> m_baris_kelompok (CASCADE)
        $checkFk4 = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'm_baris_item' AND CONSTRAINT_NAME = 'fk_baris_kelompok'")->getRow();
        if (!$checkFk4) {
            $this->db->query("ALTER TABLE m_baris_item ADD CONSTRAINT fk_baris_kelompok FOREIGN KEY (id_kelompok) REFERENCES m_baris_kelompok(id) ON DELETE CASCADE");
        }

        // 6. Foreign Key: m_periode_item -> m_periode_kelompok (CASCADE)
        $checkFk5 = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'm_periode_item' AND CONSTRAINT_NAME = 'fk_periode_kelompok'")->getRow();
        if (!$checkFk5) {
            $this->db->query("ALTER TABLE m_periode_item ADD CONSTRAINT fk_periode_kelompok FOREIGN KEY (id_kelompok) REFERENCES m_periode_kelompok(id) ON DELETE CASCADE");
        }
    }

    public function down()
    {
        $this->db->query("ALTER TABLE t_nilai_dinamis DROP FOREIGN KEY fk_nilai_indikator");
        $this->db->query("ALTER TABLE m_indikator_tabel DROP FOREIGN KEY fk_indikator_tahun_tabel");
        $this->db->query("ALTER TABLE m_karakteristik_item DROP FOREIGN KEY fk_karakteristik_kelompok");
        $this->db->query("ALTER TABLE m_baris_item DROP FOREIGN KEY fk_baris_kelompok");
        $this->db->query("ALTER TABLE m_periode_item DROP FOREIGN KEY fk_periode_kelompok");
    }
}
