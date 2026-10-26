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
    detect_bps_symbol,
    is_bps_unavailable,
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
        for s in ["-", "–", "—", "−", "na", "n.a.", "n/a", "...", ""]:
            val = parse_num(s)
            self.assertTrue(val is None or val == 0.0, f"Failed for '{s}' -> {val}")

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


class TestBpsSymbolsAndAccuracyUpgrades(unittest.TestCase):
    def test_detect_bps_symbols(self):
        kind, sym = detect_bps_symbol("-")
        self.assertEqual(kind, "NIHIL")
        kind_u, _ = detect_bps_symbol("...")
        self.assertEqual(kind_u, "UNAVAILABLE")
        kind_p, _ = detect_bps_symbol("12.5*")
        self.assertEqual(kind_p, "PRELIMINARY")
        kind_r, _ = detect_bps_symbol("15.4 r")
        self.assertEqual(kind_r, "REVISED")

    def test_parse_num_with_preliminary_and_revision_symbols(self):
        self.assertEqual(parse_num("*12,5", is_pdf=True), 12.5)
        self.assertEqual(parse_num("12,5*", is_pdf=True), 12.5)
        self.assertEqual(parse_num("15,4 r", is_pdf=True), 15.4)
        self.assertEqual(parse_num("20,1 e", is_pdf=True), 20.1)

    def test_unavailable_cells_matching(self):
        # Jika kedua sel sama-sama tidak tersedia / kosong, dianggap match status
        is_m, is_tol, diff = evaluate_cell_match(None, None)
        self.assertTrue(is_m)
        self.assertEqual(diff, 0.0)

        is_m_dots, _, _ = evaluate_cell_match("...", "na")
        self.assertTrue(is_m_dots)

    def test_explain_mismatch_unavailable(self):
        hint = explain_mismatch(None, 100.0)
        self.assertIn("tidak tercetak di PDF", hint)
        hint_db = explain_mismatch(100.0, None)
        self.assertIn("belum diisi di database", hint_db)


if __name__ == "__main__":
    unittest.main()


