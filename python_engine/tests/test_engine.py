"""
Unit tests untuk python_engine (akurasi angka, penormalan wilayah, dan ekstraksi)
Dapat dijalankan langsung dengan:
    python -m unittest python_engine/tests/test_engine.py
atau:
    pytest python_engine/tests/test_engine.py
"""

import os
import sys
import unittest

ENGINE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if ENGINE_DIR not in sys.path:
    sys.path.insert(0, ENGINE_DIR)

from normalizer import (
    parse_num,
    evaluate_cell_match,
    explain_mismatch,
    resolve_jateng_entity,
    resolve_provinsi_entity,
    is_safe_fuzzy_match,
    clean_header_text,
)
from auto_config_pdf import (
    clean_cell_text,
    clean_footnote_markers,
    split_bilingual,
    is_period_token,
)


class TestParseNum(unittest.TestCase):
    def test_native_numbers(self):
        self.assertEqual(parse_num(1234), 1234.0)
        self.assertEqual(parse_num(123.456), 123.456)
        self.assertEqual(parse_num(-45.67), -45.67)
        self.assertEqual(parse_num(0), 0.0)

    def test_boolean_is_not_number(self):
        self.assertIsNone(parse_num(True))
        self.assertIsNone(parse_num(False))

    def test_indonesian_number_formats(self):
        # Format ribuan titik, desimal koma
        self.assertEqual(parse_num("1.234,56"), 1234.56)
        self.assertEqual(parse_num("49.752.646,8"), 49752646.8)
        self.assertEqual(parse_num("12,5"), 12.5)
        self.assertEqual(parse_num("0,5"), 0.5)
        self.assertEqual(parse_num("0,001"), 0.001)
        self.assertEqual(parse_num("1,685"), 1.685)
        self.assertEqual(parse_num("1500,685"), 1500.685)
        self.assertEqual(parse_num("1.500,685"), 1500.685)

    def test_us_number_formats(self):
        # Format ribuan koma, desimal titik
        self.assertEqual(parse_num("1,234.56"), 1234.56)
        self.assertEqual(parse_num("1,234,567.89"), 1234567.89)
        self.assertEqual(parse_num("0.25"), 0.25)
        self.assertEqual(parse_num("1.5"), 1.5)

    def test_source_side_programmatic_decimals(self):
        # Di sisi sumber (DB / API JSON: is_pdf=False), '123.456' adalah desimal 123.456, bukan ribuan 123456
        self.assertEqual(parse_num("123.456", is_pdf=False), 123.456)
        self.assertEqual(parse_num("0.789", is_pdf=False), 0.789)

    def test_negative_numbers_and_unicode_minuses(self):
        self.assertEqual(parse_num("-5"), -5.0)
        self.assertEqual(parse_num("-1.234,5"), -1234.5)
        self.assertEqual(parse_num("−5"), -5.0)  # \u2212
        self.assertEqual(parse_num("–5"), -5.0)  # en-dash \u2013
        self.assertEqual(parse_num("—10.5"), -10.5)  # em-dash \u2014

    def test_dash_nihil_zero_representations(self):
        for s in ["-", "–", "—", "−", "\u2212"]:
            val = parse_num(s)
            self.assertEqual(val, 0.0, f"Dash should return 0.0 for '{s}'")
        for s in ["...", "…", "na", "n.a.", "n/a"]:
            val = parse_num(s)
            self.assertEqual(val, "...", f"Dots/NA should return '...' for '{s}'")
        self.assertIsNone(parse_num(""))

    def test_accounting_dash_suffix(self):
        self.assertEqual(parse_num("100,-"), 100.0)
        self.assertEqual(parse_num("2.500,-"), 2500.0)

    def test_percent_and_units_stripped(self):
        self.assertEqual(parse_num("12 %"), 12.0)
        self.assertEqual(parse_num("75%"), 75.0)
        self.assertEqual(parse_num("1.500 km2"), 1500.0)
        self.assertEqual(parse_num("250 ha"), 250.0)

    def test_period_label_not_number(self):
        self.assertIsNone(parse_num("2023/2024"))
        self.assertIsNone(parse_num("2024/2025"))


class TestEvaluateCellMatch(unittest.TestCase):
    def test_strict_exact_matches(self):
        # Angka sama persis
        is_m, is_tol, diff = evaluate_cell_match(1234.56, 1234.56)
        self.assertTrue(is_m)
        self.assertFalse(is_tol)
        self.assertEqual(diff, 0.0)

    def test_strict_exact_zero_diff(self):
        is_m, is_tol, diff = evaluate_cell_match(0.0, 0.0)
        self.assertTrue(is_m)
        self.assertFalse(is_tol)

    def test_bps_symbols_matching(self):
        # 1. Dash vs 0.0 -> COCOK (keduanya bernilai 0 / nihil)
        is_m, _, _ = evaluate_cell_match(0.0, 0.0)
        self.assertTrue(is_m)
        is_m, _, _ = evaluate_cell_match("-", 0.0)
        self.assertTrue(is_m)
        is_m, _, _ = evaluate_cell_match(0.0, "-")
        self.assertTrue(is_m)

        # 2. Dots vs Dots / None -> COCOK (keduanya data belum tersedia)
        is_m, _, _ = evaluate_cell_match("...", "...")
        self.assertTrue(is_m)
        is_m, _, _ = evaluate_cell_match("...", None)
        self.assertTrue(is_m)
        is_m, _, _ = evaluate_cell_match(None, "...")
        self.assertTrue(is_m)

        # 3. Dash (0.0) vs Dots (...) -> BEDA NILAI (nihil != belum ada data)
        is_m, _, _ = evaluate_cell_match(0.0, "...")
        self.assertFalse(is_m)
        is_m, _, _ = evaluate_cell_match("...", 0.0)
        self.assertFalse(is_m)

        # 4. Angka riil vs Dots (...) -> BEDA NILAI
        is_m, _, _ = evaluate_cell_match(1234.0, "...")
        self.assertFalse(is_m)
        is_m, _, _ = evaluate_cell_match("...", 1234.0)
        self.assertFalse(is_m)

    def test_strict_mismatch_detected_even_small(self):
        # Selisih 0.01 HARUS dilaporkan sebagai TIDAK COCOK bila tolerance=0.0
        is_m, is_tol, diff = evaluate_cell_match(10.50, 10.51, tolerance=0.0)
        self.assertFalse(is_m)
        self.assertFalse(is_tol)
        self.assertEqual(diff, 0.01)

    def test_tolerance_flag_when_explicitly_provided(self):
        is_m, is_tol, diff = evaluate_cell_match(10.50, 10.51, tolerance=0.05)
        self.assertTrue(is_m)
        self.assertTrue(is_tol)
        self.assertEqual(diff, 0.01)

    def test_ambiguous_dot_comma_representations(self):
        # '1.234' pada PDF (bisa dibaca 1234 atau 1.234) cocok dengan 1234 di DB
        pv = parse_num("1.234", is_pdf=True)
        is_m, _, _ = evaluate_cell_match(pv, 1234.0)
        self.assertTrue(is_m)

        # dan juga cocok jika DB memang menyimpan nilai desimal 1.234
        is_m2, _, _ = evaluate_cell_match(pv, 1.234)
        self.assertTrue(is_m2)

    def test_explain_mismatch_hints(self):
        # Pola selisih x1000
        hint = explain_mismatch(1.5, 1500.0)
        self.assertIn("ribuan", hint)

    def test_time_format_crime_clock(self):
        # Format waktu Crime Clock (e.g. '05:23:46')
        pv = parse_num("05:23:46", is_pdf=True)
        self.assertEqual(pv, "05:23:46")
        is_m, is_tol, diff = evaluate_cell_match(pv, "05:23:46")
        self.assertTrue(is_m)
        self.assertFalse(is_tol)
        self.assertEqual(diff, 0.0)

        # Mismatch waktu
        is_m_diff, _, _ = evaluate_cell_match(pv, "18:56:28")
        self.assertFalse(is_m_diff)

        # Pola selisih x1.000.000
        hint_juta = explain_mismatch(2.0, 2000000.0)
        self.assertIn("jutaan", hint_juta)

        # Pola footnote superscript (3601 vs 360)
        hint_fn = explain_mismatch(3601.0, 360.0)
        self.assertIn("catatan kaki", hint_fn)


class TestJatengEntityResolution(unittest.TestCase):
    def test_regencies_and_cities(self):
        c_cil, n_cil = resolve_jateng_entity("Kabupaten Cilacap")
        self.assertEqual(c_cil, "33.01")
        self.assertEqual(n_cil, "Kab. Cilacap")

        c_smg_k, n_smg_k = resolve_jateng_entity("Kota Semarang")
        self.assertEqual(c_smg_k, "33.74")
        self.assertEqual(n_smg_k, "Kota Semarang")

        c_smg_kab, n_smg_kab = resolve_jateng_entity("Kabupaten Semarang")
        self.assertEqual(c_smg_kab, "33.22")
        self.assertEqual(n_smg_kab, "Kab. Semarang")

        c_sur, n_sur = resolve_jateng_entity("Surakarta")
        self.assertEqual(c_sur, "33.72")
        self.assertEqual(n_sur, "Kota Surakarta")

        c_prov, n_prov = resolve_jateng_entity("Provinsi Jawa Tengah")
        self.assertEqual(c_prov, "33.00")


class TestBilingualAndPdfCleaning(unittest.TestCase):
    def test_split_bilingual_single_line_slash(self):
        ind, en = split_bilingual("Produksi / Production")
        self.assertEqual(ind, "Produksi")
        self.assertEqual(en, "Production")

    def test_split_bilingual_preserves_slash_in_terms(self):
        # 'Kabupaten/Kota' tidak boleh dipecah sembarangan
        ind, en = split_bilingual("Kabupaten/Kota\nRegency/Municipality")
        self.assertEqual(ind, "Kabupaten/Kota")
        self.assertEqual(en, "Regency/Municipality")

    def test_clean_footnote_markers(self):
        self.assertEqual(clean_footnote_markers("20241)"), "2024")
        self.assertEqual(clean_footnote_markers("Padi1"), "Padi")

    def test_is_period_token(self):
        self.assertTrue(is_period_token("2024"))
        self.assertTrue(is_period_token("2023-2024"))
        self.assertTrue(is_period_token("2023/2024"))
        self.assertFalse(is_period_token("Jumlah"))

    def test_split_bilingual_with_double_bar_separator(self):
        ind, en = split_bilingual("Jenis Komoditi / Type of Commodity || Provinsi / Province")
        self.assertEqual(ind, "Jenis Komoditi || Provinsi")
        self.assertEqual(en, "Type of Commodity || Province")

    def test_split_bilingual_multiline_with_double_bar(self):
        ind, en = split_bilingual("1. Binatang hidup\nLive animals || Jawa Tengah\nCentral Java")
        self.assertEqual(ind, "1. Binatang hidup || Jawa Tengah")
        self.assertEqual(en, "Live animals || Central Java")


class TestEngineSymbolIntegrity(unittest.TestCase):
    def test_compare_table_has_no_undefined_symbols(self):
        import ast
        import builtins
        import glob

        engine_files = [
            os.path.join(ENGINE_DIR, "compare_table.py"),
            os.path.join(ENGINE_DIR, "normalizer.py"),
            os.path.join(ENGINE_DIR, "auto_config_pdf.py"),
            os.path.join(ENGINE_DIR, "fetcher.py"),
            os.path.join(ENGINE_DIR, "table_profiler.py"),
            os.path.join(ENGINE_DIR, "pdf_extractor.py"),
        ]

        class ScopeVisitor(ast.NodeVisitor):
            def __init__(self):
                self.scopes = [set(dir(builtins))]
                self.scopes[0].add("__file__")
                self.undefined = set()

            def visit_Import(self, node):
                for alias in node.names:
                    name = alias.asname or alias.name.split(".")[0]
                    self.scopes[-1].add(name)

            def visit_ImportFrom(self, node):
                for alias in node.names:
                    name = alias.asname or alias.name
                    self.scopes[-1].add(name)

            def visit_FunctionDef(self, node):
                self.scopes[-1].add(node.name)
                local_scope = set()
                for arg in node.args.args + node.args.kwonlyargs:
                    local_scope.add(arg.arg)
                if node.args.vararg:
                    local_scope.add(node.args.vararg.arg)
                if node.args.kwarg:
                    local_scope.add(node.args.kwarg.arg)
                self.scopes.append(local_scope)
                self.generic_visit(node)
                self.scopes.pop()

            def visit_AsyncFunctionDef(self, node):
                self.visit_FunctionDef(node)

            def visit_ClassDef(self, node):
                self.scopes[-1].add(node.name)
                self.scopes.append(set())
                self.generic_visit(node)
                self.scopes.pop()

            def visit_ListComp(self, node):
                local_scope = set()
                for gen in node.generators:
                    for n in ast.walk(gen.target):
                        if isinstance(n, ast.Name):
                            local_scope.add(n.id)
                self.scopes.append(local_scope)
                self.generic_visit(node)
                self.scopes.pop()

            def visit_SetComp(self, node):
                self.visit_ListComp(node)

            def visit_GeneratorExp(self, node):
                self.visit_ListComp(node)

            def visit_DictComp(self, node):
                local_scope = set()
                for gen in node.generators:
                    for n in ast.walk(gen.target):
                        if isinstance(n, ast.Name):
                            local_scope.add(n.id)
                self.scopes.append(local_scope)
                self.generic_visit(node)
                self.scopes.pop()

            def visit_Lambda(self, node):
                local_scope = set()
                for arg in node.args.args + node.args.kwonlyargs:
                    local_scope.add(arg.arg)
                self.scopes.append(local_scope)
                self.generic_visit(node)
                self.scopes.pop()

            def visit_For(self, node):
                for n in ast.walk(node.target):
                    if isinstance(n, ast.Name):
                        self.scopes[-1].add(n.id)
                self.generic_visit(node)

            def visit_ExceptHandler(self, node):
                if node.name:
                    self.scopes[-1].add(node.name)
                self.generic_visit(node)

            def visit_Name(self, node):
                if isinstance(node.ctx, ast.Store):
                    self.scopes[-1].add(node.id)
                elif isinstance(node.ctx, ast.Load):
                    name = node.id
                    found = any(name in s for s in reversed(self.scopes))
                    if not found:
                        self.undefined.add((name, node.lineno))

        for file_path in engine_files:
            if not os.path.exists(file_path):
                continue
            with open(file_path, "r", encoding="utf-8") as f:
                tree = ast.parse(f.read(), file_path)
            visitor = ScopeVisitor()
            visitor.visit(tree)
            self.assertEqual(
                len(visitor.undefined),
                0,
                f"Undefined symbols found in {os.path.basename(file_path)}: {visitor.undefined}",
            )


class TestGenericSchemaProfiler(unittest.TestCase):
    def test_schema_profiling_roles(self):
        from table_profiler import profile_api_schema
        records = [
            {
                "id": 1,
                "tahun_data": 2025,
                "kode_wilayah": "33.01",
                "nama_wilayah": "Kab. Cilacap",
                "jumlah_pma": "15",
                "nilai_investasi": "1250000.5",
            },
            {
                "id": 2,
                "tahun_data": 2025,
                "kode_wilayah": "33.02",
                "nama_wilayah": "Kab. Banyumas",
                "jumlah_pma": "8",
                "nilai_investasi": "750000.0",
            },
            {
                "id": 3,
                "tahun_data": 2025,
                "kode_wilayah": "33.03",
                "nama_wilayah": "Kab. Purbalingga",
                "jumlah_pma": "3",
                "nilai_investasi": "250000.0",
            },
        ]
        roles = profile_api_schema(records)
        self.assertEqual(roles.get("id"), "system")
        self.assertEqual(roles.get("tahun_data"), "temporal")
        self.assertEqual(roles.get("kode_wilayah"), "code")
        self.assertEqual(roles.get("nama_wilayah"), "region")
        self.assertEqual(roles.get("jumlah_pma"), "metric")
        self.assertEqual(roles.get("nilai_investasi"), "metric")


