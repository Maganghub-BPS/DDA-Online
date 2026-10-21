<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\M_tabeldinamis;

class UnsurIklimSeeder extends Seeder
{
    public function run()
    {
        $m = new M_tabeldinamis();
        $db = \Config\Database::connect();

        $nama_ind = 'Unsur Iklim Menurut Stasiun Klimatologi';
        $nama_en  = 'Climate Elements by Climatological Station';

        // Cek apakah sudah ada untuk menghindari duplikasi
        $existing = $db->table('m_baris_kelompok')
            ->where('nama_kelompok_ind', $nama_ind)
            ->orWhere('nama_kelompok_ind', 'Unsur Iklim')
            ->get()
            ->getRow();

        if ($existing) {
            $idB = $existing->id;
            echo "Kelompok Baris sudah ada dengan ID {$idB}. Memperbarui item...\n";
            $db->table('m_baris_kelompok')->where('id', $idB)->update([
                'nama_kelompok_ind' => $nama_ind,
                'nama_kelompok_en'  => $nama_en,
                'keterangan'        => 'Unsur Iklim 4 Stasiun Klimatologi: Semarang, Cilacap, Tegal, Banjarnegara (Suhu, Kelembaban, Angin, Tekanan, Curah Hujan, dsb)',
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        } else {
            $idB = $m->saveBarisKelompok([
                'nama_kelompok_ind' => $nama_ind,
                'nama_kelompok_en'  => $nama_en,
                'keterangan'        => 'Unsur Iklim 4 Stasiun Klimatologi: Semarang, Cilacap, Tegal, Banjarnegara (Suhu, Kelembaban, Angin, Tekanan, Curah Hujan, dsb)',
            ]);
            echo "Kelompok Baris baru berhasil dibuat dengan ID {$idB}.\n";
        }

        $baris_ind = "Stasiun Klimatologi Semarang
-- Suhu
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kelembaban (%)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kecepatan Angin (km/jam)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Tekanan Udara (mb)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Jumlah Curah Hujan (mm)
-- Jumlah Hari Hujan (hari)
-- Penyinaran Matahari (%)
Stasiun Klimatologi Cilacap
-- Suhu
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kelembaban (%)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kecepatan Angin (km/jam)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Tekanan Udara (mb)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Jumlah Curah Hujan (mm)
-- Jumlah Hari Hujan (hari)
-- Penyinaran Matahari (%)
Stasiun Klimatologi Tegal
-- Suhu
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kelembaban (%)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kecepatan Angin (km/jam)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Tekanan Udara (mb)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Jumlah Curah Hujan (mm)
-- Jumlah Hari Hujan (hari)
-- Penyinaran Matahari (%)
Stasiun Klimatologi Banjarnegara
-- Suhu
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kelembaban (%)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Kecepatan Angin (m/det)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Tekanan Udara (mb)
---- Minimum
---- Rata-Rata
---- Maksimum
-- Jumlah Curah Hujan (mm)
-- Jumlah Hari Hujan (hari)
-- Penyinaran Matahari (%)";

        $baris_en = "Semarang Climatological Station
-- Temperature
---- Minimum
---- Average
---- Maximum
-- Humidity (%)
---- Minimum
---- Average
---- Maximum
-- Wind Velocity (km/h)
---- Minimum
---- Average
---- Maximum
-- Atmospheric Pressure (mb)
---- Minimum
---- Average
---- Maximum
-- Number of Precipitation (mm)
-- Number of Rainy Days (day)
-- Duration of Sunshine (%)
Cilacap Climatological Station
-- Temperature
---- Minimum
---- Average
---- Maximum
-- Humidity (%)
---- Minimum
---- Average
---- Maximum
-- Wind Velocity (km/h)
---- Minimum
---- Average
---- Maximum
-- Atmospheric Pressure (mb)
---- Minimum
---- Average
---- Maximum
-- Number of Precipitation (mm)
-- Number of Rainy Days (day)
-- Duration of Sunshine (%)
Tegal Climatological Station
-- Temperature
---- Minimum
---- Average
---- Maximum
-- Humidity (%)
---- Minimum
---- Average
---- Maximum
-- Wind Velocity (km/h)
---- Minimum
---- Average
---- Maximum
-- Atmospheric Pressure (mb)
---- Minimum
---- Average
---- Maximum
-- Number of Precipitation (mm)
-- Number of Rainy Days (day)
-- Duration of Sunshine (%)
Banjarnegara Climatological Station
-- Temperature
---- Minimum
---- Average
---- Maximum
-- Humidity (%)
---- Minimum
---- Average
---- Maximum
-- Wind Velocity (m/sec)
---- Minimum
---- Average
---- Maximum
-- Atmospheric Pressure (mb)
---- Minimum
---- Average
---- Maximum
-- Number of Precipitation (mm)
-- Number of Rainy Days (day)
-- Duration of Sunshine (%)";

        $m->parseBarisFromText($idB, $baris_ind, $baris_en);

        $total = count($m->getBarisItems($idB));
        echo "Berhasil menyimpan {$total} baris bertingkat untuk Unsur Iklim!\n";
    }
}
