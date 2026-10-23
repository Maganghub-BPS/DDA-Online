# Blueprint & Dokumen Implementasi Teknis: Drag & Drop Reorder Dimensi Baris Bertingkat

Dokumen ini memuat arsitektur teknis, desain antarmuka, dan kode implementasi siap pakai untuk fitur **Drag & Drop Ubah Urutan Baris/Kolom** pada sistem DDA Online (Jateng Dalam Angka).

Fitur ini dirancang berdasarkan dua keputusan arsitektur utama:
1. **Pilihan 1 (Hierarchical Cluster Drag):** Saat baris induk digeser, seluruh anak-anaknya (*sub-items*) otomatis ikut berpindah bersamanya sebagai satu kesatuan kelompok.
2. **Opsi A (Draft in Client + Batch Submit):** Penataan urutan dilakukan secara visual di sisi antarmuka (*client-side*), dan perubahan baru disimpan permanen ke database saat pengguna menekan tombol oranye **"Simpan Baris"** (alur eksisting tanpa risiko perubahan data tidak sengaja).

---

## 1. Arsitektur & Alur Data (Data Flow)

```
[ Pengguna Drag & Drop Baris / Induk ]
                 │
                 ▼
[ DOM Reordered & Event 'stop' Terpicu ]
                 │
                 ▼
[ syncItemsOrderFromDOM() Menyusun Ulang Array In-Memory 'this.items' ]
                 │
                 ▼
[ notify() Dipanggil Otomatis ]
 ├──► Memperbarui Live Table Preview di bawah modal secara Real-Time
 └──► Memperbarui Nilai Input Tersembunyi (Hidden JSON: '#manage_baris_json')
                 │
                 ▼ (Pengguna Puas & Klik Tombol)
[ Tombol Oranye "Simpan Baris" Diklik ]
                 │
                 ▼
[ Form POST: /admin/master_dimensi/sync_baris_items ]
                 │
                 ▼
[ Controller Admin::sync_baris_items ]
 └──► Membaca $items_json yang telah tersusun sesuai urutan baru
                 │
                 ▼
[ Model M_tabeldinamis::syncBarisFromTreeItems ]
 ├──► Memberi nilai baru: no_urut = 1, 2, 3, ... (sesuai urutan array)
 ├──► Mempertahankan Primary Key ID fisik lama ($matchedId)
 └──► Database Transaction (UPDATE no_urut pada m_baris_item)
                 │
                 ▼
[ Nilai Statistik di t_nilai_dinamis Tetap 100% Utuh & Terikat pada ID ]
```

---

## 2. Mengapa Integritas Data 100% Terjamin?

1. **Relasi Foreign Key ID Fisik:**
   Tabel `t_nilai_dinamis` menyimpan angka statistik menggunakan referensi `id_baris_item` (kunci primer `id` dari `m_baris_item`), **bukan** nomor urut.
2. **Kecocokan ID Terjaga:**
   Saat `syncBarisItems` dijalankan, sistem mencocokkan `db_id` item yang sudah ada:
   ```php
   // cuplikan dari M_tabeldinamis.php:
   if ($matchedId !== null && isset($existingById[$matchedId])) {
       // UPDATE baris yang sudah ada (Pertahankan ID agar t_nilai_dinamis TIDAK PUTUS!)
       $this->db->table('m_baris_item')->where('id', $matchedId)->update($rowPayload);
   }
   ```
   Karena ID fisik baris tidak berubah, seluruh angka statistik yang telah diunggah oleh OPD/instansi tetap terikat pada baris yang benar. Saat tabel dibuka di web atau diunduh ke Excel, baris tersebut tampil di posisi barunya bersama seluruh nilai datanya secara akurat.

---

## 3. Komponen Desain & Gaya Visual (CSS)

Tambahkan aturan CSS berikut pada bagian `<style>` di [app/Views/admin/l_master_dimensi.php](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php):

