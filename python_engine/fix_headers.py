import re

with open('python_engine/compare_table.py', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the old BPS detection block in detect_col_headers (lines 393-412)
old_block = """    # A. Deteksi baris nomor kolom BPS: (1), (2), (3)...
    num_row_idx = -1
    for i, r in enumerate(source_data[:20]):
        if not isinstance(r, list): continue
        non_empty = [clean_header_text(c) for c in r if clean_header_text(c)]
        if len(non_empty) >= 2 and all(re.match(r'^\\(?\\d+\\)?$', c) for c in non_empty):
            num_row_idx = i
            break

    # Dynamic Column Profiler: Jika ada data wilayah dan kolom numerik aktif, ekstrak langsung dari kolom tersebut
    active_cols = detect_active_data_cols(source_data)
    if active_cols:
        first_data_idx = 0
        for idx, r in enumerate(source_data):
            if isinstance(r, list) and find_wilayah_with_type(' '.join(str(c) for c in r[:3])) not in ['Umum', 'Total Jawa Tengah']:
                first_data_idx = idx
                break
        
        limit_hdr = num_row_idx if num_row_idx != -1 else first_data_idx
        header_rows = source_data[:limit_hdr] if limit_hdr > 0 else source_data[:first_data_idx]"""

new_block = """    # A. Deteksi baris nomor kolom BPS menggunakan fungsi terpusat
    num_row_idx, _ = find_bps_numbering_row(source_data)

    # Dynamic Column Profiler: Jika ada kolom numerik aktif, ekstrak header dari kolom tersebut
    active_cols = detect_active_data_cols(source_data)
    if active_cols:
        # Cari baris data pertama (bisa wilayah ATAU baris dengan nomor urut + angka)
        first_data_idx = num_row_idx + 1 if num_row_idx >= 0 else 0
        if first_data_idx == 0:
            for idx, r in enumerate(source_data):
                if not isinstance(r, list): continue
                if find_wilayah_with_type(' '.join(str(c) for c in r[:3])) not in ['Umum', 'Total Jawa Tengah']:
                    first_data_idx = idx
                    break
                c0 = str(r[0]).strip().rstrip('.')
                if c0.isdigit() and int(c0) <= 40 and any(parse_num(str(c).strip()) is not None for c in r[1:]):
                    first_data_idx = idx
                    break
        
        limit_hdr = num_row_idx if num_row_idx != -1 else first_data_idx
        header_rows = source_data[:limit_hdr] if limit_hdr > 0 else source_data[:first_data_idx]"""

if old_block in content:
    content = content.replace(old_block, new_block, 1)
    with open('python_engine/compare_table.py', 'w', encoding='utf-8') as f:
        f.write(content)
    print("SUCCESS: Replaced detect_col_headers BPS block")
else:
    print("ERROR: Old block not found")
