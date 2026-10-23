<?php

namespace App\Models;

use CodeIgniter\Model;

class M_tabeldinamis extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_indikator_tabel';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = [
        'id_tahun_tabel',
        'no_tabel',
        'judul_ind',
        'judul_en',
        'id_unitkerja',
        'id_karakteristik_kelompok',
        'id_baris_kelompok',
        'id_periode_kelompok',
        'posisi_hirarki',
        'tingkat_kolom',
        'konfigurasi_total',
        'label_total_kustom',
        'tampilan_baris',
        'desimal',
        'catatan',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps    = true;

    // =========================================================================
    // 1. DIMENSI KARAKTERISTIK (KOLOM & SUB-KOLOM ASIMETRIS)
    // =========================================================================

    public function getKarakteristikKelompokAll(): array
    {
        return $this->db->table('m_karakteristik_kelompok k')
            ->select('k.*, COUNT(i.id) AS total_items')
            ->join('m_karakteristik_item i', 'k.id = i.id_kelompok', 'left')
            ->groupBy('k.id')
            ->orderBy('k.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getKarakteristikKelompok(int $id): ?object
    {
        return $this->db->table('m_karakteristik_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function saveKarakteristikKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_karakteristik_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deleteKarakteristikKelompok(int $id): bool
    {
        $this->db->transStart();
        $this->db->table('m_karakteristik_item')->where('id_kelompok', $id)->delete();
        $this->db->table('m_karakteristik_kelompok')->where('id', $id)->delete();
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function getKarakteristikItems(int $id_kelompok): array
    {
        $items = $this->db->table('m_karakteristik_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        foreach ($items as $item) {
            $item->is_standalone = (int)($item->is_standalone ?? 0);
        }

        return $items;
    }

    public function getKarakteristikCounts(): array
    {
        $rows = $this->db->table('m_karakteristik_item')
            ->select('id_kelompok, COUNT(*) as total')
            ->groupBy('id_kelompok')
            ->get()
            ->getResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->id_kelompok] = (int)$r->total;
        }
        return $map;
    }

    /**
     * Sinkronisasi item karakteristik (kolom) dari array tree builder
     * Mempertahankan db_id dari node leaf agar relasi t_nilai_dinamis tetap utuh saat edit teks/typo
     */
    public function syncKarakteristikFromTreeItems(int $id_kelompok, array $treeItems): bool
    {
        if (empty($treeItems)) {
            return $this->syncKarakteristikItems($id_kelompok, []);
        }

        // 1. Petakan parentId untuk mencari node anak vs node induk
        $hasChildren = [];
        $nodeMap = [];
        foreach ($treeItems as $it) {
            $nodeId = $it['id'] ?? '';
            if ($nodeId !== '') {
                $nodeMap[$nodeId] = $it;
            }
            $pId = $it['parentId'] ?? null;
            if (!empty($pId)) {
                $hasChildren[$pId] = true;
            }
        }

        $items = [];
        $no = 1;

        foreach ($treeItems as $it) {
            $nodeId = $it['id'] ?? '';
            // Hanya node daun (leaf) yang disimpan sebagai item kolom di m_karakteristik_item
            if (isset($hasChildren[$nodeId])) {
                continue;
            }

            $chain = [$it];
            $curr = $it;
            while (!empty($curr['parentId']) && isset($nodeMap[$curr['parentId']])) {
                $curr = $nodeMap[$curr['parentId']];
                array_unshift($chain, $curr);
            }

            $grup_induk_ind = null;
            $grup_induk_en  = null;
            $grup_sub_ind   = null;
            $grup_sub_en    = null;
            $item_ind = '';
            $item_en  = null;

            $totalSegs = count($chain);
            if ($totalSegs >= 3) {
                $grup_induk_ind = trim($chain[0]['ind'] ?? '');
                $grup_induk_en  = !empty($chain[0]['en']) ? trim($chain[0]['en']) : null;
                $grup_sub_ind   = trim($chain[1]['ind'] ?? '');
                $grup_sub_en    = !empty($chain[1]['en']) ? trim($chain[1]['en']) : null;
                $leafPartsInd   = [];
                $leafPartsEn    = [];
                for ($k = 2; $k < $totalSegs; $k++) {
                    $leafPartsInd[] = trim($chain[$k]['ind'] ?? '');
                    if (!empty($chain[$k]['en'])) {
                        $leafPartsEn[] = trim($chain[$k]['en']);
                    }
                }
                $item_ind = implode(' || ', $leafPartsInd);
                $item_en  = !empty($leafPartsEn) ? implode(' || ', $leafPartsEn) : null;
            } elseif ($totalSegs === 2) {
                $grup_induk_ind = trim($chain[0]['ind'] ?? '');
                $grup_induk_en  = !empty($chain[0]['en']) ? trim($chain[0]['en']) : null;
                $item_ind       = trim($chain[1]['ind'] ?? '');
                $item_en        = !empty($chain[1]['en']) ? trim($chain[1]['en']) : null;
            } else {
                $item_ind = trim($chain[0]['ind'] ?? '');
                $item_en  = !empty($chain[0]['en']) ? trim($chain[0]['en']) : null;
            }

            if ($item_ind === '') {
                continue;
            }

            $is_standalone = 0;
            $lower_ind = strtolower($item_ind);
            $lower_en  = strtolower($item_en ?? '');
            if ($lower_ind === 'total' || $lower_ind === 'jumlah' || $lower_en === 'total') {
                $is_standalone = 1;
            }

            $items[] = [
                'db_id'          => !empty($it['db_id']) ? (int)$it['db_id'] : null,
                'grup_induk_ind' => $grup_induk_ind ?: null,
                'grup_induk_en'  => $grup_induk_en ?: null,
                'grup_sub_ind'   => $grup_sub_ind ?: null,
                'grup_sub_en'    => $grup_sub_en ?: null,
                'item_ind'       => $item_ind,
                'item_en'        => $item_en ?: null,
                'is_standalone'  => $is_standalone,
                'no_urut'        => $no++,
            ];
        }

        return $this->syncKarakteristikItems($id_kelompok, $items);
    }

    public function syncKarakteristikItems(int $id_kelompok, array $items): bool
    {
        $this->db->transStart();

        $existingRows = $this->db->table('m_karakteristik_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $existingById = [];
        $existingByComp = [];
        foreach ($existingRows as $row) {
            $existingById[(int)$row['id']] = $row;
            $compKey = mb_strtolower(trim($row['grup_induk_ind'] ?? '')) . '|' . mb_strtolower(trim($row['grup_sub_ind'] ?? '')) . '|' . mb_strtolower(trim($row['item_ind']));
            if (!isset($existingByComp[$compKey])) {
                $existingByComp[$compKey] = (int)$row['id'];
            }
        }

        if (empty($items)) {
            if (!empty($existingById)) {
                $deleteIds = array_keys($existingById);
                $hasDataCount = $this->db->table('t_nilai_dinamis')
                    ->whereIn('id_karakteristik_item', $deleteIds)
                    ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                    ->countAllResults();

                if ($hasDataCount > 0) {
                    $this->db->transRollback();
                    throw new \RuntimeException("Gagal mengosongkan item kolom: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan kolom ini. Kosongkan nilai tabel terlebih dahulu.");
                }

                $this->db->table('m_karakteristik_item')->whereIn('id', $deleteIds)->delete();
                $this->db->table('t_nilai_dinamis')->whereIn('id_karakteristik_item', $deleteIds)->delete();
            }
            $this->db->transComplete();
            return $this->db->transStatus();
        }

        $now = date('Y-m-d H:i:s');
        $keptIds = [];
        $no = 1;

        foreach ($items as $item) {
            $raw_ind = trim($item['item_ind'] ?? '');
            $raw_en  = trim($item['item_en'] ?? '');

            // Bersihkan jika raw_en hanya berupa '||' atau spasi tanpa karakter teks
            if (preg_replace('/[\s\|]/', '', $raw_en) === '') {
                $raw_en = '';
            }

            if ($raw_ind === '') {
                continue;
            }

            // Jika teks Indonesia mengandung pembatas dwibahasa '//'
            if (strpos($raw_ind, '//') !== false && empty($raw_en)) {
                $parts_lang = array_map('trim', explode('//', $raw_ind, 2));
                $raw_ind = $parts_lang[0];
                $raw_en  = $parts_lang[1] ?? '';
            }

            // Ekstraksi notasi Induk || Anak hingga 3 tingkatan (Tingkat 1 || Tingkat 2 || Tingkat 3)
            $grup_induk_ind = $item['grup_induk_ind'] ?? null;
            $grup_induk_en  = $item['grup_induk_en'] ?? null;
            $grup_sub_ind   = $item['grup_sub_ind'] ?? null;
            $grup_sub_en    = $item['grup_sub_en'] ?? null;
            $item_ind       = $item['item_ind'] ?? $raw_ind;
            $item_en        = ($raw_en !== '') ? $raw_en : ($item['item_en'] ?? null);
            $is_standalone  = (int)($item['is_standalone'] ?? 0);

            if (empty($grup_induk_ind) && strpos($raw_ind, '||') !== false) {
                $parts_ind = array_map('trim', explode('||', $raw_ind));
                $parts_en  = (!empty($raw_en) && strpos($raw_en, '||') !== false) 
                    ? array_map('trim', explode('||', $raw_en)) 
                    : [];

                if (count($parts_ind) >= 3) {
                    $grup_induk_ind = $parts_ind[0];
                    $grup_sub_ind   = $parts_ind[1];
                    $item_ind       = implode(' || ', array_slice($parts_ind, 2));

                    $grup_induk_en  = (!empty($parts_en[0])) ? $parts_en[0] : null;
                    $grup_sub_en    = (!empty($parts_en[1])) ? $parts_en[1] : null;
                    $item_en        = (!empty($parts_en) && count($parts_en) >= 3)
                        ? implode(' || ', array_slice($parts_en, 2))
                        : ((isset($parts_en[2]) && $parts_en[2] !== '') ? $parts_en[2] : null);
                } elseif (count($parts_ind) === 2) {
                    $grup_induk_ind = $parts_ind[0];
                    $grup_sub_ind   = null;
                    $item_ind       = $parts_ind[1];

                    $grup_induk_en  = (!empty($parts_en[0])) ? $parts_en[0] : null;
                    $grup_sub_en    = null;
                    $item_en        = (isset($parts_en[1]) && $parts_en[1] !== '') ? $parts_en[1] : null;
                } else {
                    $item_ind       = $parts_ind[0];
                    $item_en        = (isset($parts_en[0]) && $parts_en[0] !== '') ? $parts_en[0] : null;
                }
            }

            if (empty($is_standalone)) {
                $lower_ind = strtolower($item_ind);
                $lower_en  = strtolower($item_en ?? '');
                if ($lower_ind === 'total' || $lower_ind === 'jumlah' || $lower_en === 'total') {
                    $is_standalone = 1;
                }
            }

            // Matching item existing
            $matchedId = null;
            $candidateId = (int)($item['db_id'] ?? ($item['id'] ?? 0));
            if ($candidateId > 0 && isset($existingById[$candidateId])) {
                $matchedId = $candidateId;
            } else {
                $compKey = mb_strtolower(trim($grup_induk_ind ?? '')) . '|' . mb_strtolower(trim($grup_sub_ind ?? '')) . '|' . mb_strtolower(trim($item_ind));
                if (isset($existingByComp[$compKey])) {
                    $matchedId = $existingByComp[$compKey];
                    unset($existingByComp[$compKey]);
                }
            }

            $payload = [
                'id_kelompok'    => $id_kelompok,
                'grup_induk_ind' => $grup_induk_ind,
                'grup_induk_en'  => $grup_induk_en,
                'grup_sub_ind'   => $grup_sub_ind,
                'grup_sub_en'    => $grup_sub_en,
                'item_ind'       => $item_ind,
                'item_en'        => $item_en,
                'no_urut'        => (int)($item['no_urut'] ?? $no),
                'is_standalone'  => $is_standalone,
                'updated_at'     => $now,
            ];

            if ($matchedId !== null && isset($existingById[$matchedId])) {
                $this->db->table('m_karakteristik_item')->where('id', $matchedId)->update($payload);
                $keptIds[] = $matchedId;
            } else {
                $payload['created_at'] = $now;
                $this->db->table('m_karakteristik_item')->insert($payload);
                $keptIds[] = (int)$this->db->insertID();
            }

            $no++;
        }

        $deleteIds = array_diff(array_keys($existingById), $keptIds);
        if (!empty($deleteIds)) {
            $hasDataCount = $this->db->table('t_nilai_dinamis')
                ->whereIn('id_karakteristik_item', $deleteIds)
                ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                ->countAllResults();

            if ($hasDataCount > 0) {
                $this->db->transRollback();
                throw new \RuntimeException("Gagal menyimpan perubahan: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan item kolom yang dihapus. Kosongkan nilai tabel terlebih dahulu sebelum menghapus item kolom ini.");
            }

            $this->db->table('m_karakteristik_item')->whereIn('id', $deleteIds)->delete();
            $this->db->table('t_nilai_dinamis')->whereIn('id_karakteristik_item', $deleteIds)->delete();
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function buildColumnStructure(array $items): array
    {
        $hasGroupHeaders = false;
        $maxDepth = 1;

        // 1. Ekstraksi segmen hirarki untuk setiap kolom daun (leaf)
        $parsedItems = [];
        foreach ($items as $colIdx => $item) {
            $segments_ind = [];
            $segments_en  = [];

            if (!empty($item->grup_induk_ind) && empty($item->is_standalone)) {
                $segments_ind[] = trim($item->grup_induk_ind);
                $segments_en[]  = !empty($item->grup_induk_en) ? trim($item->grup_induk_en) : '';
            }

            if (!empty($item->grup_sub_ind)) {
                $segments_ind[] = trim($item->grup_sub_ind);
                $segments_en[]  = !empty($item->grup_sub_en) ? trim($item->grup_sub_en) : '';
            }

            $raw_ind = trim($item->item_ind ?? '');
            $raw_en  = trim($item->item_en ?? '');

            if (strpos($raw_ind, '||') !== false) {
                $parts_ind = array_map('trim', explode('||', $raw_ind));
                $parts_en  = (strpos($raw_en, '||') !== false) ? array_map('trim', explode('||', $raw_en)) : [];
                foreach ($parts_ind as $k => $p) {
                    $segments_ind[] = $p;
                    $segments_en[]  = isset($parts_en[$k]) ? $parts_en[$k] : '';
                }
            } else {
                $segments_ind[] = $raw_ind;
                $segments_en[]  = $raw_en;
            }

            $depth = count($segments_ind);
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }

            // Simpan label daun (leaf) murni tanpa rantai induknya
            $item->leaf_ind = end($segments_ind);
            $item->leaf_en  = !empty($segments_en) ? end($segments_en) : '';
            reset($segments_ind);

            $parsedItems[] = [
                'item'         => $item,
                'col_idx'      => $colIdx,
                'segments_ind' => $segments_ind,
                'segments_en'  => $segments_en,
                'depth'        => $depth,
            ];
        }

        if ($maxDepth > 1) {
            $hasGroupHeaders = true;
        }

        // 2. Bangun Prefix Trie Hirarki Kolom
        $root = [
            'title_ind' => '',
            'title_en'  => '',
            'children'  => [],
            'leaf_cols' => [],
        ];

        foreach ($parsedItems as $pItem) {
            $curr = &$root;
            $currPath = '';
            $depth = $pItem['depth'];

            foreach ($pItem['segments_ind'] as $sIdx => $seg) {
                $currPath .= ($currPath === '' ? '' : ' > ') . $seg;
                $en = $pItem['segments_en'][$sIdx] ?? '';
                $isLeaf = ($sIdx === $depth - 1);

                if (!isset($curr['children'][$currPath])) {
                    $curr['children'][$currPath] = [
                        'key'       => $currPath,
                        'title_ind' => $seg,
                        'title_en'  => $en,
                        'level'     => $sIdx + 1,
                        'is_leaf'   => $isLeaf,
                        'item'      => $isLeaf ? $pItem['item'] : null,
                        'children'  => [],
                        'leaf_cols' => [],
                    ];
                }
                $curr['children'][$currPath]['leaf_cols'][] = $pItem['col_idx'];
                $curr = &$curr['children'][$currPath];
            }
            unset($curr);
        }

        // 3. Hitung row_start, row_end, rowspan, dan colspan secara universal
        $assignRows = function(&$node, int $rowStart, int $targetDepth) use (&$assignRows) {
            $node['row_start'] = $rowStart;
            if (empty($node['children'])) {
                // Node daun (leaf): membentang vertikal sampai baris terbawah
                $node['row_end'] = $targetDepth;
                $node['rowspan'] = $node['row_end'] - $node['row_start'] + 1;
                $node['colspan'] = count($node['leaf_cols']);
                return;
            }

            // Cek apakah seluruh anak langsungnya adalah node daun
            $allChildrenLeaves = true;
            foreach ($node['children'] as $child) {
                if (!empty($child['children'])) {
                    $allChildrenLeaves = false;
                    break;
                }
            }

            if ($allChildrenLeaves) {
                // Node induk langsung dari daun: membentang sampai baris (targetDepth - 1)
                $node['row_end'] = $targetDepth - 1;
                $node['rowspan'] = $node['row_end'] - $node['row_start'] + 1;
                $node['colspan'] = count($node['leaf_cols']);

                foreach ($node['children'] as &$child) {
                    $child['row_start'] = $targetDepth;
                    $child['row_end']   = $targetDepth;
                    $child['rowspan']   = 1;
                    $child['colspan']   = count($child['leaf_cols']);
                }
                unset($child);
            } else {
                // Node induk perantara: menempati 1 baris di tingkatannya
                $node['row_end'] = $rowStart;
                $node['rowspan'] = 1;
                $node['colspan'] = count($node['leaf_cols']);

                foreach ($node['children'] as &$child) {
                    $assignRows($child, $rowStart + 1, $targetDepth);
                }
                unset($child);
            }
        };

        foreach ($root['children'] as &$topNode) {
            $assignRows($topNode, 1, $maxDepth);
        }
        unset($topNode);

        // 4. Kumpulkan sel per baris header (1..maxDepth) dalam urutan kolom teratur
        $headerRows = [];
        for ($r = 1; $r <= $maxDepth; $r++) {
            $headerRows[$r] = [];
        }

        $collectCells = function($node) use (&$collectCells, &$headerRows) {
            if (isset($node['row_start'])) {
                $headerRows[$node['row_start']][] = [
                    'title_ind' => $node['title_ind'],
                    'title_en'  => $node['title_en'],
                    'row'       => $node['row_start'],
                    'rowspan'   => $node['rowspan'],
                    'colspan'   => $node['colspan'],
                    'is_leaf'   => $node['is_leaf'],
                    'item_id'   => $node['item'] ? (int)$node['item']->id : null,
                ];
            }
            foreach ($node['children'] as $child) {
                $collectCells($child);
            }
        };

        foreach ($root['children'] as $topNode) {
            $collectCells($topNode);
        }

        // 5. Bangun struktur kelompok warisan ($groups) untuk backward-compatibility 1..3 tingkat
        $groups = [];
        if ($maxDepth >= 3) {
            foreach ($items as $item) {
                $l1 = $item->grup_induk_ind;
                $l2 = $item->grup_sub_ind;

                if (!empty($l1)) {
                    if (!isset($groups[$l1])) {
                        $groups[$l1] = [
                            'type'      => 'group',
                            'title_ind' => $l1,
                            'title_en'  => $item->grup_induk_en ?? null,
                            'subgroups' => [],
                            'children'  => [],
                        ];
                    }
                    $groups[$l1]['children'][] = $item;

                    $subKey = !empty($l2) ? $l2 : '__standalone_' . $item->id;
                    if (!isset($groups[$l1]['subgroups'][$subKey])) {
                        $groups[$l1]['subgroups'][$subKey] = [
                            'type'      => !empty($l2) ? 'subgroup' : 'standalone',
                            'title_ind' => !empty($l2) ? $l2 : $item->item_ind,
                            'title_en'  => !empty($l2) ? ($item->grup_sub_en ?? null) : ($item->item_en ?? null),
                            'children'  => [],
                        ];
                    }
                    $groups[$l1]['subgroups'][$subKey]['children'][] = $item;
                } else {
                    $groups['__standalone_' . $item->id] = [
                        'type'      => 'standalone',
                        'title_ind' => $item->item_ind,
                        'title_en'  => $item->item_en ?? null,
                        'subgroups' => [],
                        'children'  => [$item],
                    ];
                }
            }

            foreach ($groups as &$grp) {
                if (!empty($grp['subgroups'])) {
                    $grp['subgroups'] = array_values($grp['subgroups']);
                }
            }
            unset($grp);
        } else {
            // maxDepth 1 atau 2
            foreach ($items as $item) {
                $parentName = $item->grup_induk_ind ?: ($item->is_standalone ? '' : null);

                if (!empty($parentName)) {
                    if (!isset($groups[$parentName])) {
                        $groups[$parentName] = [
                            'type'      => 'group',
                            'title_ind' => $parentName,
                            'title_en'  => $item->grup_induk_en ?? null,
                            'children'  => [],
                        ];
                    }
                    $groups[$parentName]['children'][] = $item;
                } else {
                    $groups['__standalone_' . $item->id] = [
                        'type'      => 'standalone',
                        'title_ind' => $item->item_ind,
                        'title_en'  => $item->item_en ?? null,
                        'item'      => $item,
                        'children'  => [$item],
                    ];
                }
            }
        }

        return [
            'max_depth'   => $maxDepth,
            'has_groups'  => $hasGroupHeaders,
            'header_rows' => $headerRows,
            'groups'      => array_values($groups),
            'flat_items'  => $items,
        ];
    }

    // =========================================================================
    // 2. DIMENSI BARIS (BARIS BERTINGKAT / HIERARKIS & INDENTASI)
    // =========================================================================

    public function getBarisKelompokAll(): array
    {
        return $this->db->table('m_baris_kelompok k')
            ->select('k.*, COUNT(i.id) AS total_items')
            ->join('m_baris_item i', 'k.id = i.id_kelompok', 'left')
            ->groupBy('k.id')
            ->orderBy('k.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getBarisKelompok(int $id): ?object
    {
        return $this->db->table('m_baris_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function saveBarisKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_baris_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deleteBarisKelompok(int $id): bool
    {
        $this->db->transStart();
        $this->db->table('m_baris_item')->where('id_kelompok', $id)->delete();
        $this->db->table('m_baris_kelompok')->where('id', $id)->delete();
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function getBarisItems(int $id_kelompok): array
    {
        return $this->db->table('m_baris_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function getBarisCounts(): array
    {
        $rows = $this->db->table('m_baris_item')
            ->select('id_kelompok, COUNT(*) as total')
            ->groupBy('id_kelompok')
            ->get()
            ->getResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->id_kelompok] = (int)$r->total;
        }
        return $map;
    }

    public function getBarisTree(int $id_kelompok): array
    {
        return $this->getBarisItems($id_kelompok);
    }

    public function syncBarisItems(int $id_kelompok, array $items): bool
    {
        $this->db->transStart();

        $builder = $this->db->table('m_baris_item');

        // 1. Ambil seluruh data baris existing untuk kelompok ini
        $existingRows = $builder->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $existingById = [];
        $existingByName = [];
        foreach ($existingRows as $row) {
            $existingById[(int)$row['id']] = $row;
            $norm = mb_strtolower(trim($row['nama_baris_ind']));
            if (!isset($existingByName[$norm])) {
                $existingByName[$norm] = (int)$row['id'];
            }
        }

        if (empty($items)) {
            if (!empty($existingById)) {
                $deleteIds = array_keys($existingById);
                $hasDataCount = $this->db->table('t_nilai_dinamis')
                    ->whereIn('id_baris_item', $deleteIds)
                    ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                    ->countAllResults();

                if ($hasDataCount > 0) {
                    $this->db->transRollback();
                    throw new \RuntimeException("Gagal mengosongkan kelompok baris: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan baris ini. Kosongkan nilai tabel terlebih dahulu.");
                }

                $this->db->table('m_baris_item')->whereIn('id', $deleteIds)->delete();
                $this->db->table('t_nilai_dinamis')->whereIn('id_baris_item', $deleteIds)->delete();
            }
            $this->db->transComplete();
            return $this->db->transStatus();
        }

        $now = date('Y-m-d H:i:s');
        $keptIds = [];
        $no = 1;

        foreach ($items as $item) {
            $nama_ind = trim($item['nama_baris_ind'] ?? '');
            $nama_en  = trim($item['nama_baris_en'] ?? '');

            if ($nama_ind === '') {
                continue;
            }

            $level_indentasi = (int)($item['level_indentasi'] ?? ($item['level'] ?? 0));
            $tipe_baris = $item['tipe_baris'] ?? '';
            $validTypes = ['kategori', 'data', 'subtotal', 'total'];
            
            // Evaluasi tipe baris cerdas & hormati penandaan is_total / tipe_baris dari pengguna
            $is_user_total = (!empty($item['is_total']) || $tipe_baris === 'total');
            $detectedTipe  = $this->detectTipeBaris($nama_ind, $level_indentasi);

            if (!empty($tipe_baris) && in_array($tipe_baris, $validTypes, true)) {
                // Prioritas tertinggi: pertahankan tipe fungsional yang eksplisit dipilih pengguna
                if (!empty($item['is_total']) && $tipe_baris !== 'data' && $tipe_baris !== 'kategori') {
                    $tipe_baris = 'total';
                }
            } elseif ($is_user_total || $detectedTipe === 'total') {
                $tipe_baris = 'total';
            } else {
                $tipe_baris = $detectedTipe;
            }

            $is_bold = !empty($item['is_bold']) ? 1 : ($tipe_baris === 'total' || $tipe_baris === 'kategori' ? 1 : 0);
            $noUrut  = (int)($item['no_urut'] ?? $no);

            // Deteksi pencocokan item existing agar ID baris TIDAK BERUBAH:
            // Prioritas 1: Kecocokan eksplisit db_id / id dari client
            // Prioritas 2: Kecocokan nama_baris_ind
            $matchedId = null;
            $candidateId = (int)($item['db_id'] ?? ($item['id'] ?? 0));
            if ($candidateId > 0 && isset($existingById[$candidateId])) {
                $matchedId = $candidateId;
            } else {
                $norm = mb_strtolower($nama_ind);
                if (isset($existingByName[$norm])) {
                    $matchedId = $existingByName[$norm];
                    unset($existingByName[$norm]); // Hindari pencocokan ganda
                }
            }

            $rowPayload = [
                'id_kelompok'     => $id_kelompok,
                'no_urut'         => $noUrut,
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => $nama_en !== '' ? $nama_en : null,
                'tipe_baris'      => $tipe_baris,
                'level_indentasi' => $level_indentasi,
                'is_bold'         => $is_bold,
                'updated_at'      => $now,
            ];

            if ($matchedId !== null && isset($existingById[$matchedId])) {
                // UPDATE baris yang sudah ada (Pertahankan ID agar t_nilai_dinamis TIDAK PUTUS!)
                $this->db->table('m_baris_item')->where('id', $matchedId)->update($rowPayload);
                $keptIds[] = $matchedId;
            } else {
                // INSERT baris baru (Dapat ID baru)
                $rowPayload['created_at'] = $now;
                $this->db->table('m_baris_item')->insert($rowPayload);
                $newId = (int)$this->db->insertID();
                $keptIds[] = $newId;
            }

            $no++;
        }

        // Hapus HANYA baris-baris yang memang dibuang oleh pengguna
        $deleteIds = array_diff(array_keys($existingById), $keptIds);
        if (!empty($deleteIds)) {
            $hasDataCount = $this->db->table('t_nilai_dinamis')
                ->whereIn('id_baris_item', $deleteIds)
                ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                ->countAllResults();

            if ($hasDataCount > 0) {
                $this->db->transRollback();
                throw new \RuntimeException("Gagal menyimpan perubahan: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan item baris yang dihapus. Kosongkan nilai tabel terlebih dahulu sebelum menghapus item baris ini.");
            }

            $this->db->table('m_baris_item')->whereIn('id', $deleteIds)->delete();
            $this->db->table('t_nilai_dinamis')->whereIn('id_baris_item', $deleteIds)->delete();
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function detectTipeBaris(string $namaInd, int $indent = 0, bool $hasChildren = false): string
    {
        $clean = trim(preg_replace('/^(\(?[A-Za-z0-9]+\)[\.\)]?|[A-Za-z0-9]+[\.\)]|[IVXLCDMivxlcdm]+[\.\)]|[–\-\•])\s+/', '', $namaInd));
        $lower = strtolower($clean);

        // 1. Cek pengecualian nama indikator/variabel statistik: "jumlah curah hujan", "jumlah hari hujan", dsb.
        if (strpos($lower, 'curah hujan') !== false || strpos($lower, 'hari hujan') !== false) {
            return 'data';
        }
        if (preg_match('/^jumlah\s+(curah|hari|penduduk|kasus|sekolah|guru|murid|jiwa|balita|kk|desa|kelurahan|sarana|penerima|armada|tindak|kejadian|korban|kelahiran|kematian|pasien|peserta|tenaga)/i', $lower)) {
            return 'data';
        }

        // 2. Grand Total: Jawa Tengah, Produk Domestik Regional Bruto (PDRB), Jumlah, Total, dsb.
        $totalTerms = [
            'jumlah', 'total', 'grand total', 'grandtotal', 
            'jumlah total', 'jumlah seluruhnya', 'total seluruhnya', 
            'produk domestik regional bruto', 'pdrb', 
            'gross regional domestic product', 'grdp', 'all'
        ];
        $base = trim(explode('/', explode('(', $lower)[0])[0]);

        if (
            $lower === 'jawa tengah' || 
            strpos($lower, 'jawa tengah') === 0 || 
            $lower === 'provinsi jawa tengah' || 
            $lower === 'prov. jawa tengah' || 
            $lower === 'central java' ||
            strpos($lower, 'produk domestik regional bruto') === 0 ||
            strpos($lower, 'pdrb') === 0 ||
            strpos($lower, 'gross regional domestic product') === 0 ||
            strpos($lower, 'grdp') === 0 ||
            in_array($lower, $totalTerms, true) || 
            in_array($base, $totalTerms, true) ||
            preg_match('/^(jumlah|total|grand\s*total)\s*(\/|\/\/)\s*(total|grand\s*total|jumlah)$/i', $lower)
        ) {
            return 'total';
        }

        // 3. Subtotal
        if (
            $base === 'subtotal' || $base === 'sub total' || $base === 'sub-total' || 
            strpos($lower, 'subtotal') === 0 || strpos($lower, 'sub total') === 0 ||
            strpos($lower, 'sub jumlah') === 0 || strpos($lower, 'sub-jumlah') === 0 ||
            strpos($base, 'jumlah ') === 0 || strpos($base, 'total ') === 0
        ) {
            return 'subtotal';
        }

        // 4. Kategori (Pembatas Bab/Kategori - khusus pembatas wilayah Kabupaten/Kota atau banner judul murni)
        if ($lower === 'kabupaten' || $lower === 'kota') {
            return 'kategori';
        }

        return 'data';
    }

    /**
     * Sinkronisasi item baris dari array tree manager (menjaga flag is_total yang dipilih pengguna)
     */
    public function syncBarisFromTreeItems(int $id_kelompok, array $treeItems): bool
    {
        $items = [];
        $no = 1;

        foreach ($treeItems as $it) {
            $nama_ind = trim($it['ind'] ?? ($it['nama_baris_ind'] ?? ''));
            $nama_en  = trim($it['en'] ?? ($it['nama_baris_en'] ?? ''));
            if ($nama_ind === '') {
                continue;
            }

            $lvl = (int)($it['level'] ?? ($it['level_indentasi'] ?? 0));
            $is_total = !empty($it['is_total']) || (($it['tipe_baris'] ?? '') === 'total');

            $items[] = [
                'db_id'           => !empty($it['db_id']) ? (int)$it['db_id'] : null,
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => !empty($nama_en) ? $nama_en : null,
                'level_indentasi' => $lvl,
                'tipe_baris'      => $is_total ? 'total' : ($it['tipe_baris'] ?? 'data'),
                'is_total'        => $is_total ? 1 : 0,
                'no_urut'         => $no++,
            ];
        }

        return $this->syncBarisItems($id_kelompok, $items);
    }

    public function parseBarisFromText(int $id_kelompok, string $text, string $text_en = ''): bool
    {
        $lines_ind = preg_split('/\r\n|\r|\n/', trim($text));
        $lines_en  = !empty($text_en) ? preg_split('/\r\n|\r|\n/', trim($text_en)) : [];
        $items = [];
        $no = 1;

        foreach ($lines_ind as $idx => $line) {
            $trimmed_ind = trim($line);
            if ($trimmed_ind === '') {
                continue;
            }

            $trimmed_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';

            // Jika baris Indonesia mengandung separator '//'
            if (strpos($trimmed_ind, '//') !== false && empty($trimmed_en)) {
                $parts = array_map('trim', explode('//', $trimmed_ind, 2));
                $trimmed_ind = $parts[0];
                $trimmed_en  = $parts[1] ?? '';
            }

            $indent = 0;
            $nama_ind = $trimmed_ind;
            $nama_en  = $trimmed_en;

            // Hitung indentasi pada teks Indonesia
            if (strpos($trimmed_ind, '----') === 0) {
                $indent = 2;
                $nama_ind = trim(substr($trimmed_ind, 4));
            } elseif (strpos($trimmed_ind, '--') === 0) {
                $indent = 1;
                $nama_ind = trim(substr($trimmed_ind, 2));
            } elseif (preg_match('/^(\s+)/', $line, $matches)) {
                $spaceCount = strlen($matches[1]);
                $indent = $spaceCount >= 4 ? 2 : 1;
            }

            // Bersihkan indentasi pada teks English jika ada
            if (strpos($nama_en, '----') === 0) {
                $nama_en = trim(substr($nama_en, 4));
            } elseif (strpos($nama_en, '--') === 0) {
                $nama_en = trim(substr($nama_en, 2));
            }

            $items[] = [
                'nama_baris_ind'  => $nama_ind,
                'nama_baris_en'   => !empty($nama_en) ? $nama_en : null,
                'level_indentasi' => $indent,
                'no_urut'         => $no++,
            ];
        }

        return $this->syncBarisItems($id_kelompok, $items);
    }

    // =========================================================================
    // 3. DIMENSI PERIODE WAKTU (TAHUN / BULAN)
    // =========================================================================

    public function getPeriodeKelompokAll(): array
    {
        return $this->db->table('m_periode_kelompok k')
            ->select('k.*, COUNT(i.id) AS total_items')
            ->join('m_periode_item i', 'k.id = i.id_kelompok', 'left')
            ->groupBy('k.id')
            ->orderBy('k.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getPeriodeKelompok(int $id): ?object
    {
        return $this->db->table('m_periode_kelompok')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function savePeriodeKelompok(array $data, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('m_periode_kelompok');

        if (!empty($id)) {
            $data['updated_at'] = $now;
            $builder->where('id', $id)->update($data);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $builder->insert($data);
            return (int)$this->db->insertID();
        }
    }

    public function deletePeriodeKelompok(int $id): bool
    {
        $this->db->transStart();
        $this->db->table('m_periode_item')->where('id_kelompok', $id)->delete();
        $this->db->table('m_periode_kelompok')->where('id', $id)->delete();
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function getPeriodeItems(int $id_kelompok): array
    {
        return $this->db->table('m_periode_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();
    }

    public function getPeriodeCounts(): array
    {
        $rows = $this->db->table('m_periode_item')
            ->select('id_kelompok, COUNT(*) as total')
            ->groupBy('id_kelompok')
            ->get()
            ->getResult();
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->id_kelompok] = (int)$r->total;
        }
        return $map;
    }

    /**
     * Sinkronisasi item periode dari array tree builder
     * Mempertahankan db_id agar relasi t_nilai_dinamis tetap utuh saat edit teks/typo periode
     */
    public function syncPeriodeFromTreeItems(int $id_kelompok, array $treeItems): bool
    {
        $items = [];
        $no = 1;
        foreach ($treeItems as $it) {
            $nama_ind = trim($it['ind'] ?? ($it['nama_periode_ind'] ?? ''));
            $nama_en  = trim($it['en'] ?? ($it['nama_periode_en'] ?? ''));
            if ($nama_ind === '') {
                continue;
            }

            $items[] = [
                'db_id'            => !empty($it['db_id']) ? (int)$it['db_id'] : null,
                'nama_periode_ind' => $nama_ind,
                'nama_periode_en'  => $nama_en !== '' ? $nama_en : null,
                'no_urut'          => $no++,
            ];
        }

        return $this->syncPeriodeItems($id_kelompok, $items);
    }

    public function syncPeriodeItems(int $id_kelompok, array $items): bool
    {
        $this->db->transStart();

        $existingRows = $this->db->table('m_periode_item')
            ->where('id_kelompok', $id_kelompok)
            ->orderBy('no_urut', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $existingById = [];
        $existingByName = [];
        foreach ($existingRows as $row) {
            $existingById[(int)$row['id']] = $row;
            $norm = mb_strtolower(trim($row['nama_periode_ind']));
            if (!isset($existingByName[$norm])) {
                $existingByName[$norm] = (int)$row['id'];
            }
        }

        if (empty($items)) {
            if (!empty($existingById)) {
                $deleteIds = array_keys($existingById);
                $hasDataCount = $this->db->table('t_nilai_dinamis')
                    ->whereIn('id_periode_item', $deleteIds)
                    ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                    ->countAllResults();

                if ($hasDataCount > 0) {
                    $this->db->transRollback();
                    throw new \RuntimeException("Gagal mengosongkan kelompok periode: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan kelompok periode ini. Kosongkan nilai tabel terlebih dahulu.");
                }

                $this->db->table('m_periode_item')->whereIn('id', $deleteIds)->delete();
                $this->db->table('t_nilai_dinamis')->whereIn('id_periode_item', $deleteIds)->delete();
            }
            $this->db->transComplete();
            return $this->db->transStatus();
        }

        $now = date('Y-m-d H:i:s');
        $keptIds = [];
        $no = 1;

        foreach ($items as $item) {
            $nama_ind = trim($item['nama_periode_ind'] ?? '');
            $nama_en  = trim($item['nama_periode_en'] ?? '');

            if ($nama_ind === '') {
                continue;
            }

            $matchedId = null;
            $candidateId = (int)($item['db_id'] ?? ($item['id'] ?? 0));
            if ($candidateId > 0 && isset($existingById[$candidateId])) {
                $matchedId = $candidateId;
            } else {
                $norm = mb_strtolower($nama_ind);
                if (isset($existingByName[$norm])) {
                    $matchedId = $existingByName[$norm];
                    unset($existingByName[$norm]);
                }
            }

            $payload = [
                'id_kelompok'      => $id_kelompok,
                'nama_periode_ind' => $nama_ind,
                'nama_periode_en'  => $nama_en !== '' ? $nama_en : null,
                'no_urut'          => (int)($item['no_urut'] ?? $no),
                'updated_at'       => $now,
            ];

            if ($matchedId !== null && isset($existingById[$matchedId])) {
                $this->db->table('m_periode_item')->where('id', $matchedId)->update($payload);
                $keptIds[] = $matchedId;
            } else {
                $payload['created_at'] = $now;
                $this->db->table('m_periode_item')->insert($payload);
                $keptIds[] = (int)$this->db->insertID();
            }

            $no++;
        }

        $deleteIds = array_diff(array_keys($existingById), $keptIds);
        if (!empty($deleteIds)) {
            $hasDataCount = $this->db->table('t_nilai_dinamis')
                ->whereIn('id_periode_item', $deleteIds)
                ->where('(nilai IS NOT NULL OR simbol IS NOT NULL)', null, false)
                ->countAllResults();

            if ($hasDataCount > 0) {
                $this->db->transRollback();
                throw new \RuntimeException("Gagal menyimpan perubahan: Terdapat {$hasDataCount} data nilai pada tabel dinamis yang menggunakan item periode yang dihapus. Kosongkan nilai tabel terlebih dahulu sebelum menghapus item periode ini.");
            }

            $this->db->table('m_periode_item')->whereIn('id', $deleteIds)->delete();
            $this->db->table('t_nilai_dinamis')->whereIn('id_periode_item', $deleteIds)->delete();
        }

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function generatePeriodeYearRange(int $id_kelompok, int $startYear, int $endYear, string $order = 'ASC'): bool
    {
        $years = range($startYear, $endYear);
        if (strtoupper($order) === 'DESC') {
            rsort($years);
        } else {
            sort($years);
        }

        $items = [];
        $no = 1;
        foreach ($years as $year) {
            $items[] = [
                'nama_periode_ind' => (string)$year,
                'nama_periode_en'  => null,
                'no_urut'          => $no++,
            ];
        }

        return $this->syncPeriodeItems($id_kelompok, $items);
    }

    public function parsePeriodeFromText(int $id_kelompok, string $text_ind, string $text_en = ''): bool
    {
        $splitPattern = '/\r\n|\r|\n/';
        if (strpos($text_ind, "\n") === false && strpos($text_ind, ',') !== false) {
            $splitPattern = '/,/';
        }

        $lines_ind = preg_split($splitPattern, trim($text_ind));
        $lines_en  = !empty($text_en) ? preg_split($splitPattern, trim($text_en)) : [];
        $items = [];
        $no = 1;

        foreach ($lines_ind as $idx => $part) {
            $trimmed_ind = trim($part);
            if ($trimmed_ind === '') {
                continue;
            }

            $trimmed_en = isset($lines_en[$idx]) ? trim($lines_en[$idx]) : '';

            // Cek jika teks Indonesia mengandung pemisah dwibahasa '//'
            if (strpos($trimmed_ind, '//') !== false && empty($trimmed_en)) {
                $parts_lang = array_map('trim', explode('//', $trimmed_ind, 2));
                $trimmed_ind = $parts_lang[0];
                $trimmed_en  = $parts_lang[1] ?? '';
            }

            $items[] = [
                'nama_periode_ind' => $trimmed_ind,
                'nama_periode_en'  => $trimmed_en !== '' ? $trimmed_en : null,
                'no_urut'          => $no++,
            ];
        }

        return $this->syncPeriodeItems($id_kelompok, $items);
    }

    // =========================================================================
    // 4. PERAKITAN INDIKATOR TABEL (GENERATOR UTAMA)
    // =========================================================================

    public function getIndikatorAll(): array
    {
        return $this->db->table('m_indikator_tabel')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getIndikator(int $id): ?object
    {
        return $this->find($id);
    }

    public function getTableFullConfig(int $id_indikator): ?object
    {
        $indikator = $this->find($id_indikator);
        if (!$indikator) {
            return null;
        }

        // 1. Dimensi Kolom (Karakteristik)
        $indikator->karakteristik_kelompok = $this->getKarakteristikKelompok((int)$indikator->id_karakteristik_kelompok);
        $indikator->kolom_items = $this->getKarakteristikItems((int)$indikator->id_karakteristik_kelompok);
        $indikator->kolom_structure = $this->buildColumnStructure($indikator->kolom_items);

        // 2. Dimensi Baris
        $indikator->baris_kelompok = $this->getBarisKelompok((int)$indikator->id_baris_kelompok);
        $indikator->baris_items = $this->getBarisItems((int)$indikator->id_baris_kelompok);

        // 3. Dimensi Periode
        $indikator->periode_kelompok = $this->getPeriodeKelompok((int)$indikator->id_periode_kelompok);
        $indikator->periode_items = $this->getPeriodeItems((int)$indikator->id_periode_kelompok);

        // 4. Detail relasi tabel DDA (t_tahun_tabel)
        $indikator->tahun_tabel = null;
        if (!empty($indikator->id_tahun_tabel)) {
            $indikator->tahun_tabel = $this->db->table('t_tahun_tabel')
                ->where('id', $indikator->id_tahun_tabel)
                ->get()
                ->getRow();
        }

        return $indikator;
    }

    public function getByTahunTabel(int $id_tahun_tabel): ?object
    {
        $indikator = $this->where('id_tahun_tabel', $id_tahun_tabel)->first();
        if ($indikator) {
            return $this->getTableFullConfig((int)$indikator->id);
        }
        return null;
    }

    public function linkToTahunTabel(int $id_indikator, int $id_tahun_tabel): bool
    {
        $this->update($id_indikator, ['id_tahun_tabel' => $id_tahun_tabel]);
        $this->db->table('t_tahun_tabel')
            ->where('id', $id_tahun_tabel)
            ->update(['is_dynamic' => 1]);

        return true;
    }

    // =========================================================================
    // 5. NILAI DATA MATRIKS DINAMIS (t_nilai_dinamis)
    // =========================================================================

    public function getMatrixLookup(int $id_indikator): array
    {
        $rows = $this->db->table('t_nilai_dinamis')
            ->where('id_indikator', $id_indikator)
            ->get()
            ->getResult();

        $lookup = [];
        foreach ($rows as $row) {
            $key = "{$row->id_baris_item}_{$row->id_karakteristik_item}_{$row->id_periode_item}";
            $lookup[$key] = [
                'id'     => $row->id,
                'nilai'  => $row->nilai !== null ? (float)$row->nilai : null,
                'simbol' => $row->simbol,
            ];
        }

        return $lookup;
    }

    public function batchUpsertNilai(int $id_indikator, array $cells): int
    {
        if (empty($cells)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $chunks = array_chunk($cells, 500);
        $totalAffected = 0;

        $this->db->transStart();

        foreach ($chunks as $chunk) {
            $values = [];
            $params = [];

            foreach ($chunk as $c) {
                $idBaris = (int)$c['id_baris_item'];
                $idKarakteristik = (int)$c['id_karakteristik_item'];
                $idPeriode = (int)$c['id_periode_item'];

                $nilai = isset($c['nilai']) && $c['nilai'] !== '' && $c['nilai'] !== null ? (float)$c['nilai'] : null;
                $simbol = !empty($c['simbol']) ? trim($c['simbol']) : null;

                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?)";
                $params[] = $id_indikator;
                $params[] = $idBaris;
                $params[] = $idKarakteristik;
                $params[] = $idPeriode;
                $params[] = $nilai;
                $params[] = $simbol;
                $params[] = $now;
                $params[] = $now;
            }

            $sql = "INSERT INTO t_nilai_dinamis 
                    (id_indikator, id_baris_item, id_karakteristik_item, id_periode_item, nilai, simbol, created_at, updated_at)
                    VALUES " . implode(', ', $values) . "
                    ON DUPLICATE KEY UPDATE 
                        nilai = VALUES(nilai),
                        simbol = VALUES(simbol),
                        updated_at = VALUES(updated_at)";

            $this->db->query($sql, $params);
            $totalAffected += count($chunk);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException("Gagal menyimpan data nilai sel ke database. Transaksi dibatalkan (rollback).");
        }

        return $totalAffected;
    }

    public function clearNilai(int $id_indikator): bool
    {
        return $this->db->table('t_nilai_dinamis')
            ->where('id_indikator', $id_indikator)
            ->delete();
    }

    // =========================================================================
    // 6. EKSPOR MATRIKS 2D UNTUK ENGINE MATCHING & VERIFIKASI PDF
    // =========================================================================

    /**
     * Mengekspor satu tabel dinamis berdasarkan id_tahun_tabel ke format grid 2D
     * yang langsung kompatibel dengan compare_table.py.
     */
    public function exportSingleDynamicTable(int $id_tahun_tabel): ?array
    {
        $indikator = $this->db->table('m_indikator_tabel i')
            ->select('i.*, t.tahun, t.no_tabel')
            ->join('t_tahun_tabel t', 't.id = i.id_tahun_tabel', 'left')
            ->where('i.id_tahun_tabel', $id_tahun_tabel)
            ->get()
            ->getRow();

        if (!$indikator) {
            return null;
        }

        return $this->buildDynamicTableExport($indikator);
    }

    /**
     * Mengekspor seluruh tabel dinamis pada tahun tertentu sekaligus (keyed by id_tahun_tabel).
     */
    public function exportDynamicTablesForMatching(int $tahun): array
    {
        $indikators = $this->db->table('m_indikator_tabel i')
            ->select('i.*, t.tahun, t.no_tabel')
            ->join('t_tahun_tabel t', 't.id = i.id_tahun_tabel', 'inner')
            ->where('t.tahun', $tahun)
            ->get()
            ->getResult();

        if (empty($indikators)) {
            return [];
        }

        $indIds = [];
        $karIds = [];
        $barIds = [];
        $perIds = [];
        foreach ($indikators as $ind) {
            $indIds[] = (int)$ind->id;
            if (!empty($ind->id_karakteristik_kelompok)) $karIds[] = (int)$ind->id_karakteristik_kelompok;
            if (!empty($ind->id_baris_kelompok)) $barIds[] = (int)$ind->id_baris_kelompok;
            if (!empty($ind->id_periode_kelompok)) $perIds[] = (int)$ind->id_periode_kelompok;
        }
        $indIds = array_values(array_unique($indIds));
        $karIds = array_values(array_unique($karIds));
        $barIds = array_values(array_unique($barIds));
        $perIds = array_values(array_unique($perIds));

        $cache = [
            'kar_items' => [],
            'bar_kel'   => [],
            'bar_items' => [],
            'per_kel'   => [],
            'per_items' => [],
            'nilai'     => [],
        ];

        if (!empty($karIds)) {
            $rows = $this->db->table('m_karakteristik_item')->whereIn('id_kelompok', $karIds)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
            foreach ($rows as $r) {
                $cache['kar_items'][(int)$r->id_kelompok][] = $r;
            }
        }
        if (!empty($barIds)) {
            $rows = $this->db->table('m_baris_kelompok')->whereIn('id', $barIds)->get()->getResult();
            foreach ($rows as $r) {
                $cache['bar_kel'][(int)$r->id] = $r;
            }
            $rows = $this->db->table('m_baris_item')->whereIn('id_kelompok', $barIds)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
            foreach ($rows as $r) {
                $cache['bar_items'][(int)$r->id_kelompok][] = $r;
            }
        }
        if (!empty($perIds)) {
            $rows = $this->db->table('m_periode_kelompok')->whereIn('id', $perIds)->get()->getResult();
            foreach ($rows as $r) {
                $cache['per_kel'][(int)$r->id] = $r;
            }
            $rows = $this->db->table('m_periode_item')->whereIn('id_kelompok', $perIds)->orderBy('no_urut', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
            foreach ($rows as $r) {
                $cache['per_items'][(int)$r->id_kelompok][] = $r;
            }
        }
        if (!empty($indIds)) {
            $rows = $this->db->table('t_nilai_dinamis')->whereIn('id_indikator', $indIds)->get()->getResult();
            foreach ($rows as $row) {
                $key = "{$row->id_baris_item}_{$row->id_karakteristik_item}_{$row->id_periode_item}";
                $cache['nilai'][(int)$row->id_indikator][$key] = [
                    'id'     => $row->id,
                    'nilai'  => $row->nilai !== null ? (float)$row->nilai : null,
                    'simbol' => $row->simbol,
                ];
            }
        }

        $exports = [];
        foreach ($indikators as $ind) {
            $idTahunTabel = (int)$ind->id_tahun_tabel;
            $built = $this->buildDynamicTableExport($ind, $cache);
            if ($built) {
                $exports[$idTahunTabel] = $built;
            }
        }

        return $exports;
    }

    /**
     * Merakit grid 2D dari konfigurasi m_indikator_tabel dan isi t_nilai_dinamis.
     */
    protected function buildDynamicTableExport(object $indikator, ?array $cache = null): array
    {
        $idIndikator      = (int)$indikator->id;
        $idKarakteristik  = (int)($indikator->id_karakteristik_kelompok ?? 0);
        $idBaris          = (int)($indikator->id_baris_kelompok ?? 0);
        $idPeriode        = (int)($indikator->id_periode_kelompok ?? 0);
        $posisiHirarki    = $indikator->posisi_hirarki ?? 'kolom_atas';
        $cfgTotal         = $indikator->konfigurasi_total ?? 'default';
        $isRuntunWaktu    = ($cfgTotal === 'runtun_waktu');
        $tingkatKolom     = $indikator->tingkat_kolom ?? 'semua';

        if ($cache !== null) {
            $itemsKolom      = $idKarakteristik > 0 ? ($cache['kar_items'][$idKarakteristik] ?? []) : [];
            $kelompokBaris   = $idBaris > 0 ? ($cache['bar_kel'][$idBaris] ?? null) : null;
            $itemsBaris      = $idBaris > 0 ? ($cache['bar_items'][$idBaris] ?? []) : [];
            $kelompokPeriode = $idPeriode > 0 ? ($cache['per_kel'][$idPeriode] ?? null) : null;
            $itemsPeriode    = $idPeriode > 0 ? ($cache['per_items'][$idPeriode] ?? []) : [];
        } else {
            $itemsKolom      = $idKarakteristik > 0 ? $this->getKarakteristikItems($idKarakteristik) : [];
            $kelompokBaris   = $idBaris > 0 ? $this->getBarisKelompok($idBaris) : null;
            $itemsBaris      = $idBaris > 0 ? $this->getBarisItems($idBaris) : [];
            $kelompokPeriode = $idPeriode > 0 ? $this->getPeriodeKelompok($idPeriode) : null;
            $itemsPeriode    = $idPeriode > 0 ? $this->getPeriodeItems($idPeriode) : [];
        }
        $kolomStructure = $this->buildColumnStructure($itemsKolom);

        if (empty($itemsBaris)) {
            $posisiHirarki = 'kolom_atas';
        }

        // Resolusi tingkat kolom aktif
        $maxDepth = !empty($kolomStructure['max_depth']) ? (int)$kolomStructure['max_depth'] : (!empty($kolomStructure['has_groups']) ? 2 : 1);
        $activeLevels = ($maxDepth === 3) ? [1, 2, 3] : (($maxDepth === 2) ? [1, 2] : [1]);
        if (!empty($tingkatKolom) && $tingkatKolom !== 'semua') {
            if ($tingkatKolom === 'sub_bab') {
                $activeLevels = ($maxDepth === 3) ? [2, 3] : [2];
            } elseif ($tingkatKolom === 'bab') {
                $activeLevels = [1];
            } else {
                $parts = array_values(array_filter(array_map('intval', explode(',', $tingkatKolom)), function ($l) use ($maxDepth) {
                    return $l >= 1 && $l <= $maxDepth;
                }));
                if (!empty($parts)) {
                    $activeLevels = $parts;
                }
            }
        }
        sort($activeLevels);

        $groups       = $kolomStructure['groups'] ?? [];
        $rawFlatKolom = $kolomStructure['flat_items'] ?? $itemsKolom;
        $flatKolom    = $rawFlatKolom;

        if (!empty($kolomStructure) && $maxDepth === 3) {
            if (in_array(3, $activeLevels)) {
                $flatKolom = $rawFlatKolom;
            } elseif (in_array(2, $activeLevels)) {
                $flatKolom = [];
                foreach ($groups as $grp) {
                    if (!empty($grp['subgroups'])) {
                        foreach ($grp['subgroups'] as $sub) {
                            $firstChild = !empty($sub['children']) ? $sub['children'][0] : null;
                            $flatKolom[] = (object)[
                                'id'              => $firstChild ? (int)$firstChild->id : 0,
                                'item_ind'        => $sub['title_ind'],
                                'item_en'         => $sub['title_en'] ?? $sub['title_ind'],
                                'group_title_ind' => $grp['title_ind'],
                            ];
                        }
                    } else {
                        $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                        $flatKolom[] = (object)[
                            'id'              => $firstChild ? (int)$firstChild->id : 0,
                            'item_ind'        => $grp['title_ind'],
                            'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                            'group_title_ind' => null,
                        ];
                    }
                }
            } else {
                $flatKolom = [];
                foreach ($groups as $grp) {
                    $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                    $flatKolom[] = (object)[
                        'id'              => $firstChild ? (int)$firstChild->id : 0,
                        'item_ind'        => $grp['title_ind'],
                        'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                        'group_title_ind' => null,
                    ];
                }
            }
        } elseif (!empty($kolomStructure) && $maxDepth === 2 && !in_array(2, $activeLevels)) {
            $flatKolom = [];
            foreach ($groups as $grp) {
                $firstChild = !empty($grp['children']) ? $grp['children'][0] : ($grp['item'] ?? null);
                $flatKolom[] = (object)[
                    'id'              => $firstChild ? (int)$firstChild->id : 0,
                    'item_ind'        => $grp['title_ind'],
                    'item_en'         => $grp['title_en'] ?? $grp['title_ind'],
                    'group_title_ind' => null,
                ];
            }
        }

        $hasKolom   = !empty($flatKolom);
        $hasPeriode = !empty($itemsPeriode);
        $hasBaris   = !empty($itemsBaris);

        $leafColumns = [];
        if (!$hasBaris && $hasKolom && $hasPeriode) {
            foreach ($flatKolom as $child) {
                $grpTitle = $child->grup_induk_ind ?? ($child->group_title_ind ?? null);
                $label = $grpTitle ? ($grpTitle . ' - ' . $child->item_ind) : $child->item_ind;
                $leafColumns[] = [
                    'item_id'    => (int)$child->id,
                    'periode_id' => 0,
                    'label'      => trim((string)$label),
                ];
            }
        } elseif (!$hasKolom && $hasPeriode) {
            foreach ($itemsPeriode as $p) {
                $leafColumns[] = [
                    'item_id'    => 0,
                    'periode_id' => (int)$p->id,
                    'label'      => trim((string)$p->nama_periode_ind),
                ];
            }
        } elseif ($hasKolom && !$hasPeriode) {
            foreach ($flatKolom as $child) {
                $grpTitle = $child->grup_induk_ind ?? ($child->group_title_ind ?? null);
                $label = $grpTitle ? ($grpTitle . ' - ' . $child->item_ind) : $child->item_ind;
                $leafColumns[] = [
                    'item_id'    => (int)$child->id,
                    'periode_id' => 0,
                    'label'      => trim((string)$label),
                ];
            }
        } elseif ($hasKolom && $hasPeriode) {
            if ($isRuntunWaktu || $posisiHirarki === 'periode_baris') {
                foreach ($flatKolom as $child) {
                    $grpTitle = $child->grup_induk_ind ?? ($child->group_title_ind ?? null);
                    $label = $grpTitle ? ($grpTitle . ' - ' . $child->item_ind) : $child->item_ind;
                    $leafColumns[] = [
                        'item_id'    => (int)$child->id,
                        'periode_id' => 0,
                        'label'      => trim((string)$label),
                    ];
                }
            } elseif ($posisiHirarki === 'periode_atas') {
                foreach ($itemsPeriode as $p) {
                    foreach ($flatKolom as $child) {
                        $grpTitle = $child->grup_induk_ind ?? ($child->group_title_ind ?? null);
                        $colName = $grpTitle ? ($grpTitle . ' ' . $child->item_ind) : $child->item_ind;
                        $leafColumns[] = [
                            'item_id'    => (int)$child->id,
                            'periode_id' => (int)$p->id,
                            'label'      => trim($p->nama_periode_ind . ' ' . $colName),
                        ];
                    }
                }
            } else {
                foreach ($flatKolom as $child) {
                    $grpTitle = $child->grup_induk_ind ?? ($child->group_title_ind ?? null);
                    $colName = $grpTitle ? ($grpTitle . ' ' . $child->item_ind) : $child->item_ind;
                    foreach ($itemsPeriode as $p) {
                        $leafColumns[] = [
                            'item_id'    => (int)$child->id,
                            'periode_id' => (int)$p->id,
                            'label'      => (count($itemsPeriode) > 1) ? trim($colName . ' (' . $p->nama_periode_ind . ')') : trim((string)$colName),
                        ];
                    }
                }
            }
        }

        // Ambil nilai sel dari t_nilai_dinamis
        $lookup = ($cache !== null) ? ($cache['nilai'][$idIndikator] ?? []) : $this->getMatrixLookup($idIndikator);
        $filledCellCount = 0;
        foreach ($lookup as $cell) {
            if ($cell['nilai'] !== null || (!empty($cell['simbol']) && trim((string)$cell['simbol']) !== '')) {
                $filledCellCount++;
            }
        }

        $titleBaris = 'Rincian';
        if (!$hasBaris && $kelompokPeriode) {
            $titleBaris = $kelompokPeriode->judul_periode_ind ?: ($kelompokPeriode->nama_kelompok_ind ?? 'Tahun');
        } elseif ($kelompokBaris) {
            $titleBaris = $kelompokBaris->judul_baris_ind ?: ($kelompokBaris->nama_kelompok_ind ?? 'Rincian');
            $titleBaris = preg_replace('/^\d+\s+/', '', $titleBaris);
        }

        $headerRow = [$titleBaris];
        foreach ($leafColumns as $lc) {
            $headerRow[] = $lc['label'];
        }

        $numRow = ['(1)'];
        for ($i = 0; $i < count($leafColumns); $i++) {
            $numRow[] = '(' . ($i + 2) . ')';
        }

        $grid = [$headerRow, $numRow];
        $templateGrid = [$headerRow, $numRow];
        $cellCoords = [];
        $seq = 0;

        $formatCellValue = function (?array $cell) {
            if (!$cell) {
                return null;
            }
            if (!empty($cell['simbol']) && $cell['nilai'] === null) {
                return $cell['simbol'];
            }
            if ($cell['nilai'] !== null) {
                $v = (float)$cell['nilai'];
                return (floor($v) == $v) ? (int)$v : $v;
            }
            return $cell['simbol'] ?? null;
        };

        $pushCellWithCoord = function (array &$valRow, array &$tplRow, int $bId, int $cId, int $pId, ?array $cell) use (&$seq, &$cellCoords, $formatCellValue) {
            $valRow[] = $formatCellValue($cell);
            $seq++;
            $tplRow[] = -900000 - $seq;
            $cellCoords[(string)$seq] = [
                'b' => $bId,
                'k' => $cId,
                'p' => $pId,
            ];
        };

        $formatCategoryLabel = function (string $namaBaris): string {
            $lower = strtolower(trim($namaBaris));
            if ($lower === 'kabupaten') {
                return 'Kabupaten/Regency';
            }
            if ($lower === 'kota') {
                return 'Kota/Municipality';
            }
            return $namaBaris;
        };

        if (!$hasBaris && $hasKolom && $hasPeriode) {
            foreach ($itemsPeriode as $p) {
                $row = [trim((string)$p->nama_periode_ind)];
                $tRow = [trim((string)$p->nama_periode_ind)];
                foreach ($leafColumns as $lc) {
                    $key = "0_{$lc['item_id']}_{$p->id}";
                    $pushCellWithCoord($row, $tRow, 0, (int)$lc['item_id'], (int)$p->id, $lookup[$key] ?? null);
                }
                $grid[] = $row;
                $templateGrid[] = $tRow;
            }
        } else {
            $idPeriodeAktif = 0;
            if (!empty($itemsPeriode)) {
                $tahunTabel = trim((string)($indikator->tahun ?? ''));
                foreach ($itemsPeriode as $p) {
                    if ($tahunTabel !== '' && trim((string)$p->nama_periode_ind) === $tahunTabel) {
                        $idPeriodeAktif = (int)$p->id;
                        break;
                    }
                }
                if (!$idPeriodeAktif) {
                    $idPeriodeAktif = (int)$itemsPeriode[0]->id;
                }
            }

            $hasRenderedTotal = false;

            foreach ($itemsBaris as $baris) {
                $idB = (int)$baris->id;
                $tipeBaris = strtolower(trim($baris->tipe_baris ?? ''));
                $namaBaris = trim((string)($baris->nama_baris_ind ?? ($baris->item_ind ?? '')));
                $lowerBaris = strtolower($namaBaris);

                if (!$tipeBaris) {
                    if ($lowerBaris === 'kabupaten' || $lowerBaris === 'kota') {
                        $tipeBaris = 'kategori';
                    } elseif ($lowerBaris === 'jumlah' || $lowerBaris === 'total' || strpos($lowerBaris, 'jawa tengah') !== false) {
                        $tipeBaris = 'total';
                    } else {
                        $tipeBaris = 'data';
                    }
                }

                if ($tipeBaris === 'total') {
                    if ($cfgTotal === 'none') {
                        continue;
                    }
                    if ($cfgTotal === 'jawa_tengah') {
                        $namaBaris = 'Jawa Tengah';
                    } elseif ($cfgTotal === 'jumlah' || $cfgTotal === 'runtun_waktu') {
                        $namaBaris = 'Jumlah';
                    } elseif ($cfgTotal === 'kustom' && !empty($indikator->label_total_kustom)) {
                        $namaBaris = $indikator->label_total_kustom;
                    }
                }

                if (strpos($namaBaris, '||') !== false) {
                    $parts = array_map('trim', explode('||', $namaBaris));
                    $namaBaris = implode(' ', array_filter($parts));
                }

                if ($tipeBaris === 'kategori') {
                    $catRow = [$formatCategoryLabel($namaBaris)];
                    for ($i = 0; $i < count($leafColumns); $i++) {
                        $catRow[] = null;
                    }
                    $grid[] = $catRow;
                    $templateGrid[] = $catRow;
                    continue;
                }

                if ($isRuntunWaktu) {
                    if ($tipeBaris === 'total') {
                        $hasRenderedTotal = true;
                        foreach ($itemsPeriode as $p) {
                            $row = [$namaBaris . ' ' . trim((string)$p->nama_periode_ind)];
                            $tRow = [$namaBaris . ' ' . trim((string)$p->nama_periode_ind)];
                            foreach ($leafColumns as $lc) {
                                $key = "{$idB}_{$lc['item_id']}_{$p->id}";
                                if (!isset($lookup[$key])) {
                                    $key0 = "0_{$lc['item_id']}_{$p->id}";
                                    $pushCellWithCoord($row, $tRow, 0, (int)$lc['item_id'], (int)$p->id, $lookup[$key0] ?? null);
                                } else {
                                    $pushCellWithCoord($row, $tRow, $idB, (int)$lc['item_id'], (int)$p->id, $lookup[$key] ?? null);
                                }
                            }
                            $grid[] = $row;
                            $templateGrid[] = $tRow;
                        }
                    } else {
                        $row = [$namaBaris];
                        $tRow = [$namaBaris];
                        foreach ($leafColumns as $lc) {
                            $key = "{$idB}_{$lc['item_id']}_{$idPeriodeAktif}";
                            $pushCellWithCoord($row, $tRow, $idB, (int)$lc['item_id'], (int)$idPeriodeAktif, $lookup[$key] ?? null);
                        }
                        $grid[] = $row;
                        $templateGrid[] = $tRow;
                    }
                } elseif ($posisiHirarki === 'periode_baris' && !empty($itemsPeriode)) {
                    foreach ($itemsPeriode as $p) {
                        $row = [$namaBaris . ' ' . trim((string)$p->nama_periode_ind)];
                        $tRow = [$namaBaris . ' ' . trim((string)$p->nama_periode_ind)];
                        foreach ($leafColumns as $lc) {
                            $key = "{$idB}_{$lc['item_id']}_{$p->id}";
                            $pushCellWithCoord($row, $tRow, $idB, (int)$lc['item_id'], (int)$p->id, $lookup[$key] ?? null);
                        }
                        $grid[] = $row;
                        $templateGrid[] = $tRow;
                    }
                } else {
                    $row = [$namaBaris];
                    $tRow = [$namaBaris];
                    foreach ($leafColumns as $lc) {
                        $pId = (int)$lc['periode_id'];
                        $cId = (int)$lc['item_id'];
                        $key = "{$idB}_{$cId}_{$pId}";
                        $pushCellWithCoord($row, $tRow, $idB, $cId, $pId, $lookup[$key] ?? null);
                    }
                    $grid[] = $row;
                    $templateGrid[] = $tRow;
                }
            }

            if ($isRuntunWaktu && !$hasRenderedTotal && !empty($itemsPeriode)) {
                foreach ($itemsPeriode as $p) {
                    $row = ['Jumlah ' . trim((string)$p->nama_periode_ind)];
                    $tRow = ['Jumlah ' . trim((string)$p->nama_periode_ind)];
                    foreach ($leafColumns as $lc) {
                        $key = "0_{$lc['item_id']}_{$p->id}";
                        $pushCellWithCoord($row, $tRow, 0, (int)$lc['item_id'], (int)$p->id, $lookup[$key] ?? null);
                    }
                    $grid[] = $row;
                    $templateGrid[] = $tRow;
                }
            }
        }

        return [
            'id_indikator'  => $idIndikator,
            'is_dynamic'    => 1,
            'dynamic_empty' => ($filledCellCount === 0),
            'cell_count'    => $filledCellCount,
            'dynamic_grid'  => ($filledCellCount === 0) ? [] : $grid,
            'template_grid' => $templateGrid,
            'cell_coords'   => $cellCoords,
        ];
    }
}