class TestGenericApiNormalizer(unittest.TestCase):
    def test_crosstab_pivot_normalization(self):
        from table_profiler import normalize_api_data
        records = [
            {"asal_partai_politik": "Partai A", "jenis_kelamin": "L", "jumlah": 10},
            {"asal_partai_politik": "Partai A", "jenis_kelamin": "P", "jumlah": 5},
            {"asal_partai_politik": "Partai B", "jenis_kelamin": "L", "jumlah": 7},
            {"asal_partai_politik": "Partai B", "jenis_kelamin": "P", "jumlah": 3},
        ]
        grid = normalize_api_data(records, pdf_rows=[["Laki-Laki", "Perempuan", "Jumlah"]])
        self.assertIsNotNone(grid)
        self.assertIn("Laki-Laki", grid[0])
        self.assertIn("Perempuan", grid[0])
        self.assertIn("Jumlah", grid[0])
        # Cek row Partai A
        row_a = next(r for r in grid if r[0] == "Partai A")
        self.assertEqual(row_a[1], 10)  # L
        self.assertEqual(row_a[2], 5)   # P
        self.assertEqual(row_a[3], 15)  # Total

    def test_time_series_pivot_normalization(self):
        from table_profiler import normalize_api_data
        records = [
            {"tahun_data": 2024, "jenis_belanja": "Belanja Pegawai", "realisasi": 500},
            {"tahun_data": 2025, "jenis_belanja": "Belanja Pegawai", "realisasi": 600},
            {"tahun_data": 2024, "jenis_belanja": "Belanja Barang", "realisasi": 200},
            {"tahun_data": 2025, "jenis_belanja": "Belanja Barang", "realisasi": 250},
        ]
        grid = normalize_api_data(records)
        self.assertIsNotNone(grid)
        self.assertEqual(grid[0], ["Jenis Belanja", "Tahun 2024", "Tahun 2025"])
        row_peg = next(r for r in grid if r[0] == "Belanja Pegawai")
        self.assertEqual(row_peg, ["Belanja Pegawai", 500, 600])


class TestActiveDataColsAndAlignment(unittest.TestCase):
    def test_detect_active_data_cols_preserves_satuan_pendidikan_and_unit_kapal(self):
        from table_profiler import detect_active_data_cols
        grid_412 = [
            ["Kabupaten/Kota", "2024/2025 Satuan Pendidikan", "2024/2025 Kepala Sekolah dan Pendidik", "2024/2025 Peserta Didik", "2025/2026 Satuan Pendidikan", "2025/2026 Kepala Sekolah dan Pendidik", "2025/2026 Peserta Didik"],
            ["(1)", "(2)", "(3)", "(4)", "(5)", "(6)", "(7)"],
            ["Cilacap", None, None, None, 188, 570, 8202],
            ["Banyumas", None, None, None, 167, 442, 6199],
        ]
        cols = detect_active_data_cols(grid_412)
        self.assertEqual(cols, [1, 2, 3, 4, 5, 6])

        grid_9112 = [
            ["Tahun", "Jenis Pelayaran - Samudra - Unit Kapal", "Jenis Pelayaran - Samudra - Gross Tonage (GT)"],
            ["(1)", "(2)", "(3)"],
            ["2025", 998, 23483962],
        ]
        self.assertEqual(detect_active_data_cols(grid_9112), [1, 2])

    def test_is_header_or_metadata_row_catches_unit_kapal_subheader(self):
        from normalizer import is_header_or_metadata_row
        self.assertTrue(is_header_or_metadata_row([None, "Unit Kapal\nShip Unit", "Gross Tonage\n(GT)", "Unit Kapal\nShip Unit", "Gross Tonage\n(GT)"]))
        self.assertFalse(is_header_or_metadata_row(["2025", "998", "23.483.962", "2.959", "10.521.394"]))


class TestMatchPdfAndYearScoping(unittest.TestCase):
    def test_title_guard_prevents_blind_number_collision(self):
        from match_pdf import match_tables
        toc = {
            "1.2.2": {"judul_pdf": "Pengamatan Unsur Iklim di Stasiun Pengamatan BMKG", "pages": [80]},
            "1.2.3": {"judul_pdf": "Rata-Rata Suhu dan Kelembaban Udara Menurut Bulan di Provinsi Jawa Tengah", "pages": [82]},
        }
        db_tables = [
            {
                "id_db": 320,
                "nomor_tabel": "1.2.2",
                "judul_tabel": "Rata-rata Suhu dan Kelembaban Udara Menurut Bulan di Provinsi Jawa Tengah",
                "source_type": "dinamis",
                "is_dynamic": 1,
            }
        ]
        res = match_tables(toc, db_tables)
        self.assertEqual(len(res["matched_in_pdf"]), 1)
        m0 = res["matched_in_pdf"][0]
        self.assertEqual(m0["nomor_tabel"], "1.2.3")
        self.assertEqual(m0["id_db"], "320")
        self.assertGreaterEqual(m0["similarity"], 90.0)

    def test_api_year_filter_enforces_n_minus_1_cap(self):
        from table_profiler import _filter_api_records_by_target_year
        recs = [
            {"nama_wilayah": "Kab. Cilacap", "tahun_data": "2025", "jumlah": 100},
            {"nama_wilayah": "Kab. Cilacap", "tahun_data": "2026", "jumlah": 200},
        ]
        out_2026_book = _filter_api_records_by_target_year(recs, ["tahun_data"], pdf_rows=[["Kabupaten/Kota", "Jumlah"]], pub_year=2026)
        self.assertEqual(len(out_2026_book), 1)
        self.assertEqual(out_2026_book[0]["tahun_data"], "2025")

        out_2027_book = _filter_api_records_by_target_year(recs, ["tahun_data"], pdf_rows=[["Kabupaten/Kota", "Jumlah"]], pub_year=2027)
        self.assertEqual(len(out_2027_book), 1)
        self.assertEqual(out_2027_book[0]["tahun_data"], "2026")

    def test_semantic_conflict_prevents_dimension_and_scope_collisions(self):
        from match_pdf import match_tables, _has_semantic_conflict
        # Uji 1: PNS Menurut Kab/Kota harus ke 2.3.1, tidak terkunci di 2.3.5 Dinas/Instansi walau nomor sama
        toc = {
            "2.3.1": {"judul_pdf": "Jumlah Pegawai Negeri Sipil Menurut Kabupaten/Kota dan Jenis Kelamin di Provinsi Jawa Tengah", "pages": [125]},
            "2.3.5": {"judul_pdf": "Jumlah Pegawai Negeri Sipil Menurut Dinas/Instansi Pemerintah dan Jenis Kelamin di Pemerintah Provinsi Jawa Tengah", "pages": [133]},
            "11.7": {"judul_pdf": "Rata-Rata Pengeluaran per Kapita Sebulan Makanan dan Bukan Makanan di Daerah Perdesaan Menurut Kabupaten/Kota", "pages": [500]},
            "11.8": {"judul_pdf": "Persentase Pengeluaran per Kapita Sebulan Makanan dan Bukan Makanan di Daerah Perkotaan Menurut Kabupaten/Kota", "pages": [501]},
            "4.6.9": {"judul_pdf": "Jumlah Kejadian Bencana Non Alam Menurut Kabupaten/Kota di Provinsi Jawa Tengah", "pages": [400]},
            "6.1.9": {"judul_pdf": "Banyaknya Desa yang Berlistrik PLN dan Pelanggan", "pages": [600]},
        }
        db_tables = [
            {"id_db": 795, "nomor_tabel": "2.3.5", "judul_tabel": "Jumlah Pegawai Negeri Sipil Menurut Kabupaten/Kota dan Jenis Kelamin di Provinsi Jawa Tengah, Tahunan", "source_type": "dinamis", "is_dynamic": 1},
            {"id_db": 217, "nomor_tabel": "11.8", "judul_tabel": "Rata-rata Pengeluaran per Kapita Sebulan Makanan dan Bukan Makanan di Daerah Perdesaan Menurut Kabupaten/Kota (rupiah), Tahunan", "source_type": "dinamis", "is_dynamic": 1},
            {"id_db": 621, "nomor_tabel": "4.6.9", "judul_tabel": "Jumlah Kejadian Bencana Kebakaran dan Jumlah Korban yang Diakibatkan Bencana Kebakaran Menurut Kabupaten/Kota", "source_type": "dinamis", "is_dynamic": 1},
            {"id_db": 555, "nomor_tabel": "6.1.9", "judul_tabel": "Banyaknya Desa yang Berlistrik PLN dan Pelanggan di PT PLN (Persero) Distribusi Provinsi Jawa Tengah, Tahunan", "source_type": "dinamis", "is_dynamic": 1},
        ]
        res = match_tables(toc, db_tables)
        matched_map = {m["id_db"]: m["nomor_tabel"] for m in res["matched_in_pdf"]}
        self.assertEqual(matched_map.get("795"), "2.3.1")
        self.assertEqual(matched_map.get("217"), "11.7")
        self.assertEqual(matched_map.get("555"), "6.1.9")
        self.assertNotIn("621", matched_map)  # Kebakaran vs Bencana Non Alam tidak boleh dipaksa jodoh
        self.assertTrue(_has_semantic_conflict("Rata-rata Pengeluaran Perkotaan", "Persentase Pengeluaran Perkotaan"))
        self.assertFalse(_has_semantic_conflict("Banyaknya Guru SD", "Jumlah Kepala Sekolah dan Pendidik SD"))


