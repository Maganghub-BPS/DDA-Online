<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTStatusTahunanAndCleanSchema extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Buat tabel t_status_tahunan jika belum ada
        if (!$db->tableExists('t_status_tahunan')) {
            $db->query("
                CREATE TABLE `t_status_tahunan` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_tabel` INT(11) NOT NULL,
                    `tahun` SMALLINT(5) UNSIGNED NOT NULL COMMENT 'Tahun Publikasi / Anggaran DDA (misal 2025, 2026)',
                    `is_periksa` TINYINT(1) NOT NULL DEFAULT 0,
                    `catatan_periksa` MEDIUMTEXT NULL,
                    `is_confirm` VARCHAR(10) NOT NULL DEFAULT '0',
                    `catatan` VARCHAR(255) NULL DEFAULT '-',
                    `is_publish` TINYINT(1) NOT NULL DEFAULT 0,
                    `published_at` DATETIME NULL DEFAULT NULL,
                    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_status_tabel_tahun` (`id_tabel`, `tahun`),
                    KEY `idx_status_tahun_publish` (`tahun`, `is_publish`),
                    CONSTRAINT `fk_status_tahunan_tabel` FOREIGN KEY (`id_tabel`) REFERENCES `m_list_tabel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
        }

        // 2. Migrasikan data historis dari t_tahun_tabel ke t_status_tahunan (deduplikasi per id_tabel + tahun)
        if ($db->tableExists('t_tahun_tabel')) {
            // Pastikan semua m_indikator_tabel sudah memiliki id_tabel yang valid sebelum id_tahun_tabel dihapus
            if ($db->fieldExists('id_tahun_tabel', 'm_indikator_tabel')) {
                $db->query("
                    UPDATE `m_indikator_tabel` mi
                    INNER JOIN `t_tahun_tabel` tt ON tt.id = mi.id_tahun_tabel
                    SET mi.id_tabel = tt.id_tabel
                    WHERE (mi.id_tabel IS NULL OR mi.id_tabel = 0)
                      AND tt.id_tabel IS NOT NULL
                ");
            }

            // Sinkronkan link_tabel yang belum masuk ke m_konfigurasi_api
            if ($db->tableExists('m_konfigurasi_api')) {
                $db->query("
                    INSERT INTO `m_konfigurasi_api` (`id_tabel`, `link_api`, `config_tabel`, `tahun_tersedia`, `updated_at`)
                    SELECT
                        t.id_tabel,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN t.link_tabel IS NOT NULL AND t.link_tabel != '' AND t.link_tabel != '-' THEN t.link_tabel ELSE NULL END ORDER BY t.tahun DESC, t.id DESC SEPARATOR '|||'), '|||', 1) AS link_api,
                        SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN t.config_tabel IS NOT NULL AND t.config_tabel != '' THEN t.config_tabel ELSE NULL END ORDER BY t.tahun DESC, t.id DESC SEPARATOR '|||'), '|||', 1) AS config_tabel,
                        GROUP_CONCAT(DISTINCT t.periode_id ORDER BY t.periode_id ASC SEPARATOR ',') AS tahun_tersedia,
                        NOW()
                    FROM `t_tahun_tabel` t
                    INNER JOIN `m_list_tabel` m ON m.id = t.id_tabel
                    LEFT JOIN `m_konfigurasi_api` ka ON ka.id_tabel = t.id_tabel
                    WHERE ka.id IS NULL
                      AND t.id_tabel IS NOT NULL
                      AND (
                          (t.link_tabel IS NOT NULL AND t.link_tabel != '' AND t.link_tabel != '-')
                          OR (t.config_tabel IS NOT NULL AND t.config_tabel != '')
                      )
                    GROUP BY t.id_tabel
                ");
            }

            // Migrasikan status tahunan ke t_status_tahunan
            $db->query("
                INSERT INTO `t_status_tahunan` (`id_tabel`, `tahun`, `is_periksa`, `catatan_periksa`, `is_confirm`, `catatan`, `is_publish`, `published_at`, `updated_at`)
                SELECT
                    t.id_tabel,
                    CAST(t.tahun AS UNSIGNED) AS tahun_int,
                    MAX(COALESCE(t.is_periksa, 0)) AS is_periksa,
                    SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN t.catatan_periksa IS NOT NULL AND t.catatan_periksa != '' AND t.catatan_periksa != '-' THEN t.catatan_periksa ELSE NULL END ORDER BY t.id DESC SEPARATOR '|||'), '|||', 1) AS catatan_periksa,
                    CASE WHEN MAX(CASE WHEN t.is_confirm = '1' THEN 1 ELSE 0 END) = 1 THEN '1' ELSE COALESCE(MAX(t.is_confirm), '0') END AS is_confirm,
                    COALESCE(SUBSTRING_INDEX(GROUP_CONCAT(CASE WHEN t.catatan IS NOT NULL AND t.catatan != '' AND t.catatan != '-' THEN t.catatan ELSE NULL END ORDER BY t.id DESC SEPARATOR '|||'), '|||', 1), '-') AS catatan,
                    MAX(COALESCE(t.is_publish, 0)) AS is_publish,
                    MAX(t.published_at) AS published_at,
                    NOW()
                FROM `t_tahun_tabel` t
                INNER JOIN `m_list_tabel` m ON m.id = t.id_tabel
                WHERE t.id_tabel IS NOT NULL
                  AND t.tahun REGEXP '^[0-9]{4}$'
                GROUP BY t.id_tabel, CAST(t.tahun AS UNSIGNED)
                ON DUPLICATE KEY UPDATE
                    `is_periksa` = VALUES(`is_periksa`),
                    `catatan_periksa` = COALESCE(VALUES(`catatan_periksa`), `t_status_tahunan`.`catatan_periksa`),
                    `is_confirm` = VALUES(`is_confirm`),
                    `catatan` = COALESCE(VALUES(`catatan`), `t_status_tahunan`.`catatan`),
                    `is_publish` = VALUES(`is_publish`),
                    `published_at` = COALESCE(VALUES(`published_at`), `t_status_tahunan`.`published_at`)
            ");
        }

        // 3. Hapus kolom id_tahun_tabel dari m_indikator_tabel jika ada
        if ($db->fieldExists('id_tahun_tabel', 'm_indikator_tabel')) {
            // Lepas foreign key atau index pada id_tahun_tabel jika ada
            $indexes = $db->getIndexData('m_indikator_tabel');
            foreach ($indexes as $idx) {
                if (in_array('id_tahun_tabel', $idx->fields, true) && $idx->name !== 'PRIMARY') {
                    try {
                        $db->query("ALTER TABLE `m_indikator_tabel` DROP INDEX `{$idx->name}`");
                    } catch (\Throwable $e) {
                        // Abaikan jika index tidak bisa dihapus langsung
                    }
                }
            }
            $this->forge->dropColumn('m_indikator_tabel', 'id_tahun_tabel');
        }

        // 4. Hapus tabel legacy t_tahun_tabel
        if ($db->tableExists('t_tahun_tabel')) {
            $this->forge->dropTable('t_tahun_tabel', true);
        }
    }

    public function down()
    {
        // One-way cleanup migration
    }
}
