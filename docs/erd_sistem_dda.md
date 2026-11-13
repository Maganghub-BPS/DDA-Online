# Diagram Relasi & Alur Tabel — Sistem DDA Online

![ERD Sistem DDA Online](erd_sistem_dda.svg)

Tata letak diagram mengalir **dari atas ke bawah**, kecuali `m_unitkerja` yang mengarah ke kanan ke `m_list_tabel`. Node yang setara (`m_indikator_tabel` & `m_konfigurasi_api`, ketiga kelompok dimensi, serta ketiga item dimensi) sejajar secara horizontal pada levelnya masing-masing.

```mermaid
flowchart TD
    subgraph TOP[" "]
        direction LR
        UK["m_unitkerja\n(PK: id_unitkerja | unitkerja_ind, user_wali, jenis_instansi)"]
        LT["m_list_tabel\n(PK: id | FK: id_unitkerja | no_tabel, judul_ind, jenis_sumber)"]
        UK -->|"1 : N (id_unitkerja)"| LT
    end

    LT -->|"dinamis (1 : 1)"| IND["m_indikator_tabel\n(PK: id | FK: id_tabel, id_karakteristik_kelompok, id_baris_kelompok, id_periode_kelompok)"]
    LT -->|"api_kominfo (1 : 1)"| API["m_konfigurasi_api\n(PK: id | FK: id_tabel | id_api, link_api, config_tabel, cached_json)"]

    IND --> KK["m_karakteristik_kelompok\n(Judul Kolom | PK: id)"]
    IND --> BK["m_baris_kelompok\n(Judul Baris | PK: id)"]
    IND --> PKL["m_periode_kelompok\n(Periode Waktu | PK: id)"]

    KK -->|"1 : N"| KI["m_karakteristik_item\n(Item Kolom | FK: id_kelompok, parent_id)"]
    BK -->|"1 : N"| BI["m_baris_item\n(Item Baris | FK: id_kelompok, parent_id)"]
    PKL -->|"1 : N"| PI["m_periode_item\n(Item Periode | FK: id_kelompok)"]

    KI -->|"id_karakteristik_item"| VAL["t_nilai_dinamis\n(PK: id | FK: id_indikator, tahun_data, id_baris_item, id_karakteristik_item, id_periode_item, nilai, simbol)"]
    BI -->|"id_baris_item"| VAL
    PI -->|"id_periode_item"| VAL

    VAL -->|"status periksa & publish"| ST["t_status_tahunan\n(PK: id | FK: id_tabel, tahun | is_periksa, is_confirm, is_publish)"]
    API -->|"status periksa & publish"| ST

    style TOP fill:transparent,stroke:transparent
```

> [!NOTE]
> Secara skema fisik database, `t_status_tahunan` berelasi ke `m_list_tabel` melalui `id_tabel` dengan kunci unik `(id_tabel, tahun)`. Panah dari `t_nilai_dinamis` dan `m_konfigurasi_api` ke `t_status_tahunan` menunjukkan alur proses bahwa kedua jalur sumber data tersebut bermuara pada status periksa, konfirmasi, dan publikasi tahunan.