class TestMultiPageStitchingAndHierarchy(unittest.TestCase):
    def test_stitch_table_pages_vertical_vs_true_horizontal(self):
        from unittest.mock import patch
        import pdf_extractor

        vertical_pages = {
            1: [
                ["Unsur Iklim\nClimate Elements", "2022", "2023", "2024"],
                ["(1)", "(2)", "(3)", "(4)"],
                ["Stasiun Klimatologi Semarang", "", "", ""],
                ["Suhu", "", "", ""],
                ["Minimum", "23,1", "24,1", "25,1"],
            ],
            2: [
                ["Unsur Iklim\nClimate Elements", "2022", "2023", "2024"],
                ["(1)", "(3)", "(4)", "(5)"],
                ["Stasiun Klimatologi Tegal", "", "", ""],
                ["Suhu", "", "", ""],
                ["Minimum", "23,3", "24,9", "25,4"],
            ],
        }
        with patch.object(pdf_extractor, "get_cached_page_tables", side_effect=lambda _p, pg: vertical_pages[pg]):
            stitched_v = pdf_extractor.stitch_table_pages("dummy.pdf", [1, 2])
        self.assertTrue(all(len(r) == 4 for r in stitched_v if isinstance(r, list)))
        flat_txt = " ".join(str(c) for r in stitched_v for c in r if c)
        self.assertIn("Semarang", flat_txt)
        self.assertIn("Tegal", flat_txt)

        horiz_pages = {
            1: [
                ["Kabupaten/Kota", "2023 - Kota", "2023 - Desa"],
                ["(1)", "(2)", "(3)"],
                ["Cilacap", "10", "20"],
            ],
            2: [
                ["Kabupaten/Kota", "2024 - Kota", "2024 - Desa"],
                ["(1)", "(4)", "(5)"],
                ["Cilacap", "30", "40"],
            ],
        }
        with patch.object(pdf_extractor, "get_cached_page_tables", side_effect=lambda _p, pg: horiz_pages[pg]):
            stitched_h = pdf_extractor.stitch_table_pages("dummy.pdf", [1, 2])
        self.assertEqual(len(stitched_h[0]), 5)
        self.assertEqual(stitched_h[-1], ["Cilacap", "10", "20", "30", "40"])

    def test_compare_multilevel_reordered_stations_and_dash_subheader(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        db_grid = [
            ["Unsur Iklim", "2023", "2024"],
            ["(1)", "(2)", "(3)"],
            ["Stasiun A", None, None],
            ["Suhu", None, None],
            ["Minimum", 24.1, 25.1],
            ["Maksimum", 33.3, 33.4],
            ["Tekanan Udara (mb)", None, None],
            ["Minimum", 1009.1, 1006.4],
            ["Jumlah Curah Hujan (mm)", 1904.1, 1907.1],
            ["Stasiun B", None, None],
            ["Suhu", None, None],
            ["Minimum", 20.1, 21.1],
            ["Tekanan Udara (mb)", None, None],
            ["Minimum", None, None],
            ["Jumlah Curah Hujan (mm)", 2799.0, 3413.7],
        ]
        # template_grid mirrors DB coords: None for group headers, negative IDs for leaf rows
        tpl_grid = [
            ["Unsur Iklim", "2023", "2024"],
            ["(1)", "(2)", "(3)"],
            ["Stasiun A", None, None],
            ["Suhu", None, None],
            ["Minimum", -900001, -900002],
            ["Maksimum", -900003, -900004],
            ["Tekanan Udara (mb)", None, None],
            ["Minimum", -900005, -900006],
            ["Jumlah Curah Hujan (mm)", -900007, -900008],
            ["Stasiun B", None, None],
            ["Suhu", None, None],
            ["Minimum", -900009, -900010],
            ["Tekanan Udara (mb)", None, None],
            ["Minimum", -900011, -900012],
            ["Jumlah Curah Hujan (mm)", -900013, -900014],
        ]
        # PDF has Stasiun C before Stasiun B, plus all-dash Tekanan Udara header on Stasiun B
        pdf_pages_data = {
            10: [
                ["Unsur Iklim", "2022", "2023", "2024"],
                ["(1)", "(2)", "(3)", "(4)"],
                ["Stasiun A", "", "", ""],
                ["Suhu/Temperature", "", "", ""],
                ["Minimum/Minimum", "23,1", "24,1", "25,1"],
                ["Maksimum/Maximum", "34,1", "33,3", "33,4"],
                ["Tekanan Udara\nAtmospheric Pressure (mb)", "", "", ""],
                ["Minimum/Minimum", "1.008,2", "1.009,1", "1.006,4"],
                ["Jumlah Curah Hujan (mm)", "2.663,2", "1.904,1", "1.907,1"],
            ],
            11: [
                ["Unsur Iklim", "2022", "2023", "2024"],
                ["(1)", "(3)", "(4)", "(5)"],
                ["Stasiun C", "", "", ""],
                ["Suhu/Temperature", "", "", ""],
                ["Minimum/Minimum", "23,3", "24,9", "25,4"],
                ["Jumlah Curah Hujan (mm)", "2.225,0", "1.333,0", "1.433,1"],
            ],
            12: [
                ["Unsur Iklim", "2022", "2023", "2024"],
                ["(1)", "(6)", "(7)", "(8)"],
                ["Stasiun B", "", "", ""],
                ["Suhu/Temperature", "", "", ""],
                ["Minimum/Minimum", "19,0", "20,1", "21,1"],
                ["Tekanan Udara\nAtmospheric Pressure (mb)", "–", "–", "–"],
                ["Minimum/Minimum", "–", "–", "942,8"],
                ["Jumlah Curah Hujan (mm)", "4.250,0", "2.799,0", "3.413,7"],
            ],
        }
        stitched_all = pdf_pages_data[10] + pdf_pages_data[11] + pdf_pages_data[12]

        def fake_extract(_pdf_path, info_list):
            for info in info_list:
                pgs = info.get("pages") or []
                if len(pgs) == 1:
                    info["extracted_data"] = pdf_pages_data.get(pgs[0], [])
                else:
                    info["extracted_data"] = stitched_all

        db_payload = [{
            "id_db": "991",
            "nomor_tabel": "9.9.1",
            "judul_tabel": "Unsur Iklim Uji",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_grid,
            "template_grid": tpl_grid,
            "cell_coords": {str(i): {"b": 100 + i, "k": 0, "p": 2023 + (i % 2 == 0), "t": 2023 + (i % 2 == 0)} for i in range(1, 15)},
        }]
        _entry = {"nomor_tabel": "9.9.1", "id_db": "991", "judul_db": "Unsur Iklim Uji", "judul_pdf": "Unsur Iklim Uji", "pages": [10, 11, 12], "similarity": 100}
        fake_idx = {"9.9.1": _entry, "991": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "extract_table_content", side_effect=fake_extract), \
                 patch.object(compare_table, "get_cached_page_tables", side_effect=lambda _p, pg: pdf_pages_data.get(pg, [])), \
                 patch.object(compare_table, "detect_table_archetype", return_value="MULTI_PAGE_SECTION_PER_PAGE"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2025):
                res = compare_table.compare_head_to_head("dummy.pdf", "9.9.1", db_file, id_db="991", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(len(res["matches"]), 12)
        matched_pairs = {(m["wilayah"], m["metric"]): m["val"] for m in res["matches"]}
        self.assertEqual(matched_pairs[("Stasiun A - Suhu - Minimum", "Tahun 2023")], 24.1)
        self.assertEqual(matched_pairs[("Stasiun A - Tekanan Udara (mb) - Minimum", "Tahun 2024")], 1006.4)
        self.assertEqual(matched_pairs[("Stasiun A - Jumlah Curah Hujan", "Tahun 2024")], 1907.1)
        self.assertEqual(matched_pairs[("Stasiun B - Suhu - Minimum", "Tahun 2023")], 20.1)
        self.assertEqual(matched_pairs[("Stasiun B - Jumlah Curah Hujan", "Tahun 2024")], 3413.7)
        pdf_only_w = {p["wilayah"] for p in res["pdf_only"]}
        self.assertIn("Stasiun C - Suhu - Minimum", pdf_only_w)
        self.assertIn("Stasiun B - Tekanan Udara - Minimum", pdf_only_w)

    def test_categorical_year_aware_col_map_and_direct_map_guard(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        db_grid = [
            ["Jabatan", "Jenis Kelamin - Laki-Laki", "Jenis Kelamin - Perempuan"],
            ["(1)", "(2)", "(3)"],
            ["Fungsional Tertentu", 60667, 117064],
            ["Struktural", 10788, 6733],
        ]
        tpl_grid = [
            ["Jabatan", "Jenis Kelamin - Laki-Laki", "Jenis Kelamin - Perempuan"],
            ["(1)", "(2)", "(3)"],
            ["Fungsional Tertentu", -900001, -900002],
            ["Struktural", -900003, -900004],
        ]
        stitched_pdf = [
            ["Jabatan", "2023", None, "2024", None],
            [None, "Laki-Laki", "Perempuan", "Laki-Laki", "Perempuan"],
            ["(1)", "(2)", "(3)", "(4)", "(5)"],
            ["Fungsional Tertentu", "64.449", "118.055", "60.667", "117.064"],
            ["Struktural", "11.483", "7.002", "10.788", "6.733"],
        ]

        def fake_extract(_pdf_path, info_list):
            for info in info_list:
                info["extracted_data"] = stitched_pdf

        db_payload = [{
            "id_db": "992",
            "nomor_tabel": "9.9.2",
            "judul_tabel": "PNS Menurut Jabatan",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_grid,
            "template_grid": tpl_grid,
            "cell_coords": {
                "1": {"b": 10, "k": 101, "p": 2024, "t": 2024},
                "2": {"b": 10, "k": 102, "p": 2024, "t": 2024},
                "3": {"b": 11, "k": 101, "p": 2024, "t": 2024},
                "4": {"b": 11, "k": 102, "p": 2024, "t": 2024},
            },
        }]
        _entry = {"nomor_tabel": "9.9.2", "id_db": "992", "judul_db": "PNS Menurut Jabatan", "judul_pdf": "PNS Menurut Jabatan", "pages": [127, 128], "similarity": 100}
        fake_idx = {"9.9.2": _entry, "992": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "extract_table_content", side_effect=fake_extract), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=stitched_pdf), \
                 patch.object(compare_table, "detect_table_archetype", return_value="MULTI_PAGE_SECTION_PER_PAGE"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2025):
                res = compare_table.compare_head_to_head("dummy.pdf", "9.9.2", db_file, id_db="992", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(res.get("target_year"), "2024")
        self.assertEqual(len(res["matches"]), 4)
        self.assertEqual(len(res["diffs"]), 0)
        self.assertEqual(len(res["pdf_only"]), 4)

    def test_all_null_api_dataset_detected_as_empty(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        api_rows = [
            {"tahun_data": 2025, "kode_wilayah": f"33.{i:02d}", "nama_wilayah": f"Kab. Test {i}", "realisasi_pajak": None}
            for i in range(1, 36)
        ]
        db_payload = [{
            "id_db": "993",
            "nomor_tabel": "9.9.3",
            "judul_tabel": "Realisasi Pajak Daerah",
            "source_type": "api",
            "is_dynamic": 0,
            "link_tabel": "index.php/admin/view_portal_tabel?id=993",
        }]
        _entry = {"nomor_tabel": "9.9.3", "id_db": "993", "pages": [177], "similarity": 100}
        fake_idx = {"9.9.3": _entry, "993": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_rows), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=[["Kabupaten/Kota", "2024"], ["1.", "Cilacap", "100"]]), \
                 patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": [["Kabupaten/Kota", "2024"], ["1.", "Cilacap", "100"]]})), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy.pdf", "9.9.3", db_file, id_db="993", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(res.get("status"), "error")
        self.assertTrue(res.get("is_api_empty"))

    def test_non_additive_total_jateng_not_summed(self):
        from table_profiler import normalize_api_data, _is_non_additive_metric_header

        self.assertTrue(_is_non_additive_metric_header("Persentase Belum Kawin"))
        self.assertTrue(_is_non_additive_metric_header("Penemuan Kasus Baru Kusta Per 100000 Penduduk"))
        self.assertFalse(_is_non_additive_metric_header("Jumlah Penduduk"))

        api_rows = [
            {"tahun_data": 2025, "kode_wilayah": "33.01", "nama_wilayah": "Kab. Cilacap", "jumlah_penduduk": 1000, "persentase_belum_kawin": 45.0},
            {"tahun_data": 2025, "kode_wilayah": "33.02", "nama_wilayah": "Kab. Banyumas", "jumlah_penduduk": 2000, "persentase_belum_kawin": 55.0},
            {"tahun_data": 2025, "kode_wilayah": "33.03", "nama_wilayah": "Kab. Purbalingga", "jumlah_penduduk": 3000, "persentase_belum_kawin": 60.0},
        ]
        pdf_rows = [["Kabupaten/Kota", "Jumlah", "Persentase"], ["Cilacap", "1000", "45,0"], ["Banyumas", "2000", "55,0"], ["Purbalingga", "3000", "60,0"], ["Jawa Tengah", "6000", "50,0"]]
        grid = normalize_api_data(api_rows, pdf_rows=pdf_rows, target_table="9.9.4", pub_year=2026)
        tot = grid[-1]
        self.assertEqual(tot[0], "Total Jawa Tengah")
        self.assertEqual(tot[1], 6000)
        self.assertIsNone(tot[2])

    def test_interval_operator_preserved_and_col_map_leaf_matching(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        api_rows = [
            {"tahun_data": 2025, "partai": "PKB", "jumlah_anggota_dprd_beragama_islam": 20, "jumlah_anggota_dprd_beragama_kristen": 0, "jumlah_anggota_dprd": 20},
            {"tahun_data": 2025, "partai": "PDIP", "jumlah_anggota_dprd_beragama_islam": 28, "jumlah_anggota_dprd_beragama_kristen": 4, "jumlah_anggota_dprd": 33},
        ]
        pdf_rows = [
            ["Partai Politik", "Agama/Religion", None, "Jumlah/Total"],
            [None, "Islam", "Kristen", None],
            ["(1)", "(2)", "(3)", "(4)"],
            ["PKB", "20", "–", "20"],
            ["PDIP", "28", "4", "33"],
        ]
        db_payload = [{
            "id_db": "995",
            "nomor_tabel": "9.9.5",
            "judul_tabel": "Anggota DPRD Menurut Agama",
            "source_type": "api",
            "is_dynamic": 0,
            "link_tabel": "index.php/admin/view_portal_tabel?id=995",
        }]
        _entry = {"nomor_tabel": "9.9.5", "id_db": "995", "pages": [120], "similarity": 100}
        fake_idx = {"9.9.5": _entry, "995": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_rows), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows), \
                 patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows})), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy.pdf", "9.9.5", db_file, id_db="995", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(len(res["diffs"]), 0)
        self.assertEqual(len(res["matches"]), 6)

    def test_le_interval_keeps_operator_and_promotes_nonzero_to_diffs(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        db_grid = [
            ["Kelompok Umur", "2024 Jenis Kelamin - Laki-Laki", "2025 Jenis Kelamin - Laki-Laki"],
            ["(1)", "(2)", "(3)"],
            ["≤ 20", None, None],
            ["21 - 30", 4832, 8331],
        ]
        tpl_grid = [
            ["Kelompok Umur", "2024 Jenis Kelamin - Laki-Laki", "2025 Jenis Kelamin - Laki-Laki"],
            ["(1)", "(2)", "(3)"],
            ["≤ 20", -900001, -900002],
            ["21 - 30", -900003, -900004],
        ]
        pdf_rows = [
            ["Kelompok Umur", "2024", "2025"],
            [None, "Laki-Laki", "Laki-Laki"],
            ["(1)", "(2)", "(3)"],
            ["≤ 20", "–", "1"],
            ["21 - 30", "4.832", "8.331"],
        ]
        db_payload = [{
            "id_db": "996",
            "nomor_tabel": "9.9.6",
            "judul_tabel": "PPPK Menurut Umur",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_grid,
            "template_grid": tpl_grid,
            "cell_coords": {
                "1": {"b": 627, "k": 101, "p": 2024, "t": 2024},
                "2": {"b": 627, "k": 101, "p": 2025, "t": 2025},
                "3": {"b": 628, "k": 101, "p": 2024, "t": 2024},
                "4": {"b": 628, "k": 101, "p": 2025, "t": 2025},
            },
        }]
        _entry = {"nomor_tabel": "9.9.6", "id_db": "996", "pages": [148], "similarity": 100}
        fake_idx = {"9.9.6": _entry, "996": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy.pdf", "9.9.6", db_file, id_db="996", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(len(res["matches"]), 2)
        self.assertEqual(len(res["diffs"]), 1)
        self.assertEqual(res["diffs"][0]["wilayah"], "≤ 20")
        self.assertEqual(res["diffs"][0]["pdf_val"], 1)
        self.assertEqual(res["diffs"][0]["coord"]["id_baris_item"], 627)
        self.assertEqual(res["diffs"][0]["coord"]["tahun_data"], 2025)
        self.assertEqual(len(res["pdf_only"]), 1)
        self.assertEqual(res["pdf_only"][0]["wilayah"], "≤ 20")


class TestGenericRegressionAndSystemicFixes(unittest.TestCase):
    def test_explicit_kab_dot_prefix_not_overridden_by_kota_mode(self):
        for w in ["Magelang", "Semarang", "Pekalongan", "Tegal"]:
            code_kab, name_kab = resolve_jateng_entity(f"Kab. {w}", is_kota_mode=True)
            self.assertEqual(name_kab, f"Kab. {w}")
            self.assertIn(code_kab, {"33.08", "33.22", "33.26", "33.28"})
            code_kota, name_kota = resolve_jateng_entity(f"Kota {w}", is_kota_mode=False)
            self.assertEqual(name_kota, f"Kota {w}")
            self.assertIn(code_kota, {"33.71", "33.74", "33.75", "33.76"})

    def test_alphabetical_api_regions_and_stale_pivot_config_fallback(self):
        import json as _json
        from table_profiler import normalize_api_data

        # Urutan alfabetis (Brebes muncul sebelum Kab. Magelang & Kota Tegal) + config pivot basi (5.5.6 style)
        api_records = [
            {"tahun_data": 2025, "kode_wilayah": "33.04", "nama_wilayah": "Kab. Banjarnegara", "jumlah_potong": "100"},
            {"tahun_data": 2025, "kode_wilayah": "33.29", "nama_wilayah": "Kab. Brebes", "jumlah_potong": "200"},
            {"tahun_data": 2025, "kode_wilayah": "33.01", "nama_wilayah": "Kab. Cilacap", "jumlah_potong": "300"},
            {"tahun_data": 2025, "kode_wilayah": "33.08", "nama_wilayah": "Kab. Magelang", "jumlah_potong": "400"},
            {"tahun_data": 2025, "kode_wilayah": "33.71", "nama_wilayah": "Kota Magelang", "jumlah_potong": "50"},
            {"tahun_data": 2025, "kode_wilayah": "33.28", "nama_wilayah": "Kab. Tegal", "jumlah_potong": "600"},
            {"tahun_data": 2025, "kode_wilayah": "33.76", "nama_wilayah": "Kota Tegal", "jumlah_potong": "70"},
        ]
        stale_cfg = _json.dumps({
            "columns": [{"key": "tahun_data"}, {"key": "kode_wilayah"}],
            "pivot": {"enabled": True, "row": ["kab_ko"], "col": ["tahun_data"], "val": ["sapi_perah"]},
        })
        grid = normalize_api_data(api_records, pdf_rows=None, target_table="5.5.6", config_tabel=stale_cfg, pub_year=2026)
        self.assertIsNotNone(grid)
        labels = [r[0] for r in grid[1:]]
        # Config basi ditolak -> fallback ke profiler regional; Kab. Magelang & Kab. Tegal tetap Kab., bukan Kota!
        self.assertIn("Kab. Magelang", labels)
        self.assertIn("Kab. Tegal", labels)
        self.assertEqual(labels.count("Kota Magelang"), 1)
        self.assertEqual(labels.count("Kota Tegal"), 1)

    def test_stitch_horizontal_reset_bps_cols_with_year_rows(self):
        from unittest.mock import patch
        import pdf_extractor

        p1 = [
            ["Tahun", "Jenis Pelayaran", None, None, None],
            [None, "Samudra", None, "Nusantara", None],
            [None, "Unit Kapal", "GT", "Unit Kapal", "GT"],
            ["(1)", "(2)", "(3)", "(4)", "(5)"],
            ["2021", "10", "100", "20", "200"],
            ["2022", "11", "110", "21", "210"],
        ]
        # Nomor kolom BPS reset lagi ke (2)..(5), tetapi header beda (Tunker & Jumlah) dan baris tahun sama (2021-2022)
        p2 = [
            ["Tahun", "Jenis Pelayaran", None, "Jumlah / Total", None],
            [None, "Tunker", None, None, None],
            [None, "Unit Kapal", "GT", "Unit Kapal", "GT"],
            ["(1)", "(2)", "(3)", "(4)", "(5)"],
            ["2021", "30", "300", "60", "600"],
            ["2022", "31", "310", "62", "620"],
        ]
        with patch.object(pdf_extractor, "get_cached_page_tables", side_effect=lambda _p, pg: p1 if pg == 935 else p2):
            merged = pdf_extractor.stitch_table_pages("dummy.pdf", [935, 936])
        self.assertGreaterEqual(len(merged[0]), 9)
        row_2021 = next(r for r in merged if r and r[0] == "2021")
        self.assertEqual(row_2021, ["2021", "10", "100", "20", "200", "30", "300", "60", "600"])

    def test_diff_entry_meta_null_source_and_dots_pdf(self):
        from compare_table import _build_diff_entry_meta

        diff_null, note_null = _build_diff_entry_meta(97.75, None, 97.75, None, 0.0)
        self.assertEqual(diff_null, -97.75)
        self.assertIn("sel ini kosong", note_null)
        self.assertNotIn("Selisih 0", note_null)

        diff_dots, note_dots = _build_diff_entry_meta("...", 15, "...", 15, 0.0)
        self.assertIn("'...'", note_dots)
        self.assertNotIn("Selisih 0", note_dots)

    def test_year_subrows_api_sync_no_zero_regression(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        # PDF hanya memuat sub-baris tahun 2023 dan 2024 (seperti 2.4.3), sedangkan API punya 2024 dan 2025
        pdf_rows = [
            ["Kabupaten/Kota", None, "Tahun", "Pendapatan", "Belanja"],
            ["(1)", None, "", "", ""],
            ["1.", "Cilacap", "2023", "1.000", "1.100"],
            [None, None, "2024", "2.000", "2.100"],
            ["2.", "Banyumas", "2023", "1.500", "1.600"],
            [None, None, "2024", "2.500", "2.600"],
            ["3.", "Purbalingga", "2023", "1.200", "1.300"],
            [None, None, "2024", "2.200", "2.300"],
        ]
        api_records = [
            {"tahun_data": 2024, "nama_wilayah": "Kab. Cilacap", "pendapatan": "2000", "belanja": "2100"},
            {"tahun_data": 2024, "nama_wilayah": "Kab. Banyumas", "pendapatan": "2500", "belanja": "2600"},
            {"tahun_data": 2024, "nama_wilayah": "Kab. Purbalingga", "pendapatan": "2200", "belanja": "2350"},
            {"tahun_data": 2025, "nama_wilayah": "Kab. Cilacap", "pendapatan": "3000", "belanja": "3100"},
            {"tahun_data": 2025, "nama_wilayah": "Kab. Banyumas", "pendapatan": "3500", "belanja": "3600"},
            {"tahun_data": 2025, "nama_wilayah": "Kab. Purbalingga", "pendapatan": "3200", "belanja": "3300"},
        ]
        db_payload = [{
            "id_db": "785",
            "nomor_tabel": "2.4.3",
            "judul_tabel": "APBD Kabupaten/Kota",
            "source_type": "api",
            "is_dynamic": 0,
            "link_tabel": "https://example.invalid/apbd",
        }]
        _entry = {"nomor_tabel": "2.4.3", "id_db": "785", "pages": [167], "similarity": 100}
        fake_idx = {"2.4.3": _entry, "785": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_records), \
                 patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows), \
                 patch.object(compare_table, "detect_table_archetype", return_value="REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy.pdf", "2.4.3", db_file, id_db="785", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(res["target_year"], "2024")
        self.assertEqual(len(res["matches"]), 5)
        self.assertEqual(len(res["diffs"]), 1)
        self.assertEqual(res["diffs"][0]["wilayah"], "Kab. Purbalingga")
        self.assertEqual(res["diffs"][0]["pdf_val"], 2300)
        self.assertEqual(res["diffs"][0]["src_val"], 2350)

    def test_empty_db_group_auto_subtotal_and_en_comma_scale(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table

        db_grid = [
            ["Pangkat/Golongan/Ruang", "2024 - Laki-Laki", "2024 - Perempuan", "2024 - Jumlah"],
            ["(1)", "(2)", "(3)", "(4)"],
            ["Golongan I", None, None, None],
            ["1. I/A (Juru Muda)", 10, 5, 15],
            ["2. I/B (Juru Muda Tingkat I)", 1022, 95, 1117],
            ["Golongan II", None, None, None],
            ["1. II/A (Pengatur Muda)", 2342, 186, 2528],
            ["2. II/B (Pengatur Muda Tingkat I)", 17274, 14656, 31930],
            ["Jumlah", 20648, 14942, 35590],
        ]
        tpl_grid = [
            db_grid[0], db_grid[1],
            ["Golongan I", -900001, -900002, -900003],
            ["1. I/A (Juru Muda)", -900004, -900005, -900006],
            ["2. I/B (Juru Muda Tingkat I)", -900007, -900008, -900009],
            ["Golongan II", -900010, -900011, -900012],
            ["1. II/A (Pengatur Muda)", -900013, -900014, -900015],
            ["2. II/B (Pengatur Muda Tingkat I)", -900016, -900017, -900018],
            ["Jumlah", -900019, -900020, -900021],
        ]
        # PDF memakai koma ribuan ala Inggris pada seluruh kolom (1,032; 19,616; 20,648)
        pdf_rows = [
            ["Pangkat/Golongan/Ruang", "2024", None, None],
            [None, "Laki-Laki", "Perempuan", "Jumlah"],
            ["(1)", "(2)", "(3)", "(4)"],
            ["Golongan I/Range I", "1,032", "100", "1,132"],
            ["1. I/A", "10", "5", "15"],
            ["2. I/B", "1,022", "95", "1,117"],
            ["Golongan II/Range II", "19,616", "14,842", "34,458"],
            ["3. II/A", "2,342", "186", "2,528"],
            ["4. II/B", "17,274", "14,656", "31,930"],
            ["Jumlah/Total", "20,648", "14,942", "35,590"],
        ]
        db_payload = [{
            "id_db": "798",
            "nomor_tabel": "2.3.8",
            "judul_tabel": "PNS Menurut Kepangkatan",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_grid,
            "template_grid": tpl_grid,
            "cell_coords": {str(i): {"b": 450 + (i - 1) // 3, "k": 101 + (i - 1) % 3, "p": 2024, "t": 2024} for i in range(1, 22)},
        }]
        _entry = {"nomor_tabel": "2.3.8", "id_db": "798", "pages": [136], "similarity": 100}
        fake_idx = {"2.3.8": _entry, "798": _entry}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            import match_pdf
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy.pdf", "2.3.8", db_file, id_db="798", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(len(res["diffs"]), 0)
        self.assertEqual(len(res["pdf_only"]), 0)
        self.assertEqual(len(res["matches"]), 21)
        gol2_l = next(m for m in res["matches"] if m["wilayah"] == "Golongan II" and "Laki-Laki" in m["metric"])
        self.assertEqual(gol2_l["pdf_val"], 19616)
        self.assertTrue(gol2_l.get("is_group_subtotal_match"))

    def test_en_comma_scale_does_not_corrupt_three_decimals(self):
        from compare_table import _detect_en_thousands_pdf_cols, _resolve_pdf_val_with_scale

        # Kolom 0: 3-decimal (7,010 vs 7.01 di DB) -> TIDAK boleh dipromosikan ke ribuan (7010)
        p_dec1 = parse_num("7,010", is_pdf=True)
        p_dec2 = parse_num("77,160", is_pdf=True)
        en_cols = _detect_en_thousands_pdf_cols([(0, p_dec1, 7.01), (0, p_dec2, 77.16)])
        self.assertNotIn(0, en_cols)
        self.assertEqual(float(_resolve_pdf_val_with_scale(p_dec1, sv=7.01, is_en_thousands_col=False)), 7.01)

    def test_academic_year_and_footnote_year_alignment(self):
        from compare_table import _extract_header_years
        from table_profiler import align_columns_semantically

        # 1. Ekstraksi tahun kanonik (Tahun ajaran -> tahun awal; range -> tahun akhir; footnote -> bersih)
        self.assertEqual(_extract_header_years("2023/2024"), ["2023"])
        self.assertEqual(_extract_header_years("2024/2025"), ["2024"])
        self.assertEqual(_extract_header_years("Kepadatan Penduduk per km2 - 20201,5"), ["2020"])
        self.assertEqual(_extract_header_years("Laju Pertumbuhan - 2020–20244"), ["2024"])
        self.assertEqual(clean_header_text("20201,5"), "2020")
        self.assertEqual(clean_header_text("Rumah Tangga Miskin (2024)"), "Rumah Tangga Miskin (2024)")

        # 2. Tahun ajaran 2023/2024 vs 2024/2025 pada buku 2025 (target_year='2024') tidak boleh tertukar
        pdf_headers = [
            "Sekolah - Swasta - 2023/2024",
            "Sekolah - Swasta - 2024/2025",
        ]
        src_headers = ["Satuan Pendidikan - Swasta"]
        col_map, pdf_extra, _, _ = align_columns_semantically(
            src_headers, pdf_headers, {}, {}, target_year="2024"
        )
        self.assertEqual(col_map.get(0), 1)
        self.assertEqual(pdf_extra, [0])

        # 3. Header bertahun footnote ganda ('20201,5', '20242,6') tetap sejajar tepat dengan (2020) dan (2024)
        pdf_hdr_fn = [
            "Kepadatan Penduduk per km2 - 20201,5",
            "Kepadatan Penduduk per km2 - 20242,6",
        ]
        src_hdr_fn = [
            "Kepadatan Penduduk per km (2020)",
            "Kepadatan Penduduk per km (2024)",
        ]
        col_map_fn, _, _, _ = align_columns_semantically(
            src_hdr_fn, pdf_hdr_fn, {}, {}, target_year="2024"
        )
        self.assertEqual(col_map_fn.get(0), 0)
        self.assertEqual(col_map_fn.get(1), 1)

    def test_future_year_api_guard_and_disjoint_year_detection(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table
        import match_pdf
        from table_profiler import _filter_api_records_by_target_year

        # 1. Single-year API record dari masa depan (2026) tidak boleh bocor ke buku 2025 (max_data_y = 2024)
        recs_future = [{"nama_wilayah": "Kab. Cilacap", "tahun_data": "2026", "jumlah": 500}]
        self.assertEqual(_filter_api_records_by_target_year(recs_future, ["tahun_data"], [["Kabupaten/Kota", "Jumlah"]], pub_year=2025), [])

        # 2. Kasus Buku Tahun Depan (DDA 2027, meminta data 2026) namun API baru terisi s.d. 2025 -> harus is_api_empty
        pdf_rows_2027 = [
            ["Tabel 2.4.9 Realisasi Pajak Daerah Menurut Kabupaten/Kota, 2026", None],
            ["Kabupaten/Kota", "2026"],
            ["(1)", "(2)"],
            ["1. Cilacap", "450.000"],
            ["2. Banyumas", "520.000"],
            ["3. Purbalingga", "180.000"],
        ]
        api_recs_2025_only = [
            {"nama_wilayah": "Kab. Cilacap", "tahun_data": "2025", "realisasi": 362000},
            {"nama_wilayah": "Kab. Banyumas", "tahun_data": "2025", "realisasi": 323000},
            {"nama_wilayah": "Kab. Purbalingga", "tahun_data": "2025", "realisasi": 86000},
        ]
        db_payload = [{
            "id_db": "352",
            "nomor_tabel": "2.4.9",
            "judul_tabel": "Realisasi Pajak Daerah Menurut Kabupaten/Kota",
            "source_type": "api",
            "is_dynamic": 0,
            "link_tabel": "index.php/admin/view_portal_tabel?id=561",
        }]
        fake_idx = {"2.4.9": {"nomor_tabel": "2.4.9", "id_db": "352", "pages": [177], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_recs_2025_only), \
                 patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows_2027})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows_2027), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2027):
                res_2027 = compare_table.compare_head_to_head("dummy_2027.pdf", "2.4.9", db_file, id_db="352", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertTrue(res_2027.get("is_api_empty"))
        self.assertIn("2026", res_2027.get("message", ""))

    def test_direct_matrix_coord_binding_and_multiline_bilingual(self):
        """Pastikan semua sel selisih pada tabel bertingkat (A. -> 1. -> a.) dan label bilingual multiline tanpa slash memetakan coord 100% akurat."""
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table
        import match_pdf

        pdf_rows_1314 = [
            ["Lapangan Usaha/Industry", None, "2024*", "2025**"],
            ["(1)", None, "(2)", "(3)"],
            ["C. Industri Pengolahan/\nManufacturing", None, "3,52", "4,06"],
            ["", "1. Industri Makanan dan Minuman/\nManufacture of Food and Beverages", "6,57", "7,83"],
            [
                "O. Administrasi Pemerintahan,\nPertahanan, dan Jaminan Sosial Wajib\nPublic Administration and Defence;\nCompulsory Social Security",
                None,
                "7,53",
                "2,25",
            ],
            ["Produk Domestik Regional Bruto/\nGross Regional Domestic Product", None, "4,95", "5,37"],
        ]
        db_payload = [{
            "id_db": "290",
            "nomor_tabel": "13.1.4",
            "judul_tabel": "Laju Pertumbuhan PDRB Menurut Lapangan Usaha (persen), 2024-2025",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "id_indikator": 89,
            "dynamic_empty": False,
            "dynamic_grid": [
                ["Lapangan Usaha", "2024", "2025"],
                ["(1)", "(2)", "(3)"],
                ["C. Industri Pengolahan", 4.06, 0.0],
                ["1. Industri Makanan dan Minuman", 6.57, 7.83],
                ["O. Administrasi Pemerintahan, Pertahanan, dan Jaminan Sosial Wajib", 2.25, 0.0],
                ["Produk Domestik Regional Bruto", 4.95, 5.37],
            ],
            "template_grid": [
                ["Lapangan Usaha", "2024", "2025"],
                ["(1)", "(2)", "(3)"],
                ["C. Industri Pengolahan", -900001, -900002],
                ["1. Industri Makanan dan Minuman", -900003, -900004],
                ["O. Administrasi Pemerintahan, Pertahanan, dan Jaminan Sosial Wajib", -900005, -900006],
                ["Produk Domestik Regional Bruto", -900007, -900008],
            ],
            "cell_coords": {
                "1": {"b": 376, "k": 0, "p": 2024, "t": 2024},
                "2": {"b": 376, "k": 0, "p": 2025, "t": 2025},
                "3": {"b": 378, "k": 0, "p": 2024, "t": 2024},
                "4": {"b": 378, "k": 0, "p": 2025, "t": 2025},
                "5": {"b": 419, "k": 0, "p": 2024, "t": 2024},
                "6": {"b": 419, "k": 0, "p": 2025, "t": 2025},
                "7": {"b": 423, "k": 0, "p": 2024, "t": 2024},
                "8": {"b": 423, "k": 0, "p": 2025, "t": 2025},
            },
        }]
        fake_idx = {"13.1.4": {"nomor_tabel": "13.1.4", "id_db": "290", "pages": [1195], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(db_payload, tf)
            db_file = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows_1314})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows_1314), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head("dummy_2026.pdf", "13.1.4", db_file, id_db="290", preloaded_db_tables=db_payload, preloaded_index=fake_idx)
        finally:
            os.remove(db_file)

        self.assertEqual(res.get("status"), "success")
        self.assertEqual(len(res.get("pdf_only", [])), 0)
        diffs = res.get("diffs", [])
        self.assertEqual(len(diffs), 4)
        for d in diffs:
            self.assertIn("coord", d, f"Diff item missing coord: {d}")
            self.assertIsNotNone(d["coord"].get("id_baris_item"))

    def test_non_pivot_show_group_total_and_small_int_col1_not_skipped(self):
        """Pastikan tabel API non-pivot dengan show_group_total=True menghasilkan kolom Grup - Jumlah dan tidak membuang kolom pertama bernilai 1,1,0."""
        from table_profiler import normalize_api_data, detect_active_data_cols

        api_recs = [
            {"tahun_data": 2025, "kode_kabupatenkota": "33.01", "nama_kabupatenkota": "Kab. Cilacap", "jumlah_panti_pemerintah": 1, "jumlah_panti_swasta": 3, "jumlah_penghuni_pemerintah": 100, "jumlah_penghuni_swasta": 0},
            {"tahun_data": 2025, "kode_kabupatenkota": "33.02", "nama_kabupatenkota": "Kab. Banyumas", "jumlah_panti_pemerintah": 1, "jumlah_panti_swasta": 3, "jumlah_penghuni_pemerintah": 95, "jumlah_penghuni_swasta": 0},
            {"tahun_data": 2025, "kode_kabupatenkota": "33.03", "nama_kabupatenkota": "Kab. Purbalingga", "jumlah_panti_pemerintah": 0, "jumlah_panti_swasta": 4, "jumlah_penghuni_pemerintah": 0, "jumlah_penghuni_swasta": 0},
        ]
        cfg = {
            "columns": [
                {"key": "nama_kabupatenkota", "label_id": "Kabupaten/Kota", "visible": True},
                {"key": "jumlah_panti_pemerintah", "label_id": "Jumlah Panti Karya || Pemerintah", "visible": True},
                {"key": "jumlah_panti_swasta", "label_id": "Jumlah Panti Karya || Swasta", "visible": True},
                {"key": "jumlah_penghuni_pemerintah", "label_id": "Jumlah Penghuni || Pemerintah", "visible": True},
                {"key": "jumlah_penghuni_swasta", "label_id": "Jumlah Penghuni || Swasta", "visible": True},
            ],
            "pivot": {"enabled": False},
            "show_group_total": True,
            "show_total_col": False,
        }
        grid = normalize_api_data(api_recs, config_tabel=cfg, pub_year=2026)
        self.assertEqual(
            grid[0],
            [
                "Wilayah",
                "Jumlah Panti Karya - Pemerintah",
                "Jumlah Panti Karya - Swasta",
                "Jumlah Panti Karya - Jumlah",
                "Jumlah Penghuni - Pemerintah",
                "Jumlah Penghuni - Swasta",
                "Jumlah Penghuni - Jumlah",
            ],
        )
        self.assertEqual(grid[1], ["Kab. Cilacap", 1, 3, 4, 100, 0, 100])
        self.assertEqual(detect_active_data_cols(grid), [1, 2, 3, 4, 5, 6])

    def test_precision_fixes_alphanumeric_daerah_percentage_and_year_alignment(self):
        """Pastikan 7 perbaikan presisi (parse_num 1A/1B, daerah metric, Jumlah-Persentase, explain 100x, 2.4.5 title year, 9.1.19 pivot tahun_data, header teks 6.2.2) bekerja akurat."""
        from normalizer import explain_mismatch
        from table_profiler import profile_api_schema, _is_non_additive_metric_header, _filter_api_records_by_target_year, extract_pdf_headers, normalize_api_data

        # 1. Kode alfanumerik (4.5.13) tidak boleh diparsing jadi angka
        self.assertIsNone(parse_num("1A", is_pdf=True))
        self.assertIsNone(parse_num("1B", is_pdf=False))
        self.assertIsNone(parse_num("Kelas 1A", is_pdf=True))
        self.assertEqual(parse_num("7.084.p", is_pdf=True), 7084.0)

        # 2. Kolom numerik mengandung 'daerah' (2.4.9, 2.4.10) wajib ber-role metric (bukan region)
        api_30 = [
            {"tahun_data": 2025, "nama_wilayah": f"Kab. Contoh {i}", "realisasi_pajak_daerah_ribu_rupiah": str(100000 + i * 137)}
            for i in range(1, 36)
        ]
        roles = profile_api_schema(api_30)
        self.assertEqual(roles.get("realisasi_pajak_daerah_ribu_rupiah"), "metric")

        # 3. 'Jumlah - Persentase' (2.5.5) dan 'Tahun SK' tidak boleh di-sum() ke Total Jawa Tengah
        self.assertTrue(_is_non_additive_metric_header("Jumlah - Persentase"))
        self.assertTrue(_is_non_additive_metric_header("Tahun SK"))
        self.assertFalse(_is_non_additive_metric_header("Jumlah Penduduk"))

        # 4. explain_mismatch kelipatan 100x tidak boleh dilabeli konversi valas USD
        self.assertIn("100%", explain_mismatch(100, 1))
        self.assertNotIn("valuta asing", explain_mismatch(89.46, 0.895).lower())

        # 5. Judul PDF tahun tunggal < pub_year-1 (2.4.5 -> 2024) diprioritaskan di _filter_api_records_by_target_year
        recs_24_25 = [
            {"tahun_data": 2024, "nama_wilayah": "Kab. Cilacap", "pendapatan": 100},
            {"tahun_data": 2025, "nama_wilayah": "Kab. Cilacap", "pendapatan": 200},
        ]
        filtered = _filter_api_records_by_target_year(
            recs_24_25, ["tahun_data"], [["Kabupaten/Kota", "Pendapatan"], ["1.", "Cilacap", "100"]],
            pub_year=2026, pdf_title="Realisasi Pendapatan Kabupaten/Kota, 2024"
        )
        self.assertEqual([r["tahun_data"] for r in filtered], [2024])

        # 6. Pivot dengan tahun di sumbu baris (9.1.19) tidak membuang tahun 2024 & menyatukan variasi spasi hyphen
        recs_trans = [
            {"tahun_data": 2024, "koridor": "Purwokerto - Purbalingga", "jumlah": 1435938},
            {"tahun_data": 2025, "koridor": "Purwokerto-Purbalingga", "jumlah": 1545194},
            {"tahun_data": 2025, "koridor": "Semarang-Bawen", "jumlah": 2131545},
        ]
        cfg_trans = {
            "columns": [
                {"key": "tahun_data", "label_id": "Tahun", "visible": True},
                {"key": "koridor", "label_id": "koridor", "visible": True},
                {"key": "jumlah", "label_id": "Jumlah", "visible": True},
            ],
            "pivot": {"enabled": True, "row": ["tahun_data"], "col": ["koridor"], "val": ["jumlah"], "total_row": False, "total_col": False},
        }
        grid_t = normalize_api_data(recs_trans, config_tabel=cfg_trans, pub_year=2026)
        self.assertEqual(len(grid_t), 3)
        self.assertEqual(grid_t[0].count("Purwokerto-Purbalingga"), 1)
        self.assertEqual({r[0] for r in grid_t[1:]}, {"2024", "2025"})

        # 7. extract_pdf_headers menyaring kolom teks kanan (6.2.2) berdasarkan kolom angka riil
        pdf_622 = [
            ["Kabupaten/Kota", None, "Jumlah Unit", "Kapasitas", "Satuan", "Sifat Penggunaan"],
            ["(1)", None, "(2)", "(3)", "(4)", "(5)"],
            ["1.", "Cilacap", "435", "43.996", "KW", "Cadangan"],
            ["2.", "Banyumas", "295", "40.683", "KW", "Cadangan"],
        ]
        self.assertEqual(extract_pdf_headers(pdf_622, expected_p_len=2), ["Jumlah Unit", "Kapasitas"])

    def test_architectural_config_none_cols_single_row_and_cat_wrapping(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table
        import match_pdf
        from table_profiler import (
            align_columns_semantically,
            detect_active_data_cols,
            normalize_api_data,
            profile_api_schema,
        )

        # 1. Non-pivot config_tabel dengan kolom metrik all-None (pola 9.1.3) & sparse <30% + label_id (pola 6.1.10 / 4.2.4)
        api_913 = [
            {"tahun_data": 2025, "kode_wilayah": "3301", "kabupaten_kota": "Cilacap", "aspal": "132.5", "tidak_aspal": None, "lainnya": None, "jumlah": "172.33"},
            {"tahun_data": 2025, "kode_wilayah": "3302", "kabupaten_kota": "Banyumas", "aspal": "123.47", "tidak_aspal": None, "lainnya": "41.21", "jumlah": "164.68"},
            {"tahun_data": 2025, "kode_wilayah": "3303", "kabupaten_kota": "Purbalingga", "aspal": None, "tidak_aspal": None, "lainnya": None, "jumlah": None},
            {"tahun_data": 2025, "kode_wilayah": "3304", "kabupaten_kota": "Banjarnegara", "aspal": "57.82", "tidak_aspal": None, "lainnya": None, "jumlah": "57.82"},
        ]
        cfg_913 = {
            "columns": [
                {"key": "tahun_data", "label_id": "Tahun", "visible": False},
                {"key": "kabupaten_kota", "label_id": "Kabupaten/Kota", "visible": True},
                {"key": "aspal", "label_id": "Aspal", "visible": True},
                {"key": "tidak_aspal", "label_id": "Tidak Diaspal", "visible": True},
                {"key": "lainnya", "label_id": "Lainnya", "visible": True},
                {"key": "jumlah", "label_id": "Jumlah", "visible": True},
            ],
            "pivot": {"enabled": False},
        }
        roles_913 = profile_api_schema(api_913)
        self.assertEqual(roles_913.get("tidak_aspal"), "metric")
        grid_913 = normalize_api_data(api_913, config_tabel=cfg_913, pub_year=2026)
        self.assertEqual(grid_913[0], ["Wilayah", "Aspal", "Tidak Diaspal", "Lainnya", "Jumlah"])
        self.assertEqual(detect_active_data_cols(grid_913), [1, 2, 3, 4])

        # Guard: seluruh kolom metrik None semua harus tetap dianggap kosong (empty)
        all_null_recs = [
            {"tahun_data": 2025, "kode_wilayah": "3301", "kabupaten_kota": "Cilacap", "val_a": None, "val_b": None},
            {"tahun_data": 2025, "kode_wilayah": "3302", "kabupaten_kota": "Banyumas", "val_a": None, "val_b": None},
        ]
        roles_null = profile_api_schema(all_null_recs)
        self.assertEqual(roles_null.get("val_a"), "empty")
        self.assertEqual(roles_null.get("val_b"), "empty")

        # 2. Glued degree tokens (D1D3 vs D1-D3, S2S3 vs S2-S3) pada align_columns_semantically
        m_deg = align_columns_semantically(
            ["Jumlah D1D3", "Jumlah S2S3"],
            ["D1–D3", "S2–S3"],
            {"33.01": [1, 2]},
            {"33.01": [1, 2]},
        )[0]
        self.assertEqual({int(k): int(v) for k, v in m_deg.items()}, {0: 0, 1: 1})

        # 3. Tabel API 1-baris dengan kolom awal all-None (pola 5.4.2) tidak boleh bergeser kolom di compare_head_to_head
        api_1row = [{
            "tahun_data": 2025,
            "produksi_kayu_bulat_iuphhkha": None,
            "produksi_kayu_bulat_perum_perhutani": "238932.36",
            "jumlah_produksi_kayu_bulat": "238932.36",
            "produksi_veneer": "330981.3",
        }]
        grid_1row = normalize_api_data(api_1row, pub_year=2026)
        self.assertEqual(detect_active_data_cols(grid_1row), [1, 2, 3, 4])
        pdf_1row = [
            ["Tahun", "Kayu Bulat", None, None, "Kayu Olahan"],
            [None, "IUPHHK-HA", "Perum Perhutani", "Jumlah", "Veneer"],
            ["(1)", "(2)", "(3)", "(4)", "(5)"],
            ["2024", "–", "294.166,99", "294.166,99", "352.821,00"],
            ["2025", "–", "238.932,36", "238.932,36", "330.981,30"],
        ]

        # 4. Baris total provinsi 'Jawa Tengah', newline Indonesia, dan ejaan Budha/Konghuchu (pola 2.2.3 / 2.2.6)
        api_223 = [
            {"tahun_data": 2025, "partai_politik": "Partai Demokrat", "agama_buddha": None, "agama_khonghucu": None, "jumlah": "7"},
            {"tahun_data": 2025, "partai_politik": "Partai Persatuan\nPembangunan (PPP)", "agama_buddha": None, "agama_khonghucu": None, "jumlah": "6"},
        ]
        pdf_223 = [
            ["Partai Politik", None, "Agama - Budha", "Agama - Konghuchu", "Jumlah"],
            ["(1)", None, "(2)", "(3)", "(4)"],
            ["1.", "Partai Demokrat", "–", "–", "7"],
            ["2.", "Partai Persatuan Pembangunan\n(PPP)", "–", "–", "6"],
            ["Jawa Tengah", None, "–", "–", "13"],
        ]
        grid_223 = normalize_api_data(api_223, pdf_rows=pdf_223, pub_year=2026)
        self.assertEqual(grid_223[-1][0], "Jawa Tengah")
        self.assertEqual(grid_223[-1][3], 13)

        def run_synth_api(code, api_recs, pdf_grid):
            payload = [{
                "id_db": code,
                "nomor_tabel": code,
                "judul_tabel": f"Synthetic {code}",
                "source_type": "api",
                "is_dynamic": 0,
                "link_tabel": f"https://example.invalid/{code}",
            }]
            idx = {code: {"nomor_tabel": code, "id_db": code, "judul_db": f"Synthetic {code}", "judul_pdf": f"Synthetic {code}", "pages": [10], "similarity": 100}}
            with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
                json.dump(payload, tf)
                db_file = tf.name
            try:
                def fake_extract(_p, info_list):
                    for info in info_list:
                        info["extracted_data"] = pdf_grid
                with patch.object(compare_table, "fetch_data_from_link", return_value=api_recs), \
                     patch.object(compare_table, "extract_table_content", side_effect=fake_extract), \
                     patch.object(compare_table, "get_cached_page_tables", return_value=pdf_grid), \
                     patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                     patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                    return compare_table.compare_head_to_head("dummy.pdf", code, db_file, id_db=code, preloaded_db_tables=payload, preloaded_index=idx)
            finally:
                os.remove(db_file)

        res_1row = run_synth_api("9.9.7", api_1row, pdf_1row)
        self.assertEqual(len(res_1row["matches"]), 4)
        self.assertEqual(len(res_1row["diffs"]), 0)
        self.assertEqual(len(res_1row["pdf_only"]), 4)

        res_223 = run_synth_api("9.9.8", api_223, pdf_223)
        self.assertEqual(len(res_223["matches"]), 9)
        self.assertEqual(len(res_223["diffs"]), 0)
        self.assertEqual(len(res_223["pdf_only"]), 0)

    def test_dinamis_vs_api_active_cols_gender_col_map_footer_and_multiyear(self):
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table
        import match_pdf
        from pdf_extractor import strip_fused_footer_lines
        from table_profiler import detect_active_data_cols, detect_col_headers

        # 1. detect_active_data_cols tetap mempertahankan kolom all-None berheader sah agar sel DB kosong terdeteksi sebagai diff,
        # sedangkan Categorized col_map memprioritaskan kolom DB terisi (2022) atas kolom kosong (2025) bila PDF tanpa tahun (pola 7.1.4/7.1.5).
        dyn_with_empty_year = [
            ["Nomor Urut", "2022 Kode Industri", "2022 Jumlah Total", "2025 Kode Industri", "2025 Jumlah Total"],
            ["(1)", "(2)", "(3)", "(4)", "(5)"],
            ["1", "10", 100, None, None],
            ["2", "11", 200, None, None],
            ["3", "12", 300, None, None],
        ]
        self.assertEqual(detect_active_data_cols(dyn_with_empty_year), [1, 2, 3, 4])
        self.assertEqual(
            detect_col_headers(dyn_with_empty_year),
            ["2022 Kode Industri", "2022 Jumlah Total", "2025 Kode Industri", "2025 Jumlah Total"],
        )

        dyn_715 = [{
            "id_db": "715",
            "nomor_tabel": "7.1.5",
            "judul_tabel": "Industri Besar Sedang",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                ["Nomor Urut", "2022 Nilai Output - Jasa Industri", "2022 Nilai Output - Penerimaan Lain", "2025 Nilai Output - Jasa Industri", "2025 Nilai Output - Penerimaan Lain"],
                ["(1)", "(2)", "(3)", "(4)", "(5)"],
                ["1", 100, 200, None, None],
                ["2", 300, 400, None, None],
            ],
        }]
        pdf_715 = [
            ["Nomor Urut", "Nilai Output", None],
            [None, "Jasa", "Penerimaan Lain"],
            ["(1)", "(2)", "(3)"],
            ["1", "100", "200"],
            ["2", "300", "400"],
        ]
        idx_715 = {"7.1.5": {"nomor_tabel": "7.1.5", "id_db": "715", "judul_db": "Industri", "judul_pdf": "Industri", "pages": [800], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_715, tf)
            db_715 = tf.name
        try:
            def _fake_ext_715(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_715
            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext_715), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_715), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                r715 = compare_table.compare_head_to_head("dummy.pdf", "7.1.5", db_715, id_db="715", preloaded_db_tables=dyn_715, preloaded_index=idx_715)
            self.assertEqual(len(r715["matches"]), 4)
            self.assertEqual(len(r715["diffs"]), 0)
        finally:
            os.remove(db_715)

        # 2. Footer PDF satu band-Y dengan baris data dibersihkan, teks multi-baris biasa tetap utuh
        raw_rows = [
            ["No", "Kabupaten/Kota", "Predikat A", "Predikat C", "Jumlah"],
            ["5.", "Pekalongan\nUtara", "–", "4", "15"],
            ["6.\nSumber/Sou", "Tegal\nrce: Perpustakaan Nasi", "1\nonal (Pusat Pembina", "24\nan Pustakawan)/N", "26\national Library"],
        ]
        cleaned_rows = strip_fused_footer_lines(raw_rows)
        self.assertEqual(cleaned_rows[1], ["5.", "Pekalongan\nUtara", "–", "4", "15"])
        self.assertEqual(cleaned_rows[2], ["6.", "Tegal", "1", "24", "26"])

        # _build_diff_entry_meta untuk pv=None harus informatif, bukan 'Selisih 0 (PDF lebih banyak)'
        d_out, d_note = compare_table._build_diff_entry_meta(None, 1, None, "1", 0.0)
        self.assertEqual(d_out, 1.0)
        self.assertNotIn("Selisih 0", d_note)

        # 3. Guard discriminator L/P & Dewasa/Anak pada Categorized col_map (pola 4.4.16)
        dyn_4416 = [{
            "id_db": "4416",
            "nomor_tabel": "4.4.16",
            "judul_tabel": "Narapidana",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                [
                    "Wilayah",
                    "Pidana Penjara - BI - Pidana >= 1 Tahun<= 3 Tahun - Dewasa - L",
                    "Pidana Penjara - BI - Pidana >= 1 Tahun<= 3 Tahun - Dewasa - P",
                    "Pidana Penjara - BI - Pidana >= 1 Tahun<= 3 Tahun - Anak - L",
                    "Pidana Penjara - BI - Pidana >= 1 Tahun<= 3 Tahun - Anak - P",
                ],
                ["(1)", "(2)", "(3)", "(4)", "(5)"],
                ["Thd Ketertiban", 48, 0, 4, 0],
                ["Pembakaran", 2, 0, 0, 0],
            ],
        }]
        pdf_4416 = [
            ["Wilayah", None, "Pidana Penjara", None, None, None],
            [None, None, "BI", None, None, None],
            [None, None, "Pidana >= 1", None, None, None],
            [None, None, "Dewasa", None, "Anak", None],
            [None, None, "L", "P", "L", "P"],
            ["(1)", None, "(2)", "(3)", "(4)", "(5)"],
            ["1.", "Thd Ketertiban", "48", "–", "4", "–"],
            ["2.", "Pembakaran", "2", "–", "–", "–"],
        ]
        idx_4416 = {"4.4.16": {"nomor_tabel": "4.4.16", "id_db": "4416", "judul_db": "Narapidana", "judul_pdf": "Narapidana", "pages": [509], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_4416, tf)
            db_4416 = tf.name
        try:
            def _fake_ext_4416(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_4416
            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext_4416), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_4416), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                r4416 = compare_table.compare_head_to_head("dummy.pdf", "4.4.16", db_4416, id_db="4416", preloaded_db_tables=dyn_4416, preloaded_index=idx_4416)
            self.assertEqual(len(r4416["matches"]), 8)
            self.assertEqual(len(r4416["diffs"]), 0)
        finally:
            os.remove(db_4416)

        # 4. Subtotal + leaf matching pada API 5.4.2 tidak boleh ketukar
        api_542 = [{
            "tahun_data": 2025,
            "produksi_kayu_bulat_perum_perhutani": "238932.36",
            "jumlah_produksi_kayu_bulat": "238932.36",
            "produksi_kayu_gergajian": "365209.80",
        }]
        pdf_542 = [
            ["Tahun", "Kayu Bulat", None, "Kayu Olahan"],
            [None, "Perum Perhutani", "Jumlah", "Kayu\nGergajian"],
            ["(1)", "(2)", "(3)", "(4)"],
            ["2025", "238.932,36", "238.932,36", "365.209,80"],
        ]
        pay_542 = [{"id_db": "542", "nomor_tabel": "5.4.2", "judul_tabel": "Kayu", "source_type": "api", "is_dynamic": 0, "link_tabel": "https://example.invalid/542"}]
        idx_542 = {"5.4.2": {"nomor_tabel": "5.4.2", "id_db": "542", "judul_db": "Kayu", "judul_pdf": "Kayu", "pages": [718], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(pay_542, tf)
            db_542 = tf.name
        try:
            def _fake_ext_542(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_542
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_542), \
                 patch.object(compare_table, "extract_table_content", side_effect=_fake_ext_542), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_542), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                r542 = compare_table.compare_head_to_head("dummy.pdf", "5.4.2", db_542, id_db="542", preloaded_db_tables=pay_542, preloaded_index=idx_542)
            self.assertEqual(len(r542["matches"]), 3)
            self.assertEqual(len(r542["diffs"]), 0)
        finally:
            os.remove(db_542)

        # 5. Multi-year Regional subrows (pola 9.1.2) menyandingkan semua tahun yang ada di DB (2023, 2024, 2025)
        dyn_912 = [{
            "id_db": "912",
            "nomor_tabel": "9.1.2",
            "judul_tabel": "Kendaraan",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                ["Kabupaten/Kota", "Mobil Penumpang (2023)", "Mobil Penumpang (2024)", "Mobil Penumpang (2025)", "Bus (2023)", "Bus (2024)", "Bus (2025)"],
                ["(1)", "(2)", "(3)", "(4)", "(5)", "(6)", "(7)"],
                ["Cilacap", 56423, 59962, 62016, 688, 743, 775],
                ["Banyumas", 73692, 77121, 79057, 1584, 1666, 1707],
            ],
        }]
        pdf_912 = [
            ["Kabupaten/Kota", None, "Akhir Tahun", "Mobil Penumpang", "Bus"],
            ["(1)", None, "(2)", "(3)", "(4)"],
            ["1.", "Cilacap", "2023", "56.423", "688"],
            ["", "", "2024", "59.962", "743"],
            ["", "", "2025", "62.016", "775"],
            ["2.", "Banyumas", "2023", "73.692", "1.584"],
            ["", "", "2024", "77.121", "1.666"],
            ["", "", "2025", "79.057", "1.707"],
        ]
        idx_912 = {"9.1.2": {"nomor_tabel": "9.1.2", "id_db": "912", "judul_db": "Kendaraan", "judul_pdf": "Kendaraan", "pages": [918], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_912, tf)
            db_912 = tf.name
        try:
            def _fake_ext_912(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_912
            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext_912), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_912), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                r912 = compare_table.compare_head_to_head("dummy.pdf", "9.1.2", db_912, id_db="912", preloaded_db_tables=dyn_912, preloaded_index=idx_912)
            self.assertEqual(len(r912["matches"]), 12)
            self.assertEqual(len(r912["diffs"]), 0)
            self.assertEqual(len(r912["pdf_only"]), 0)
        finally:
            os.remove(db_912)

    def test_batch_verify_preserves_newer_external_sync_state(self):
        import json
        import shutil
        import tempfile
        import time
        from unittest.mock import patch
        import batch_verify

        tmp_dir = tempfile.mkdtemp(prefix="batch_race_test_")
        try:
            matched_p = os.path.join(tmp_dir, "dda_matched_2026.json")
            db_p = os.path.join(tmp_dir, "dda_db_2026.json")
            state_p = os.path.join(tmp_dir, "dda_state_2026.json")
            prog_p = os.path.join(tmp_dir, "dda_progress_2026.json")

            with open(matched_p, "w", encoding="utf-8") as f:
                json.dump({"matched_in_pdf": [
                    {"nomor_tabel": "4.1.8", "id_db": "364", "source_type": "dinamis", "is_dynamic": 1, "dynamic_empty": False},
                    {"nomor_tabel": "1.1.1", "id_db": "862", "source_type": "dinamis", "is_dynamic": 1, "dynamic_empty": False},
                ]}, f)
            with open(db_p, "w", encoding="utf-8") as f:
                json.dump([{"id_db": "364", "nomor_tabel": "4.1.8"}, {"id_db": "862", "nomor_tabel": "1.1.1"}], f)
            with open(state_p, "w", encoding="utf-8") as f:
                json.dump({"4.1.8": {"status": "diff", "diff_count": 324, "match_count": 324, "updated_at": "2026-11-18 13:00:00", "updated_epoch": 100.0}}, f)

            def fake_compare(_pdf, t_num, _db, **_kwargs):
                if t_num == "4.1.8":
                    # Simulasi PHP sync_pdf_to_dinamis menulis status 'match' terbaru tepat saat worker sedang memproses 4.1.8
                    time.sleep(0.05)
                    with open(state_p, "w", encoding="utf-8") as sf:
                        json.dump({
                            "4.1.8": {
                                "nomor_tabel": "4.1.8",
                                "status": "match",
                                "diff_count": 0,
                                "match_count": 648,
                                "updated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
                                "updated_epoch": round(time.time() + 1.0, 4),
                            }
                        }, sf)
                    return {"status": "success", "matches": [1] * 324, "diffs": [1] * 324, "pdf_only": [], "summary": {"total_matches": 324, "total_diffs": 324, "total_pdf_only": 0}}
                return {"status": "success", "matches": [1] * 10, "diffs": [], "pdf_only": [], "summary": {"total_matches": 10, "total_diffs": 0, "total_pdf_only": 0}}

            with patch.object(batch_verify, "compare_head_to_head", side_effect=fake_compare):
                rc = batch_verify.run_batch("dummy.pdf", matched_p, db_p, state_p, prog_p, tolerance=0.0, max_workers=2, source_filter="dinamis")
            self.assertEqual(rc, 0)
            with open(state_p, "r", encoding="utf-8") as f:
                final_state = json.load(f)
            self.assertEqual(final_state["4.1.8"]["status"], "match")
            self.assertEqual(final_state["4.1.8"]["diff_count"], 0)
            self.assertEqual(final_state["4.1.8"]["match_count"], 648)
            self.assertEqual(final_state["1.1.1"]["status"], "match")
        finally:
            shutil.rmtree(tmp_dir, ignore_errors=True)

    def test_categorized_empty_db_cell_matches_pdf_na_dots_but_not_explicit_zero(self):
        """Mode Categorized wajib menjaga sel DB kosong tetap None agar cocok dengan NA/'...',
        sambil tetap membedakan 0 eksplisit vs '...' dan menjaga ellipsis ber-watermark tidak jatuh ke 0.0."""
        import json, os, tempfile
        from unittest.mock import patch
        import compare_table, match_pdf

        self.assertEqual(parse_num("NA", is_pdf=True), "...")
        self.assertEqual(parse_num("j…", is_pdf=True), "...")
        self.assertEqual(parse_num("–", is_pdf=True), 0.0)

        dyn_tbl = [{
            "id_db": "9901",
            "nomor_tabel": "3.2.99",
            "judul_tabel": "Pendapatan Kategori",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                ["Kelompok Umur", "Sektor 1", "Sektor 2", "Sektor 3"],
                ["(1)", "(2)", "(3)", "(4)"],
                ["15-24", None, 1500000, ""],
                ["25-34", 0, 2000000, None],
            ],
        }]
        pdf_tbl = [
            ["Kelompok Umur", "Sektor 1", "Sektor 2", "Sektor 3"],
            ["(1)", "(2)", "(3)", "(4)"],
            ["15-24", "NA", "1.500.000", "..."],
            ["25-34", "NA", "2.000.000", "2.500.000"],
        ]
        idx_tbl = {
            "3.2.99": {
                "nomor_tabel": "3.2.99",
                "id_db": "9901",
                "judul_db": "Pendapatan Kategori",
                "judul_pdf": "Pendapatan Kategori",
                "pages": [286],
                "similarity": 100,
            }
        }
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_tbl, tf)
            db_path = tf.name
        try:
            def _fake_ext(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_tbl

            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_tbl), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head(
                    "dummy.pdf", "3.2.99", db_path, id_db="9901",
                    preloaded_db_tables=dyn_tbl, preloaded_index=idx_tbl
                )
            # 15-24: (NA vs None -> match), (1.5M vs 1.5M -> match), (... vs "" -> match)
            # 25-34: (NA vs 0 -> match via simbol ekuivalen), (2M vs 2M -> match), (2.5M vs None -> diff!)
            self.assertEqual(len(res["matches"]), 5)
            self.assertEqual(len(res["diffs"]), 1)
            diff_pairs = {(d["pdf_val"], d["src_val"]) for d in res["diffs"]}
            self.assertIn((2500000, None), diff_pairs)
        finally:
            os.remove(db_path)

    def test_fase1_toc_spaces_no_hardcode_and_generic_univ_detection(self):
        """Pastikan Fase 1B bebas hardcode: parse_toc dukung spasi maupun tab,
        koreksi nomor 2-segmen berjalan generik, dan tabel universitas terdeteksi tanpa nomor hardcode."""
        import json, os, tempfile
        from unittest.mock import patch
        import compare_table, match_pdf

        # 1. NUM_AT_LINE_START cocok baik untuk pemisah tab maupun spasi, namun tidak salah tangkap angka desimal
        toc_sample = (
            "1.1.1\tLuas Daerah Menurut Kabupaten/Kota ... 12\n"
            "1.1.2   Tinggi Wilayah di Atas Permukaan Laut ... 14\n"
            "2.5.1 Jumlah Akta Pencatatan Sipil ... 90\n"
            "1.5 persen penduduk bekerja\n"
        )
        nums = [m.group(1) for m in match_pdf.NUM_AT_LINE_START.finditer(toc_sample)]
        self.assertEqual(nums, ["1.1.1", "1.1.2", "2.5.1"])

        # 2. Deteksi tabel universitas berjalan generik meskipun nomor tabel bergeser (mis. 4.1.99 di PDF 2027)
        dyn_univ = [{
            "id_db": "9921",
            "nomor_tabel": "4.1.99",
            "judul_tabel": "Banyaknya Mahasiswa dan Dosen Pada Perguruan Tinggi Swasta di Jawa Tengah, Tahunan",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                ["Perguruan Tinggi Swasta", "Dosen", "Mahasiswa"],
                ["(1)", "(2)", "(3)"],
                ["Universitas Islam Sultan Agung Semarang", 520, 14500],
                ["Universitas Muhammadiyah Surakarta", 810, 31000],
                ["Universitas Muhammadiyah Magelang", 210, 5400],
            ],
        }]
        pdf_univ = [
            ["Perguruan Tinggi Swasta", "Dosen", "Mahasiswa"],
            ["(1)", "(2)", "(3)"],
            ["Universitas Islam Sultan Agung Semarang", "520", "14.500"],
            ["Universitas Muhammadiyah Surakarta", "810", "31.000"],
            ["Universitas Muhammadiyah Magelang", "210", "5.400"],
        ]
        idx_univ = {
            "4.1.99": {
                "nomor_tabel": "4.1.99",
                "id_db": "9921",
                "judul_db": dyn_univ[0]["judul_tabel"],
                "judul_pdf": dyn_univ[0]["judul_tabel"],
                "pages": [400],
                "similarity": 100,
            }
        }
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_univ, tf)
            db_p = tf.name
        try:
            def _fake_ext(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_univ

            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_univ), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2027):
                r_univ = compare_table.compare_head_to_head(
                    "dummy_2027.pdf", "4.1.99", db_p, id_db="9921",
                    preloaded_db_tables=dyn_univ, preloaded_index=idx_univ
                )
            self.assertEqual(len(r_univ["matches"]), 6)
            self.assertEqual(len(r_univ["diffs"]), 0)
        finally:
            os.remove(db_p)

    def test_fase2_strict_object_and_measure_guard_2027_scenarios(self):
        """Pastikan Fase 2 menolak 100% tabrakan nomor beda topik/ukuran/jenjang,
        namun tetap menjodohkan tabel yang bergeser nomor dengan objek & ukuran yang sama."""
        import match_pdf

        # 1. Nomor sama (4.1.5), tapi topik & objek inti berbeda total -> WAJIB ditolak 100%
        toc_collision = {
            "4.1.5": {"judul_pdf": "Jumlah Perpustakaan Digital Menurut Kabupaten/Kota di Provinsi Jawa Tengah, 2026", "pages": [330]},
            "4.1.7": {"judul_pdf": "Jumlah Sekolah, Guru, dan Murid Sekolah Menengah Pertama (SMP) di Bawah Kemendikdasmen Menurut Kabupaten/Kota di Provinsi Jawa Tengah, 2025/2026", "pages": [335]},
            "5.2.1": {"judul_pdf": "Produksi Padi Menurut Kabupaten/Kota di Provinsi Jawa Tengah, 2026", "pages": [540]},
            "5.1": {"judul_pdf": "Produksi Buah-Buahan dan Sayuran Tahunan Menurut Jenis Tanaman di Provinsi Jawa Tengah, 2026", "pages": [538]},
        }
        db_tables = [
            {
                "id_db": "101",
                "nomor_tabel": "4.1.5",
                "judul_tabel": "Jumlah Satuan Pendidikan, Pendidik, dan Peserta Didik Sekolah Menengah Pertama (SMP) di Bawah Kemendikdasmen Menurut Kabupaten/Kota di Provinsi Jawa Tengah, Tahunan",
                "source_type": "dinamis",
                "is_dynamic": 1,
            },
            {
                "id_db": "102",
                "nomor_tabel": "5.2.1",
                "judul_tabel": "Luas Panen Padi Menurut Kabupaten/Kota di Provinsi Jawa Tengah, Tahunan",
                "source_type": "dinamis",
                "is_dynamic": 1,
            },
            {
                "id_db": "103",
                "nomor_tabel": "5.2.99",
                "judul_tabel": "Produksi Tanaman Hias Menurut Jenis Tanaman (tangkai) di Provinsi Jawa Tengah, Tahunan",
                "source_type": "dinamis",
                "is_dynamic": 1,
            },
        ]
        res = match_pdf.match_tables(toc_collision, db_tables)
        matched_by_id = {m["id_db"]: m for m in res["matched_in_pdf"]}
        missing_ids = {m["id_db"] for m in res["missing_in_pdf"]}
        unreg_nums = {u["nomor_tabel"] for u in res["unregistered_in_db"]}

        # DB 101 (SMP, dulu 4.1.5) HARUS menolak PDF 4.1.5 (Perpustakaan) dan otomatis pindah ke PDF 4.1.7!
        self.assertIn("101", matched_by_id)
        self.assertEqual(matched_by_id["101"]["nomor_tabel"], "4.1.7")
        self.assertTrue(matched_by_id["101"]["nomor_bergeser"])
        self.assertIn("4.1.5", unreg_nums)

        # DB 102 (Luas Panen Padi, 5.2.1) HARUS menolak PDF 5.2.1 (Produksi Padi) meski nomor & komoditas sama!
        self.assertNotIn("102", matched_by_id)
        self.assertIn("102", missing_ids)
        self.assertIn("5.2.1", unreg_nums)

        # DB 103 (Tanaman Hias) HARUS menolak PDF 5.1 (Buah-Buahan dan Sayuran)
        self.assertNotIn("103", matched_by_id)
        self.assertIn("103", missing_ids)
        self.assertIn("5.1", unreg_nums)

        # 2. Konflik jenjang pendidikan (SD vs SMP), kementerian (Kemendik vs Kemenag), PDRB (Berlaku vs Konstan)
        self.assertTrue(match_pdf._has_semantic_conflict(
            "Jumlah Sekolah Dasar (SD) Menurut Kabupaten/Kota",
            "Jumlah Sekolah Menengah Pertama (SMP) Menurut Kabupaten/Kota"
        ))
        self.assertTrue(match_pdf._has_semantic_conflict(
            "Jumlah Madrasah Ibtidaiyah di Bawah Kementerian Agama Menurut Kabupaten/Kota",
            "Jumlah Sekolah Dasar di Bawah Kementerian Pendidikan Dasar dan Menengah Menurut Kabupaten/Kota"
        ))
        self.assertTrue(match_pdf._has_semantic_conflict(
            "PDRB Atas Dasar Harga Berlaku Menurut Lapangan Usaha",
            "PDRB Atas Dasar Harga Konstan 2010 Menurut Lapangan Usaha"
        ))
        # 3. Volume Produksi vs Produksi (seperti 5.6.2/5.6.7) TIDAK boleh konflik
        self.assertFalse(match_pdf._has_semantic_conflict(
            "Produksi dan Nilai Produksi Perikanan Budidaya Menurut Kabupaten/Kota",
            "Volume Produksi dan Nilai Produksi Perikanan Budidaya Menurut Kabupaten/Kota"
        ))

    def test_fase3_multiline_bilingual_headers_and_explicit_year_hierarchy(self):
        """Pastikan Fase 3 merekonstruksi header multi-baris secara utuh (Mobil Penumpang/Barang, Kayu Gergajian, Bahan Baku/Bakar)
        dan mengisi field eksplisit 'tahun' serta 'kolom_hierarki' pada setiap item hasil komparasi."""
        import json, os, tempfile
        from unittest.mock import patch
        import compare_table, match_pdf
        from normalizer import clean_header_text

        self.assertEqual(clean_header_text("Mobil\nPenumpang\nPassenger\nCars"), "Mobil Penumpang")
        self.assertEqual(clean_header_text("Mobil\nBarang\nTrucks"), "Mobil Barang")
        self.assertEqual(clean_header_text("Sepeda\nMotor\nMotorcycles"), "Sepeda Motor")
        self.assertEqual(clean_header_text("Kayu\nGergajian\nSawn Timber"), "Kayu Gergajian")
        self.assertEqual(clean_header_text("Bahan Baku/\nBakar\nRaw Material/\nFuel"), "Bahan Baku/Bakar")
        self.assertEqual(clean_header_text("Pembenihan (ribu\nekor)\nHatchery (thousand\nheads)"), "Pembenihan (ribu ekor)")
        self.assertEqual(clean_header_text("Jumlah\nTotal"), "Jumlah")

        dyn_tbl = [{
            "id_db": "9931",
            "nomor_tabel": "4.1.3",
            "judul_tabel": "Sekolah Dasar",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_grid": [
                ["Kabupaten/Kota", "2024/2025 Satuan Pendidikan - Negeri", "2025/2026 Satuan Pendidikan - Negeri"],
                ["(1)", "(2)", "(3)"],
                ["Cilacap", 970, 969],
            ],
        }]
        pdf_tbl = [
            ["Kabupaten/Kota", "Satuan Pendidikan", None],
            [None, "Negeri\nPublic", None],
            [None, "2024/2025", "2025/2026"],
            ["(1)", "(2)", "(3)"],
            ["1. Cilacap", "972", "969"],
        ]
        idx_tbl = {
            "4.1.3": {
                "nomor_tabel": "4.1.3",
                "id_db": "9931",
                "judul_db": "Sekolah Dasar",
                "judul_pdf": "Sekolah Dasar",
                "pages": [323],
                "similarity": 100,
            }
        }
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(dyn_tbl, tf)
            db_p = tf.name
        try:
            def _fake_ext(_p, info_list):
                for info in info_list:
                    info["extracted_data"] = pdf_tbl

            with patch.object(compare_table, "extract_table_content", side_effect=_fake_ext), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_tbl), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res = compare_table.compare_head_to_head(
                    "dummy.pdf", "4.1.3", db_p, id_db="9931",
                    preloaded_db_tables=dyn_tbl, preloaded_index=idx_tbl
                )
            self.assertEqual(len(res["diffs"]), 1)
            self.assertEqual(len(res["matches"]), 1)
            d0 = res["diffs"][0]
            self.assertEqual(d0["tahun"], "2024/2025")
            self.assertEqual(d0["kolom_hierarki"], ["Satuan Pendidikan", "Negeri"])
            self.assertEqual(res["matches"][0]["tahun"], "2025/2026")
        finally:
            os.remove(db_p)

    def test_fase4_matrix_excel_multilevel_headers_order_and_anti_overwrite(self):
        """Pastikan Fase 4 merender header bertingkat merged di Excel Matriks, mempertahankan urutan subtotal di tengah,
        mencantumkan Tahun Data di banner, dan tidak menimpa kolom berbeda yang berlabel sama."""
        import os, tempfile
        import openpyxl
        import export_excel

        wb = openpyxl.Workbook()
        ws = wb.active
        fake_res = {
            "status": "success",
            "source_type": "Tabel Dinamis",
            "target_year": "2025",
            "matches": [
                {"wilayah": "Kab. Cilacap", "metric": "Satuan Pendidikan - Negeri", "kolom_hierarki": ["Satuan Pendidikan", "Negeri"], "tahun": "2025", "pdf_val": 969, "src_val": 969},
                {"wilayah": "Kab. Cilacap", "metric": "Satuan Pendidikan - Jumlah", "kolom_hierarki": ["Satuan Pendidikan", "Jumlah"], "tahun": "2025", "pdf_val": 1033, "src_val": 1033},
                {"wilayah": "Kab. Cilacap", "metric": "Pendidik - Negeri", "kolom_hierarki": ["Pendidik", "Negeri"], "tahun": "2025", "pdf_val": 8321, "src_val": 8321},
            ],
            "diffs": [
                # Dua kolom berbeda yang sama-sama berlabel 'Lainnya' (simulasi anti-overwrite)
                {"wilayah": "Kab. Cilacap", "metric": "Pendidik - Swasta", "kolom_hierarki": ["Pendidik", "Swasta"], "tahun": "2025", "pdf_val": 814, "src_val": 800, "diff": -14},
                {"wilayah": "Kab. Cilacap", "metric": "Lainnya", "kolom_hierarki": ["Lainnya"], "tahun": "2025", "pdf_val": 10, "src_val": 12, "diff": 2},
                {"wilayah": "Kab. Cilacap", "metric": "Lainnya", "kolom_hierarki": ["Lainnya"], "tahun": "2025", "pdf_val": 25, "src_val": 30, "diff": 5},
            ],
            "pdf_only": [],
        }
        export_excel.populate_matrix_sheet(ws, "4.1.3", "Jumlah Sekolah SD", "Tabel Dinamis", fake_res, instansi="Dinas Pendidikan")

        # 1. Banner memuat Sumber Data + Tahun Data
        self.assertIn("Tahun Data: 2025", str(ws.cell(2, 1).value))
        # 2. Header bertingkat: row 7 = grup atas ('Satuan Pendidikan' merge B7:E7, 'Pendidik' merge F7:I7), row 8 = daun, row 9 = PDF/Database, row 10 = data
        self.assertEqual(ws.cell(7, 2).value, "Satuan Pendidikan")
        self.assertEqual(ws.cell(8, 2).value, "Negeri")
        # Subtotal 'Jumlah' tetap di urutan ke-2 (kolom D-E), TIDAK dilempar ke ujung kanan!
        self.assertEqual(ws.cell(8, 4).value, "Jumlah")
        self.assertEqual(ws.cell(7, 6).value, "Pendidik")
        self.assertEqual(ws.cell(8, 6).value, "Negeri")
        self.assertEqual(ws.cell(8, 8).value, "Swasta")
        self.assertEqual(ws.cell(9, 2).value, "PDF")
        self.assertEqual(ws.cell(9, 3).value, "Database")
        # 3. Anti-timpa: kedua sel 'Lainnya' tersimpan di 2 kolom terpisah ('Lainnya' dan 'Lainnya (2)'), keduanya ada di baris 10!
        self.assertEqual(ws.cell(7, 10).value, "Lainnya")
        self.assertEqual(ws.cell(7, 12).value, "Lainnya (2)")
        self.assertEqual(ws.cell(10, 10).value, 10)
        self.assertEqual(ws.cell(10, 12).value, 25)

        # 4. Pastikan workbook valid disimpan & dibaca ulang oleh openpyxl
        with tempfile.NamedTemporaryFile(suffix=".xlsx", delete=False) as tf:
            out_xlsx = tf.name
        try:
            wb.save(out_xlsx)
            wb2 = openpyxl.load_workbook(out_xlsx)
            self.assertEqual(wb2.active.cell(10, 1).value, "Kab. Cilacap")
            wb2.close()
        finally:
            if os.path.exists(out_xlsx):
                os.remove(out_xlsx)

    def test_opsi_a_api_dots_vs_zero_matches_while_dinamis_stays_strict(self):
        """
        Opsi A (Hybrid Symbol Equivalence):
        - Pada tabel sumber API Satudata (is_dynamic=0), PDF '...' vs API 0 disetarakan sebagai Cocok (Match, is_api_symbol_equiv=True).
        - Pada tabel sumber Tabel Dinamis (is_dynamic=1), PDF '...' vs Database 0 tetap diproteksi sebagai Selisih (Diff).
        """
        import json
        import os
        import tempfile
        from unittest.mock import patch
        import compare_table
        import match_pdf

        pdf_rows = [
            ["Kabupaten/Kota", "Jumlah Panti", "Jumlah Penghuni"],
            ["(1)", "(2)", "(3)"],
            ["Kab. Cilacap", "1", "..."],
            ["Kab. Kebumen", "1", "50"],
        ]

        # 1. Uji sumber API Satudata (is_dynamic=0)
        api_rows = [
            {"tahun_data": 2025, "kode_wilayah": "33.01", "nama_wilayah": "Kab. Cilacap", "jumlah_panti": "0", "jumlah_penghuni": "0"},
            {"tahun_data": 2025, "kode_wilayah": "33.05", "nama_wilayah": "Kab. Kebumen", "jumlah_panti": "1", "jumlah_penghuni": "50"},
        ]
        api_payload = [{
            "id_db": "4522",
            "nomor_tabel": "4.5.22",
            "judul_tabel": "Banyaknya Panti Karya",
            "source_type": "api",
            "is_dynamic": 0,
            "link_tabel": "https://example.invalid/4522",
        }]
        _entry_api = {"nomor_tabel": "4.5.22", "id_db": "4522", "pages": [570], "similarity": 100}
        fake_idx_api = {"4.5.22": _entry_api, "4522": _entry_api}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(api_payload, tf)
            api_file = tf.name
        try:
            with patch.object(compare_table, "fetch_data_from_link", return_value=api_rows), \
                 patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_api = compare_table.compare_head_to_head("dummy.pdf", "4.5.22", api_file, id_db="4522", preloaded_db_tables=api_payload, preloaded_index=fake_idx_api)
        finally:
            os.remove(api_file)

        # Pada API Satudata: Cilacap '1' vs '0' tetap diff (1 diff), sedangkan Cilacap '...' vs '0' menjadi match (is_api_symbol_equiv=True) bersama 2 sel Kebumen (total 3 matches)!
        self.assertEqual(res_api["source_type"], "API Satudata")
        self.assertEqual(len(res_api["diffs"]), 1)
        self.assertEqual(res_api["diffs"][0]["pdf_val"], 1)
        self.assertEqual(len(res_api["matches"]), 3)
        equiv_matches = [m for m in res_api["matches"] if m.get("is_api_symbol_equiv")]
        self.assertEqual(len(equiv_matches), 1)
        self.assertEqual(equiv_matches[0]["pdf_val"], "...")

        # 2. Uji sumber Tabel Dinamis (is_dynamic=1): '...' vs '0' disetarakan Match,
        # namun nilai non-nol sekecil apa pun (mis. '0.001' vs '...' atau '1' vs '0') WAJIB tetap Diff!
        din_grid = [
            ["Kabupaten/Kota", "Jumlah Panti", "Jumlah Penghuni"],
            ["Kab. Cilacap", "0", "0"],
            ["Kab. Kebumen", "1", "50"],
            ["Kab. Banyumas", "0.001", "0"],
        ]
        pdf_rows_din = [
            ["Kabupaten/Kota", "Jumlah Panti", "Jumlah Penghuni"],
            ["(1)", "(2)", "(3)"],
            ["Kab. Cilacap", "1", "..."],
            ["Kab. Kebumen", "1", "50"],
            ["Kab. Banyumas", "...", "0"],
        ]
        din_payload = [{
            "id_db": "3223",
            "nomor_tabel": "3.2.23",
            "judul_tabel": "Tabel Dinamis Uji Simbol",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": din_grid,
        }]
        _entry_din = {"nomor_tabel": "3.2.23", "id_db": "3223", "pages": [200], "similarity": 100}
        fake_idx_din = {"3.2.23": _entry_din, "3223": _entry_din}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(din_payload, tf)
            din_file = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_rows_din})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_rows_din), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_din = compare_table.compare_head_to_head("dummy.pdf", "3.2.23", din_file, id_db="3223", preloaded_db_tables=din_payload, preloaded_index=fake_idx_din)
        finally:
            os.remove(din_file)

        # Cilacap ('1' vs '0') & Banyumas ('...' vs '0.001') WAJIB tetap Diff (2 diffs);
        # Cilacap ('...' vs '0'), Kebumen (1, 50), Banyumas (0 vs 0) -> 4 matches!
        self.assertEqual(res_din["source_type"], "Tabel Dinamis")
        self.assertEqual(len(res_din["diffs"]), 2)
        self.assertEqual(len(res_din["matches"]), 4)
        self.assertTrue(any(m.get("is_symbol_equiv") for m in res_din["matches"]))

    def test_watermark_on_bps_numbering_and_year_subheader_preserved(self):
        """
        Pastikan watermark diagonal BPS pada baris nomor kolom '(1)..(7)' (mis. 'd\\n(5)', '.\\no\\n(3)')
        dan pada sel tahun header ('d\\n2022i') tidak merusak deteksi baris BPS maupun memotong tahun sub-header.
        """
        import table_profiler
        from normalizer import clean_header_text

        # 1. Pola 1.1.3 PDF 2024: watermark 'd\\n(5)' di baris nomor kolom BPS
        rows_113 = [
            ["Kabupaten/Kota\nRegency/Municipality", None, "Tepi Laut\nCoastal Altitude", None, None, "Bukan Tepi Laut\nNon-Coastal", None, None],
            [None, None, "2014", "2018", "2021", "2014", "2018", "2021"],
            ["(1)", None, "(2)", "(3)", "(4)", "d\n(5)", "(6)", "(7)"],
            ["1.", "Cilacap", "36", "36", "34", "o\n248", "248", "250"],
        ]
        bps_idx, bps_cells, rtype = table_profiler.find_bps_numbering_row(rows_113)
        self.assertEqual(bps_idx, 2)
        self.assertEqual(rtype, "bps")
        hdrs = table_profiler.extract_pdf_headers(rows_113, 6)
        self.assertEqual(hdrs, [
            "Tepi Laut - 2014", "Tepi Laut - 2018", "Tepi Laut - 2021",
            "Bukan Tepi Laut - 2014", "Bukan Tepi Laut - 2018", "Bukan Tepi Laut - 2021",
        ])

        # 2. Pola tanpa baris (1)..(7) (murni row_type='year'): baris tahun tetap ikut masuk ke header
        rows_year_only = [
            ["Kabupaten/Kota", "Tepi Laut", None, None],
            [None, "2014", "2018", "2021"],
            ["Cilacap", "36", "36", "34"],
        ]
        hdrs_yr = table_profiler.extract_pdf_headers(rows_year_only, 3)
        self.assertEqual(hdrs_yr, ["Tepi Laut - 2014", "Tepi Laut - 2018", "Tepi Laut - 2021"])

        # 3. Pola 1.2.1 PDF 2024: watermark pada '(3)' dan 'd\\n2022i' serta 'i\\n.\\no\\n(3)' dan '.\\ns'
        rows_121 = [
            ["Unsur Iklim\nClimate Elements", "2021", "d\n2022i", "2023"],
            ["(1)", "(2)", ".\no\n(3)", "(4)"],
            ["Suhu Minimum", "23,1", "23,5", "24,1"],
        ]
        bps_idx2, _, rtype2 = table_profiler.find_bps_numbering_row(rows_121)
        self.assertEqual(bps_idx2, 1)
        self.assertEqual(rtype2, "bps")
        self.assertEqual(clean_header_text("d\n2022i"), "2022")
        self.assertEqual(table_profiler._clean_bps_or_year_cell("i\n.\no\n(3)"), "(3)")
        self.assertEqual(table_profiler.extract_pdf_headers(rows_121, 3), ["Tahun 2021", "Tahun 2022", "Tahun 2023"])
        # Fragmen watermark titik tunggal '.\ns' / '.' WAJIB None (bukan 0.0), sementara '-' tetap 0.0
        self.assertIsNone(parse_num(".\ns", is_pdf=True))
        self.assertIsNone(parse_num(".", is_pdf=True))
        self.assertEqual(parse_num("–", is_pdf=True), 0.0)

    def test_gender_lp_column_abbreviations_and_mixed_sections(self):
        """
        Verifikasi regresi:
        1. Singkatan kolom API 'L' & 'P' terpetakan presisi ke kolom PDF 'Jenis Kelamin - Laki-Laki' & 'Jenis Kelamin - Perempuan' (kasus 2.2.1).
        2. Seksi baris campuran ('Laki-Laki', 'Perempuan', 'Perkotaan', 'h\\nPerdesaan/Rural', 'Jumlah/Total') terpisah bersih tanpa bocor antar-seksi (kasus 4.1.14).
        3. Sel kosong melompong ('') pada >75% baris tabel Regional tetap terkunci ke indeks kolom BPS '(1)..(4)' tanpa menggeser kolom kanan ke kiri.
        4. Paginasi fetcher API menarik seluruh halaman jika total_pages > 1 dan berhenti aman saat halaman habis.
        """
        import json
        import tempfile
        from unittest.mock import patch
        import compare_table
        import fetcher
        import match_pdf

        # 1. Uji singkatan kolom L & P vs Laki-Laki & Perempuan
        pdf_lp = [
            ["Partai Politik\nPolitical Parties", None, "Jenis Kelamin/Sex", None, None],
            [None, None, "Laki-Laki\nMale", "Perempuan\nFemale", "Jumlah\nTotal"],
            ["", "(1)", "(2)", "(3)", "(4)"],
            ["1.", "Partai Demokrasi Indonesia Perjuangan (PDI-P)", "25", "8", "33"],
            ["2.", "Partai Kebangkitan Bangsa (PKB)", "15", "5", "20"],
        ]
        api_lp_grid = [
            ["asal_partai_politik", "L", "P", "Jumlah"],
            ["Partai Demokrasi Indonesia Perjuangan (PDI-P)", 25, 8, 33],
            ["Partai Kebangkitan Bangsa (PKB)", 15, 5, 20],
        ]
        lp_payload = [{
            "id_db": "9221",
            "nomor_tabel": "2.2.1",
            "judul_tabel": "Jumlah Anggota DPRD Menurut Partai dan Jenis Kelamin",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": api_lp_grid,
        }]
        idx_lp = {"2.2.1": {"nomor_tabel": "2.2.1", "id_db": "9221", "pages": [114], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(lp_payload, tf)
            lp_file = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_lp})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_lp), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_lp = compare_table.compare_head_to_head("dummy.pdf", "2.2.1", lp_file, id_db="9221", preloaded_db_tables=lp_payload, preloaded_index=idx_lp)
        finally:
            os.remove(lp_file)
        self.assertEqual(len(res_lp["matches"]), 6)
        self.assertEqual(len(res_lp["diffs"]), 0)
        self.assertEqual(len(res_lp["pdf_only"]), 0)

        # 2. Uji seksi campuran Laki-Laki, Perempuan, Perkotaan, h\nPerdesaan/Rural, Jumlah/Total
        pdf_sec = [
            ["Kelompok Umur", "2024", "2025"],
            ["(1)", "(2)", "(3)"],
            ["Laki-Laki/Male", None, None],
            ["15–24", "99,88", "99,88"],
            ["s\np\nPerempuan/Female", None, None],
            ["15–24", "99,99", "99,99"],
            ["t\na\nPerkotaan/Urban", None, None],
            ["15–24", "99,94", "99,95"],
            ["h\nPerdesaan/Rural", None, None],
            ["15–24", "99,93", "99,92"],
            ["Jumlah/Total", None, None],
            ["15–24", "99,93", "99,94"],
        ]
        db_sec = [
            ["Kelompok Umur", "2024", "2025"],
            ["(1)", "(2)", "(3)"],
            ["Laki-Laki", None, None],
            ["15–24", 99.88, 99.88],
            ["Perempuan", None, None],
            ["15–24", 99.99, 99.99],
            ["Perkotaan", None, None],
            ["15–24", 99.94, 99.95],
            ["Perdesaan", None, None],
            ["15–24", 99.93, 99.92],
            ["Jumlah", None, None],
            ["15–24", 99.93, 99.94],
        ]
        sec_payload = [{
            "id_db": "9414",
            "nomor_tabel": "4.1.14",
            "judul_tabel": "Angka Melek Aksara",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_sec,
        }]
        idx_sec = {"4.1.14": {"nomor_tabel": "4.1.14", "id_db": "9414", "pages": [353], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(sec_payload, tf)
            sec_file = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_sec})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_sec), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_sec = compare_table.compare_head_to_head("dummy.pdf", "4.1.14", sec_file, id_db="9414", preloaded_db_tables=sec_payload, preloaded_index=idx_sec)
        finally:
            os.remove(sec_file)
        self.assertEqual(len(res_sec["matches"]), 10)
        self.assertEqual(len(res_sec["diffs"]), 0)
        wil_set = {m["wilayah"] for m in res_sec["matches"]}
        self.assertIn("Perdesaan (15 - 24 Tahun)", wil_set)
        self.assertIn("Jumlah (15 - 24 Tahun)", wil_set)

        # 3. Uji tabel Regional dengan kolom tengah kosong melompong ('') di >80% kabupaten
        pdf_reg_sparse = [
            ["Kabupaten/Kota", None, "SD", "SMP", "SMA"],
            ["(1)", None, "(2)", "(3)", "(4)"],
            ["1.", "Cilacap", "10", "", "30"],
            ["2.", "Banyumas", "11", "", "31"],
            ["3.", "Purbalingga", "12", "", "32"],
            ["4.", "Banjarnegara", "13", "", "33"],
            ["5.", "Kebumen", "14", "", "34"],
            ["6.", "Purworejo", "15", "25", "35"],
        ]
        db_reg_sparse = [
            ["Kabupaten/Kota", "SD", "SMP", "SMA"],
            ["Kab. Cilacap", 10, 0, 30],
            ["Kab. Banyumas", 11, 0, 31],
            ["Kab. Purbalingga", 12, 0, 32],
            ["Kab. Banjarnegara", 13, 0, 33],
            ["Kab. Kebumen", 14, 0, 34],
            ["Kab. Purworejo", 15, 25, 35],
        ]
        reg_payload = [{
            "id_db": "9901",
            "nomor_tabel": "4.1.1",
            "judul_tabel": "Sekolah Uji Sparse",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_reg_sparse,
        }]
        idx_reg = {"4.1.1": {"nomor_tabel": "4.1.1", "id_db": "9901", "pages": [300], "similarity": 100}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(reg_payload, tf)
            reg_file = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_reg_sparse})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_reg_sparse), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_REGIONAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_reg = compare_table.compare_head_to_head("dummy.pdf", "4.1.1", reg_file, id_db="9901", preloaded_db_tables=reg_payload, preloaded_index=idx_reg)
        finally:
            os.remove(reg_file)
        self.assertEqual(len(res_reg["diffs"]), 0)
        self.assertEqual(len(res_reg["matches"]), 18)

        # 4. Uji paginasi multi-halaman di fetcher.py
        page_responses = [
            {"data": [{"id": i, "val": i} for i in range(1, 101)], "pagination": {"total_pages": 2}},
            {"data": [{"id": i, "val": i} for i in range(101, 126)], "pagination": {"total_pages": 2}},
        ]
        with patch.object(fetcher, "SATUDATA_TOKEN", "dummy_token"), \
             patch.object(fetcher, "_request_once", side_effect=page_responses) as mock_req:
            fetched = fetcher.fetch_data_from_link("index.php/admin/view_portal_tabel?id=999999")
            self.assertEqual(len(fetched), 125)
            self.assertEqual(mock_req.call_count, 2)

        # 5. Uji kolom atribut teks DB ('Jenjang', 'Akreditasi') tidak mengotori label baris (kasus 4.1.20)
        #    dan fallback tahun pdf_only memakai tahun cetakan PDF / N-1 (bukan tahun lama DB).
        pdf_4120 = [
            ["Perguruan Tinggi Agama Islam Swasta\nNon Government Islamic Universities", None, "Jenjang\nLadder", "Akreditasi\nAccreditation", "Mahasiswa\nStudent"],
            ["(1)", None, "(2)", "(3)", "(4)"],
            ["1.", "FAI Universitas Muhammadiyah Surakarta", "S.1, S.2, dan S.3", "Unggul", "2.626"],
            ["2.", "FAI Universitas Nahdlatul Ulama (UNU) Surakarta", "S.1, S.2, dan S.3", "Baik Sekali", "1.982"],
            ["3.", "FAI Universitas Nahdlatul Ulama (UNU)", "S.1", "Baik", "149"],
            ["4.", "STAI Baru Cetakan PDF", "S.1", "Baik", "350"],
        ]
        db_4120 = [
            ["Perguruan Tinggi Agama Islam Swasta", "2024 Jenjang", "2024 Akreditasi", "2024 Mahasiswa"],
            ["(1)", "(2)", "(3)", "(4)"],
            ["FAI Universitas Muhammadiyah Surakarta", "S.1, S.2, dan S.3", "Unggul", 2.626],
            ["FAI Universitas Nahdlatul Ulama (UNU) Surakarta", "S.1, S.2, dan S.3", "Baik Sekali", 1982],
            ["FAI Universitas Nahdlatul Ulama (UNU)", "S.1", "Baik", 149],
        ]
        p_4120 = [{
            "id_db": "9420",
            "nomor_tabel": "4.1.20",
            "judul_tabel": "Banyaknya Mahasiswa PTAI Swasta, Tahunan",
            "judul_pdf": "Banyaknya Mahasiswa PTAI Swasta di Provinsi Jawa Tengah, 2025",
            "source_type": "dinamis",
            "is_dynamic": 1,
            "dynamic_empty": False,
            "dynamic_grid": db_4120,
        }]
        idx_4120 = {"4.1.20": {"nomor_tabel": "4.1.20", "id_db": "9420", "pages": [369], "similarity": 100, "display_title": "Banyaknya Mahasiswa PTAI Swasta, 2025"}}
        with tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8") as tf:
            json.dump(p_4120, tf)
            f_4120 = tf.name
        try:
            with patch.object(compare_table, "extract_table_content", side_effect=lambda _p, infos: infos[0].update({"extracted_data": pdf_4120})), \
                 patch.object(compare_table, "get_cached_page_tables", return_value=pdf_4120), \
                 patch.object(compare_table, "detect_table_archetype", return_value="SINGLE_PAGE_CATEGORICAL"), \
                 patch.object(match_pdf, "detect_pdf_publication_year", return_value=2026):
                res_4120 = compare_table.compare_head_to_head("dda_master_2026.pdf", "4.1.20", f_4120, id_db="9420", preloaded_db_tables=p_4120, preloaded_index=idx_4120)
        finally:
            os.remove(f_4120)
        self.assertEqual(len(res_4120["matches"]), 3)
        self.assertEqual(len(res_4120["diffs"]), 0)
        self.assertEqual(len(res_4120["pdf_only"]), 1)
        self.assertEqual(res_4120["pdf_only"][0]["tahun"], "2025")


if __name__ == "__main__":
    unittest.main()