```css
/* =========================================================================
   GAYA VISUAL DRAG & DROP BARIS BERTINGKAT
   ========================================================================= */

/* Ikon Grip / Pegangan Drag */
.drag-handle-group,
.drag-handle-sub {
    cursor: grab;
    color: #94a3b8;
    padding: 4px 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.drag-handle-group:hover {
    color: #2563eb;
    background: #eff6ff;
}

.drag-handle-sub:hover {
    color: #0891b2;
    background: #ecfeff;
}

.drag-handle-group:active,
.drag-handle-sub:active {
    cursor: grabbing;
}

/* Pembungkus Grup Induk & Anak (Pilihan 1: Satu Kesatuan) */
.tree-group-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.tree-group-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

/* Kotak Penampung Anak (Sub-Items Container) */
.tree-sub-container {
    padding-left: 28px;
    padding-right: 8px;
    padding-bottom: 6px;
    min-height: 2px;
}

/* Garis Penanda Lokasi Drop (Placeholder) */
.tree-sortable-placeholder {
    border: 2px dashed #3b82f6 !important;
    background: #eff6ff !important;
    border-radius: 8px !important;
    margin-bottom: 8px !important;
    visibility: visible !important;
    height: 48px !important;
}

.tree-sub-sortable-placeholder {
    border: 2px dashed #06b6d4 !important;
    background: #f0fdfa !important;
    border-radius: 6px !important;
    margin-bottom: 6px !important;
    visibility: visible !important;
    height: 42px !important;
}

/* Item Sedang Di-Drag (Helper Efek Melayang) */
.ui-sortable-helper {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    opacity: 0.95 !important;
    transform: scale(1.01) !important;
    background: #ffffff !important;
    border-color: #3b82f6 !important;
}
```

---

## 4. Modifikasi JavaScript pada `TreeDimensionManager`

Berikut adalah blok fungsi dan pembaruan class `TreeDimensionManager` pada [app/Views/admin/l_master_dimensi.php](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Views/admin/l_master_dimensi.php):

### A. Penyesuaian Method `render()`
Ubah perenderan DOM agar setiap baris Level 0 (Induk) membungkus anak-anaknya di dalam kontainer `.tree-group-card`:

