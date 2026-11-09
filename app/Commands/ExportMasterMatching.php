<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ExportMasterMatching extends BaseCommand
{
    protected $group = 'App';
    protected $name = 'export:matching';
    protected $description = 'Export database tables snapshot to dda_db_{ta}.json for PDF matching';
    protected $usage = 'export:matching [ta]';
    protected $arguments = ['ta' => 'Tahun anggaran (default 2026)'];
    protected $options = [];

    public function run(array $params)
    {
        $ta = $params[0] ?? '2026';
        CLI::write("Exporting master matching for TA {$ta}...", 'yellow');

        $mDinamis = new \App\Models\M_tabeldinamis();
        $dynamicMap = $mDinamis->exportDynamicTablesForMatching((int)$ta);
        CLI::write("Dynamic Map loaded: " . count($dynamicMap) . " tables.", 'cyan');

        $db = \Config\Database::connect();
        $query = $db->query("
            SELECT 
                m.id as id_db,
                COALESCE(m.no_tabel, '') as nomor_tabel, 
                m.judul_ind as raw_judul, 
                'Tahunan' as periode_id,
                ka.link_api as link_tabel,
                CASE 
                    WHEN m.jenis_sumber = 'api_kominfo' THEN 0 
                    WHEN m.jenis_sumber = 'dinamis' THEN 1 
                    WHEN mi.id IS NOT NULL THEN 1 
                    ELSE 0 
                END as is_dynamic,
                ka.config_tabel,
                m.jenis_sumber
            FROM m_list_tabel m
            LEFT JOIN m_konfigurasi_api ka ON ka.id_tabel = m.id
            LEFT JOIN m_indikator_tabel mi ON mi.id_tabel = m.id
            WHERE m.is_active = 1
            ORDER BY m.id ASC
        ");

        $raw_tables = $query->getResultArray();
        $db_tables = [];

        foreach ($raw_tables as $r) {
            $idDb = (int)$r['id_db'];
            $formatted_judul = function_exists('format_judul_tabel')
                ? format_judul_tabel($r['raw_judul'], $r['periode_id'])
                : $r['raw_judul'];

            $dynInfo = $dynamicMap[$idDb] ?? null;
            $isDyn = ((int)$r['is_dynamic'] === 1 || $dynInfo !== null) ? 1 : 0;
            $linkLower = strtolower(trim($r['link_tabel'] ?? ''));

            if (($r['jenis_sumber'] ?? '') === 'api_kominfo') {
                $isDyn = 0;
                $sourceType = 'api';
            } elseif ($isDyn === 1) {
                $sourceType = 'dinamis';
            } elseif (strpos($linkLower, 'view_portal_tabel') !== false || strpos($linkLower, 'satudata') !== false) {
                $sourceType = 'api';
            } else {
                $sourceType = 'spreadsheet';
            }

            $db_tables[] = [
                'id_db'         => $idDb,
                'id_indikator'  => $dynInfo ? (int)($dynInfo['id_indikator'] ?? 0) : 0,
                'nomor_tabel'   => $r['nomor_tabel'],
                'judul_tabel'   => $formatted_judul,
                'link_tabel'    => $r['link_tabel'],
                'is_dynamic'    => $isDyn,
                'source_type'   => $sourceType,
                'config_tabel'  => $r['config_tabel'] ?? null,
                'dynamic_empty' => $dynInfo ? (bool)$dynInfo['dynamic_empty'] : ($isDyn === 1),
                'dynamic_grid'  => $dynInfo ? ($dynInfo['dynamic_grid'] ?? []) : [],
                'template_grid' => $dynInfo ? ($dynInfo['template_grid'] ?? []) : [],
                'cell_coords'   => $dynInfo ? ($dynInfo['cell_coords'] ?? []) : [],
            ];
        }

        foreach ($db_tables as &$row) {
            if (empty(trim($row['nomor_tabel']))) {
                if (preg_match("/(?:Tabel|Table)?\s*(\d+\.\d+(?:\.\d+[a-zA-Z]*)?)/i", $row['judul_tabel'], $matches)) {
                    $row['nomor_tabel'] = trim($matches[1]);
                }
            }
        }
        unset($row);

        $destPath = WRITEPATH . "uploads/dda_db_{$ta}.json";
        file_put_contents($destPath, json_encode($db_tables, JSON_UNESCAPED_UNICODE));
        CLI::write("SUCCESS: Saved " . count($db_tables) . " tables to {$destPath}", 'green');

        // Check key tables
        foreach ($db_tables as $t) {
            if (in_array($t['nomor_tabel'], ['2.3.21', '6.2.7', '10.2.17', '10.2.18', '7.3.1', '5.2.12', '5.4.16', '5.3.8', '4.1.20', '4.1.12', '4.3.5', '4.3.6', '11.5'])) {
                CLI::write("  Table {$t['nomor_tabel']}: id={$t['id_db']}, src={$t['source_type']}, dyn={$t['is_dynamic']}, empty=" . ($t['dynamic_empty'] ? 'yes' : 'no'));
            }
        }
    }
}
