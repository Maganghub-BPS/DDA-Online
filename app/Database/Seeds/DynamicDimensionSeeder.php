<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\M_tabeldinamis;

class DynamicDimensionSeeder extends Seeder
{
    public function run()
    {
        $m = new M_tabeldinamis();

        // 1. SEED KARAKTERISTIK (Contoh Nyata: Sarana Farmasi & Kesehatan Asimetris)
        $idK = $m->saveKarakteristikKelompok([
            'nama_kelompok_ind' => 'Sarana Farmasi dan Kesehatan',
            'nama_kelompok_en'  => 'Pharmaceutical and Health Facilities',
            'keterangan'        => 'Struktur kolom asimetris: Distribusi (2 sub-kolom), Pelayanan (8 sub-kolom), dan Jumlah (mandiri)',
        ]);

        $kolomText = [
            ['item_ind' => 'Distribusi || Pedagang Besar Farmasi', 'item_en' => 'Distribution || Pharmaceutical Wholesaler', 'no_urut' => 1],
            ['item_ind' => 'Distribusi || Gudang Farmasi Kab/Kota', 'item_en' => 'Distribution || Regency/City Pharmacy Warehouse', 'no_urut' => 2],
            ['item_ind' => 'Pelayanan || Toko Obat', 'item_en' => 'Services || Medicine Store', 'no_urut' => 3],
            ['item_ind' => 'Pelayanan || Apotek', 'item_en' => 'Services || Pharmacy', 'no_urut' => 4],
            ['item_ind' => 'Pelayanan || Rumah Sakit Pemerintah', 'item_en' => 'Services || Government Hospital', 'no_urut' => 5],
            ['item_ind' => 'Pelayanan || Rumah Sakit Swasta', 'item_en' => 'Services || Private Hospital', 'no_urut' => 6],
            ['item_ind' => 'Pelayanan || Puskesmas', 'item_en' => 'Services || Public Health Center', 'no_urut' => 7],
            ['item_ind' => 'Pelayanan || Puskesmas Pembantu', 'item_en' => 'Services || Subsidiary Health Center', 'no_urut' => 8],
            ['item_ind' => 'Pelayanan || Rumah Bersalin', 'item_en' => 'Services || Maternity Hospital', 'no_urut' => 9],
            ['item_ind' => 'Pelayanan || Klinik', 'item_en' => 'Services || Clinic', 'no_urut' => 10],
            ['item_ind' => 'Jumlah', 'item_en' => 'Total', 'no_urut' => 11],
        ];
        $m->syncKarakteristikItems($idK, $kolomText);

        // 2. SEED BARIS (35 Kabupaten & Kota di Jawa Tengah)
        $idB = $m->saveBarisKelompok([
            'nama_kelompok_ind' => '35 Kabupaten/Kota Jawa Tengah',
            'nama_kelompok_en'  => '35 Regencies/Cities in Central Java',
            'keterangan'        => 'Daftar resmi 29 kabupaten dan 6 kota di Jawa Tengah beserta baris total',
        ]);

        $kabKota = [
            'Cilacap', 'Banyumas', 'Purbalingga', 'Banjarnegara', 'Kebumen',
            'Purworejo', 'Wonosobo', 'Magelang', 'Boyolali', 'Klaten',
            'Sukoharjo', 'Wonogiri', 'Karanganyar', 'Sragen', 'Grobogan',
            'Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara',
            'Demak', 'Semarang', 'Temanggung', 'Kendal', 'Batang',
            'Pekalongan', 'Pemalang', 'Tegal', 'Brebes',
            'Kota Magelang', 'Kota Surakarta', 'Kota Salatiga', 'Kota Semarang', 'Kota Pekalongan', 'Kota Tegal',
            'Jawa Tengah'
        ];

        $barisItems = [];
        $no = 1;
        foreach ($kabKota as $nama) {
            $isTotal = ($nama === 'Jawa Tengah');
            $barisItems[] = [
                'nama_baris_ind'  => $nama,
                'nama_baris_en'   => $nama,
                'level_indentasi' => 0,
                'tipe_baris'      => $isTotal ? 'total' : 'data',
                'is_bold'         => $isTotal ? 1 : 0,
                'no_urut'         => $no++,
            ];
        }
        $m->syncBarisItems($idB, $barisItems);

        // 2b. SEED BARIS BERTINGKAT (Contoh Pengeluaran Komoditas)
        $idB2 = $m->saveBarisKelompok([
            'nama_kelompok_ind' => 'Pengeluaran Komoditas (Hierarki 2 Level)',
            'nama_kelompok_en'  => 'Commodity Expenditure (2-Level Hierarchy)',
            'keterangan'        => 'Struktur baris bertingkat dengan kategori induk, data berindentasi, subtotal, dan total',
        ]);

        $barisHierarki = "Makanan
-- Padi-padian
-- Umbi-umbian
-- Ikan / Udang
-- Daging
Jumlah Makanan
Bukan Makanan
-- Perumahan dan Fasilitas Rumah Tangga
-- Aneka Barang dan Jasa
Jumlah Bukan Makanan
TOTAL";
        $m->parseBarisFromText($idB2, $barisHierarki);

        // 3. SEED PERIODE (Tahunan 2020 - 2024)
        $idP = $m->savePeriodeKelompok([
            'nama_kelompok_ind' => 'Tahunan 2020 - 2024',
            'nama_kelompok_en'  => 'Annual 2020 - 2024',
            'keterangan'        => 'Deret waktu tahunan 5 tahun terakhir',
        ]);
        $m->generatePeriodeYearRange($idP, 2020, 2024, 'ASC');

        echo "Seed data dimensi tabel dinamis berhasil dijalankan!\n";
    }
}