```javascript
        render() {
            const self = this;
            this.listEl.empty();

            if (this.items.length === 0) {
                this.listEl.html('<div class="text-center py-4 text-muted fst-italic small"><i class="bi bi-inbox d-block mb-1 fs-5 opacity-50"></i>Belum ada item. Klik <strong>+ Tambah Item Baru</strong> di bawah.</div>');
                return;
            }

            // Susun item dalam struktur kelompok (Induk Level 0 membawa anak Level > 0)
            let currentGroupEl = null;
            let currentSubContainer = null;

            this.items.forEach(function(item) {
                let placeholderInd = 'Indonesia';
                let placeholderEn  = 'English (opsional)';
                let leftBadgeHtml  = '';

                if (self.type === 'baris') {
                    const currentRole = item.tipe_baris || (item.isTotal ? 'total' : 'data');
                    let roleIcon = '📊';
                    let roleLabel = 'Data';
                    let roleClass = 'role-data';

                    if (currentRole === 'kategori') {
                        roleIcon = '🏷️';
                        roleLabel = 'Kategori';
                        roleClass = 'role-kategori';
                        placeholderInd = 'Judul Kategori / Sekat (Indonesia)';
                        placeholderEn  = 'Category Header (English)';
                    } else if (currentRole === 'subtotal') {
                        roleIcon = '∑₍ₛ₎';
                        roleLabel = 'Subtotal';
                        roleClass = 'role-subtotal';
                        placeholderInd = 'Subtotal (Indonesia)';
                        placeholderEn  = 'Subtotal (English)';
                    } else if (currentRole === 'total') {
                        roleIcon = '∑';
                        roleLabel = 'Total';
                        roleClass = 'role-total';
                        placeholderInd = 'Grand Total (Indonesia)';
                        placeholderEn  = 'Grand Total (English)';
                    } else {
                        roleIcon = '📊';
                        roleLabel = 'Data';
                        roleClass = 'role-data';
                        placeholderInd = `Level ${item.level + 1} (Indonesia)`;
                        placeholderEn  = `Level ${item.level + 1} (English)`;
                    }

                    leftBadgeHtml = `
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-role-badge ${roleClass} dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Ubah status fungsional baris">
                                <span>${roleIcon}</span> <span class="ms-1">${roleLabel}</span>
                            </button>
                            <ul class="dropdown-menu role-dropdown-menu shadow-sm">
                                <li><a class="dropdown-item ${currentRole === 'data' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="data" href="#"><span>📊</span><span>Baris Data</span>${currentRole === 'data' ? '<i class="bi bi-check2 ms-auto text-success fw-bold"></i>' : ''}</a></li>
                                <li><a class="dropdown-item ${currentRole === 'kategori' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="kategori" href="#"><span>🏷️</span><span>Kategori</span>${currentRole === 'kategori' ? '<i class="bi bi-check2 ms-auto text-warning fw-bold"></i>' : ''}</a></li>
                                <li><a class="dropdown-item ${currentRole === 'subtotal' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="subtotal" href="#"><span class="fw-bold">∑₍ₛ₎</span><span>Subtotal</span>${currentRole === 'subtotal' ? '<i class="bi bi-check2 ms-auto text-primary fw-bold"></i>' : ''}</a></li>
                                <li><a class="dropdown-item ${currentRole === 'total' ? 'active' : ''} set-role d-flex align-items-center gap-2" data-role="total" href="#"><span class="fw-bold">∑</span><span>Total</span>${currentRole === 'total' ? '<i class="bi bi-check2 ms-auto text-danger fw-bold"></i>' : ''}</a></li>
                            </ul>
                        </div>
                    `;
                } else if (self.allowSub) {
                    const lvlNum = item.level + 1;
                    placeholderInd = `Level ${lvlNum} (Indonesia)`;
                    placeholderEn  = `Level ${lvlNum} (English)`;
                    const badgeClasses = ['text-primary', 'text-info', 'text-success', 'text-warning', 'text-purple', 'text-secondary'];
                    const bColor = badgeClasses[item.level] || 'text-secondary';
                    leftBadgeHtml = `<span class="badge bg-light ${bColor} border small" style="font-size: 0.72rem; min-width: 52px; text-align: center;">Level ${lvlNum}</span>`;
                }

                const isTotalActive    = (self.type === 'baris' && item.tipe_baris === 'total');
                const isKategoriActive = (self.type === 'baris' && item.tipe_baris === 'kategori');
                const isSubtotalActive = (self.type === 'baris' && item.tipe_baris === 'subtotal');

                let rowExtraClass = '';
                if (isTotalActive) rowExtraClass = 'is-total-row';
                else if (isKategoriActive) rowExtraClass = 'is-kategori-row';
                else if (isSubtotalActive) rowExtraClass = 'is-subtotal-row';

                const rowClass = `tree-item-row level-${item.level} ${self.theme} ${rowExtraClass}`;

                // Handle Drag: Level 0 menggunakan drag-handle-group, Level > 0 menggunakan drag-handle-sub
                const isLevelZero = (item.level === 0);
                const dragHandleHtml = isLevelZero 
                    ? `<span class="drag-handle-group" title="Geser kelompok baris ini (beserta seluruh anak-anaknya)"><i class="bi bi-grip-vertical fs-6"></i></span>`
                    : `<span class="drag-handle-sub" title="Geser urutan sub-baris ini"><i class="bi bi-grip-vertical fs-6"></i></span>`;

                const row = $(`
                    <div class="${rowClass}" data-id="${item.id}">
                        <div class="d-flex align-items-center gap-2">
                            ${dragHandleHtml}
                            <div class="tree-badge-container">${leftBadgeHtml}</div>
                            <div class="tree-item-inputs">
                                <input type="text" class="form-control form-control-sm item-input-ind ${isTotalActive || isKategoriActive || isSubtotalActive ? 'fw-bold text-dark' : ''}" placeholder="${placeholderInd}" value="${escapeHtml(item.ind)}">
                                <input type="text" class="form-control form-control-sm item-input-en ${isTotalActive || isKategoriActive || isSubtotalActive ? 'fw-bold text-dark' : ''}" placeholder="${placeholderEn}" value="${escapeHtml(item.en)}">
                            </div>
                            <div class="tree-item-actions">
                                ${(self.allowSub && item.level < self.maxDepth && !isTotalActive && !isSubtotalActive) ? `<button type="button" class="btn btn-sm btn-sub-badge btn-add-sub" title="Tambah Sub-Level"><i class="bi bi-plus-lg"></i> Sub</button>` : ''}
                                <button type="button" class="btn btn-sm btn-delete-item btn-remove" title="Hapus"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </div>
                    </div>
                `);

                // Event listener interaktif per baris
                row.find('.set-role').on('click', function(e) {
                    e.preventDefault();
                    item.tipe_baris = $(this).data('role');
                    item.isTotal = (item.tipe_baris === 'total');
                    item.isUserToggled = true;
                    self.render();
                    self.notify();
                });

                row.find('.item-input-ind').on('input', function() {
                    item.ind = $(this).val();
                    self.notify();
                });

                row.find('.item-input-en').on('input', function() {
                    item.en = $(this).val();
                    self.notify();
                });

                row.find('.btn-add-sub').on('click', function() {
                    self.addItem(item.id, item.level + 1);
                });

                row.find('.btn-remove').on('click', function() {
                    self.removeItem(item.id);
                });

                // Susun DOM Hierarki (Pilihan 1: Induk & Anak dalam satu Wrapper Grup)
                if (isLevelZero) {
                    currentGroupEl = $('<div class="tree-group-card"></div>');
                    currentGroupEl.append(row);
                    currentSubContainer = $('<div class="tree-sub-container"></div>');
                    currentGroupEl.append(currentSubContainer);
                    self.listEl.append(currentGroupEl);
                } else {
                    if (currentSubContainer) {
                        currentSubContainer.append(row);
                    } else {
                        // Fallback jika item pertama adalah level > 0
                        currentGroupEl = $('<div class="tree-group-card"></div>');
                        currentGroupEl.append(row);
                        self.listEl.append(currentGroupEl);
                    }
                }
            });

            // Aktifkan Drag & Drop Sortable setelah DOM selesai dirender
            this.initSortable();
        }
```

