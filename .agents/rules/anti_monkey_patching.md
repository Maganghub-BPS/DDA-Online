# RULE: ANTI-TAMBAL-SULAM & ROOT-CAUSE FIRST ARCHITECTURE

## Prinsip Mutlak:
Setiap kali menyelesaikan masalah logika, bug data, atau pencocokan sistem (seperti perbandingan tabel, parser, atau API), kamu DILARANG KERAS membuat kode tambal-sulam (*monkey patching*).

## 4 Pilar Larangan Tambal-Sulaman:

1. DILARANG EKSEKUSI PER KASUS TUNGGAL SECARA TERISOLASI
   - Jangan langsung menulis kode atau patch hanya berdasarkan satu kasus/tabel jika pengguna mengindikasikan ada banyak kasus serupa yang bermasalah.
   - Kumpulkan dan minta seluruh daftar kasus yang bermasalah terlebih dahulu.

2. ANALISIS HOLISTIK SEBELUM MENULIS KODE (ROOT-CAUSE FIRST)
   - Bedah pola bersama (*shared patterns*) di antara semua sampel kasus yang dilaporkan.
   - Identifikasi akar masalah pada lapisan desain/arsitektur (misal: normalizer skema, urutan eksekusi token, state tracker wilayah, pemetaan kolom semantik).
   - Dilarang membuat `if table_id == 'xyz'` atau percabangan khusus per nomor tabel kecuali secara inheren diatur dalam regulasi/konfigurasi formal.

3. SOLUSI GENERIK & ELEGAN (GENERIC SCHEMA-DRIVEN IMPLEMENTATION)
   - Solusi wajib ditulis pada lapisan abstrak/generik yang mampu menyelesaikan seluruh kelas masalah tersebut secara alami.
   - Perubahan harus bersih, terstruktur, mudah dirawat (*maintainable*), dan tidak menambah utang teknis (*technical debt*).

4. VERIFIKASI ANTI-REGRESI MULTI-SAMPEL (BENCHMARK VERIFICATION)
   - Sebelum menyatakan pekerjaan selesai, kode wajib diuji silang terhadap seluruh kasus uji acuan (*benchmarks*), baik kasus yang baru diperbaiki maupun kasus yang sebelumnya sudah bekerja normal.
   - Jika perbaikan satu kasus menyebabkan kasus lain rusak/berubah hasil (regresi), perbaikan tersebut dianggap GAGAL dan harus dirancang ulang dari akar masalahnya.
