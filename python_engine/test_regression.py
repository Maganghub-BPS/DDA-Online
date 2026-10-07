import sys, json, re, os
sys.path.append('python_engine')
from compare_table import compare_head_to_head

db_path = os.path.abspath('writable/uploads/dda_db_2026.json')
pdf_path = os.path.abspath('writable/uploads/dda_master_2026.pdf')

targets = ['1.1.1','1.1.2','1.1.4','1.1.5','1.1.6','1.1.8','2.3.1','2.3.3','2.3.5']

for target in targets:
    result = compare_head_to_head(pdf_path, target, db_path, tolerance=0.0)
    
    if result.get('status') == 'error':
        print(f'{target}: ERROR - {result.get("message","")}')
        continue
    
    m = len(result.get('matches',[]))
    d = len(result.get('diffs',[]))
    p = len(result.get('pdf_only',[]))
    print(f'{target}: matches={m} diffs={d} pdf_only={p}')
    
    # Show first 3 diffs for debugging
    for item in result.get('diffs',[])[:3]:
        w = item.get('wilayah','')
        met = item.get('metric','')[:50]
        vals = item.get('values',[])
        print(f'  DIFF: {w} | {met} | {vals}')
    for item in result.get('pdf_only',[])[:2]:
        w = item.get('wilayah','')
        met = item.get('metric','')[:50]
        print(f'  PDF_ONLY: {w} | {met}')
    print()