### B. Method Baru: `initSortable()` & `syncItemsOrderFromDOM()`
Tambahkan dua method ini di dalam class `TreeDimensionManager`:

```javascript
        /**
         * Mengaktifkan jQuery UI Sortable untuk Induk (Grup) dan Anak (Sub-items)
         */
        initSortable() {
            const self = this;

            // 1. Sortable Level Induk: Menggeser seluruh grup (induk + anak)
            this.listEl.sortable({
                handle: '.drag-handle-group',
                items: '> .tree-group-card',
                axis: 'y',
                containment: 'parent',
                tolerance: 'pointer',
                placeholder: 'tree-sortable-placeholder',
                forcePlaceholderSize: true,
                stop: function() {
                    self.syncItemsOrderFromDOM();
                }
            });

            // 2. Sortable Level Anak: Menggeser urutan sub-baris di dalam induk yang sama
            this.listEl.find('.tree-sub-container').sortable({
                handle: '.drag-handle-sub',
                items: '> .tree-item-row',
                axis: 'y',
                containment: 'parent',
                tolerance: 'pointer',
                placeholder: 'tree-sub-sortable-placeholder',
                forcePlaceholderSize: true,
                stop: function() {
                    self.syncItemsOrderFromDOM();
                }
            });
        }

        /**
         * Membaca urutan DOM terkini dari atas ke bawah,
         * menyusun ulang array this.items, dan memicu notify()
         */
        syncItemsOrderFromDOM() {
            const itemMap = {};
            this.items.forEach(it => { itemMap[it.id] = it; });

            const newItems = [];
            let lastLevelZeroId = null;

            // Telusuri seluruh baris dari atas ke bawah persis seperti yang tampil di layar
            this.listEl.find('.tree-item-row').each(function() {
                const id = $(this).data('id');
                const it = itemMap[id];
                if (it) {
                    if (it.level === 0) {
                        lastLevelZeroId = it.id;
                        it.parentId = null;
                    } else {
                        it.parentId = lastLevelZeroId;
                    }
                    newItems.push(it);
                }
            });

            this.items = newItems;

            // Otomatis memperbarui Live Table Preview di bawah modal dan hidden input JSON
            this.notify();
        }
```

