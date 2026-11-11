<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJenisInstansiToMUnitkerja extends Migration
{
    public function up()
    {
        // 1. Tambah kolom jenis_instansi jika belum ada
        if (!$this->db->fieldExists('jenis_instansi', 'm_unitkerja')) {
            $fields = [
                'jenis_instansi' => [
                    'type'       => 'ENUM',
                    'constraint' => ['opd', 'vertikal'],
                    'default'    => 'vertikal',
                    'null'       => false,
                    'after'      => 'unitkerja_en',
                ],
            ];
            $this->forge->addColumn('m_unitkerja', $fields);
        }

        // 2. Daftar 27 OPD Pemprov Jawa Tengah yang terkonfirmasi
        $opdIds = [
            'bkd', 'BPKAD', 'bppd', 'biropem', 'disdukcapil', 'sekdprd',
            'disnaker', 'arsip', 'DP3AKB', 'perakim', 'dinsos', 'dinkes',
            'dkp', 'pertanian', 'ternaksehat', 'lingkunganhidup', 'esdm',
            'pemudaor', 'dishub', 'koperasi', 'modal', 'disperindag',
            'binamarga', 'inspektorat', 'dinpendidikan', 'BPBD', 'kesbangpol'
        ];

        // 3. Set default seluruh instansi selain BPS menjadi 'vertikal'
        $this->db->table('m_unitkerja')
            ->set('jenis_instansi', 'vertikal')
            ->update();

        // 4. Update 27 OPD Pemda terdaftar menjadi 'opd'
        $this->db->table('m_unitkerja')
            ->whereIn('id_unitkerja', $opdIds)
            ->set('jenis_instansi', 'opd')
            ->update();
    }

    public function down()
    {
        if ($this->db->fieldExists('jenis_instansi', 'm_unitkerja')) {
            $this->forge->dropColumn('m_unitkerja', 'jenis_instansi');
        }
    }
}

