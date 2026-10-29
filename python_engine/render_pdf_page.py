"""
Script untuk merender halaman spesifik dari PDF menjadi gambar resolusi tinggi (PNG).
Digunakan untuk preview / split view di DDA Online tanpa memicu auto-download browser.
"""
import argparse
import os
import sys

def render_page(pdf_path: str, page_num: int, output_path: str, dpi: int = 150):
    if not os.path.exists(pdf_path):
        print(f"ERROR: PDF file not found: {pdf_path}", file=sys.stderr)
        return False

    try:
        import pymupdf
    except ImportError:
        try:
            import fitz as pymupdf
        except ImportError:
            print("ERROR: PyMuPDF is not installed.", file=sys.stderr)
            return False

    try:
        doc = pymupdf.open(pdf_path)
        if page_num < 1 or page_num > doc.page_count:
            print(f"ERROR: Page {page_num} out of bounds (1-{doc.page_count})", file=sys.stderr)
            return False

        page = doc[page_num - 1]
        pix = page.get_pixmap(dpi=dpi)
        
        # Pastikan direktori output ada
        out_dir = os.path.dirname(os.path.abspath(output_path))
        if out_dir and not os.path.exists(out_dir):
            os.makedirs(out_dir, exist_ok=True)

        pix.save(output_path)
        print(f"SUCCESS: Page {page_num} rendered to {output_path} (DPI={dpi}, Size={len(pix.tobytes('png'))} bytes)")
        return True
    except Exception as e:
        print(f"ERROR: Exception while rendering page: {e}", file=sys.stderr)
        return False

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Render a single PDF page to PNG")
    parser.add_argument("--pdf", required=True, help="Path to PDF file")
    parser.add_argument("--page", type=int, required=True, help="1-based page number")
    parser.add_argument("--output", required=True, help="Output PNG path")
    parser.add_argument("--dpi", type=int, default=150, help="DPI resolution (default 150)")

    args = parser.parse_args()
    ok = render_page(args.pdf, args.page, args.output, args.dpi)
    sys.exit(0 if ok else 1)