---

## 5. Alur Backend (Tidak Perlu Ubah Kode Tambahan)

Alur pada sisi PHP di [app/Controllers/Admin.php](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Controllers/Admin.php#L4842) dan [app/Models/M_tabeldinamis.php](file:///e:/BPS%20JATENG/DDA/dda-online-ci4-tabel-dinamis/app/Models/M_tabeldinamis.php#L789) sudah kompatibel secara sempurna:

1. Saat modal di-submit melalui tombol **"Simpan Baris"**:
   * Input tersembunyi `items_json` dikirim ke aksi `admin/master_dimensi/sync_baris_items`.
   * Controller memanggil:
     ```php
     $mTabelDinamis->syncBarisFromTreeItems($idKelompok, $decodedJson);
     ```
2. Method `syncBarisFromTreeItems` melakukan loop:
   ```php
   $no = 1;
   foreach ($treeItems as $it) {
       $items[] = [
           'db_id'   => $it['db_id'],
           'no_urut' => $no++, // Nomor urut baru 1, 2, 3... otomatis mengikuti hasil drag
           ...
       ];
   }
   $this->syncBarisItems($id_kelompok, $items);
   ```
3. Database meng-*update* kolom `no_urut` secara berurutan di dalam transaksi aman (`transStart` / `transComplete`).

---

## 6. Skenario Pengujian (Test Plan) Saat Dijalankan Nanti

| No | Kasus Uji | Langkah Aksi | Hasil yang Diharapkan |
| :- | :--- | :--- | :--- |
| 1 | **Drag Baris Induk Tunggal** | Tarik pegangan grip pada baris Level 0 tanpa anak ke atas atau ke bawah. | Baris berpindah posisi, dan tabel pratinjau di bawah langsung mencerminkan urutan baru. |
| 2 | **Drag Induk Beranak (Pilihan 1)** | Tarik pegangan grip pada baris Level 0 yang memiliki 2 anak Level 1. | Baris induk beserta kedua anaknya berpindah bersamaan sebagai satu blok utuh. |
| 3 | **Drag Sub-Item di Dalam Induk** | Tarik pegangan grip pada sub-baris ke-2 ke atas sub-baris ke-1 di bawah induk yang sama. | Hanya urutan kedua sub-baris yang bertukar posisi; induk tidak terpengaruh. |
| 4 | **Validasi Tombol Simpan (Opsi A)** | Setelah selesai menggeser beberapa baris, klik tombol oranye **"Simpan Baris"**. | Data tersimpan dengan sukses, muncul alert hijau, dan urutan tetap sama saat modal dibuka kembali. |
| 5 | **Verifikasi Nilai Angka Eksisting** | Buka halaman tabel dinamis yang sudah terisi angka data statistik. | Data angka tidak tertukar dan tetap melekat pada nama baris yang bersangkutan. |
