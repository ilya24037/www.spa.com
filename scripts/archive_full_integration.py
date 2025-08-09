import os
import json
import csv
import difflib
from collections import defaultdict
from typing import List, Dict, Tuple, Optional

ROOT = '/workspace'
ARCHIVE = os.path.join(ROOT, 'Backap', 'js')
CURRENT = os.path.join(ROOT, 'resources')
EXTS = {'.vue', '.js', '.ts', '.tsx', '.scss', '.css', '.sass'}


def list_project_files(base_dir: str) -> List[str]:
    files: List[str] = []
    for dirpath, dirnames, filenames in os.walk(base_dir):
        # skip heavy/system dirs
        parts = set(dirpath.split(os.sep))
        if parts & {'.git', 'node_modules', 'vendor', 'storage'}:
            continue
        for filename in filenames:
            if os.path.splitext(filename)[1].lower() not in EXTS:
                continue
            files.append(os.path.relpath(os.path.join(dirpath, filename), base_dir))
    return files


def read_text(path: str) -> str:
    try:
        with open(path, 'r', encoding='utf-8', errors='ignore') as f:
            return f.read()
    except Exception:
        return ''


def best_basename_match(arch_rel: str, cur_rel_list: List[str]) -> Tuple[Optional[str], float]:
    arch_name = os.path.splitext(os.path.basename(arch_rel))[0].lower()
    candidates = [p for p in cur_rel_list if os.path.splitext(os.path.basename(p))[0].lower() == arch_name]
    if not candidates:
        return None, 0.0
    # compute similarity vs each candidate
    arch_text = read_text(os.path.join(ARCHIVE, arch_rel))
    if not arch_text:
        # no content readable, fall back to first candidate
        return candidates[0], 0.0
    best_path = None
    best_ratio = -1.0
    for cand in candidates:
        cand_text = read_text(os.path.join(CURRENT, cand))
        if not cand_text:
            ratio = 0.0
        else:
            ratio = difflib.SequenceMatcher(a=arch_text, b=cand_text, autojunk=True).quick_ratio()
        if ratio > best_ratio:
            best_ratio = ratio
            best_path = cand
    return best_path, best_ratio if best_ratio >= 0 else 0.0


def main() -> int:
    arch_files = list_project_files(ARCHIVE)
    cur_files = list_project_files(CURRENT)
    cur_set = set(cur_files)
    cur_lower_map = {p.lower(): p for p in cur_files}

    results: List[Dict] = []
    stats = defaultdict(int)

    for af in sorted(arch_files):
        guess1 = os.path.join('js', af)
        guess2 = guess1.replace('Components' + os.sep, 'components' + os.sep)
        category = None
        target = None
        similarity = None

        if guess1 in cur_set:
            category = 'exact'
            target = guess1
        elif guess1.lower() in cur_lower_map:
            category = 'case-insensitive'
            target = cur_lower_map[guess1.lower()]
        elif guess2 in cur_set:
            category = 'exact'
            target = guess2
        elif guess2.lower() in cur_lower_map:
            category = 'case-insensitive'
            target = cur_lower_map[guess2.lower()]
        else:
            best_path, ratio = best_basename_match(af, cur_files)
            if best_path is not None:
                category = 'basename-similar'
                target = best_path
                similarity = round(float(ratio), 4)
            else:
                category = 'missing'

        stats[category] += 1
        size_bytes = 0
        try:
            size_bytes = os.path.getsize(os.path.join(ARCHIVE, af))
        except Exception:
            pass
        results.append({
            'archive_rel': af,
            'size_bytes': size_bytes,
            'category': category,
            'target_rel': target,
            'similarity': similarity,
        })

    out_dir = os.path.join(ROOT, 'Backap')
    os.makedirs(out_dir, exist_ok=True)

    json_path = os.path.join(out_dir, 'full_integration_map.json')
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump({
            'summary': stats,
            'total': len(arch_files),
            'items': results,
        }, f, ensure_ascii=False, indent=2)

    md_path = os.path.join(out_dir, 'full_integration_report.md')
    with open(md_path, 'w', encoding='utf-8') as f:
        f.write('# Backap/js full integration report\n\n')
        f.write(f"- Total archive files: {len(arch_files)}\n")
        f.write('\n')
        f.write('## Summary by category\n')
        for k in ['exact', 'case-insensitive', 'basename-similar', 'missing']:
            f.write(f"- {k}: {stats.get(k, 0)}\n")
        f.write('\n')
        f.write('## Missing\n')
        for item in results:
            if item['category'] == 'missing':
                f.write(f"- {item['archive_rel']}\n")
        f.write('\n')
        f.write('## Basename-similar (with similarity)\n')
        for item in sorted([i for i in results if i['category'] == 'basename-similar'], key=lambda x: (x['similarity'] or 0), reverse=True):
            f.write(f"- {item['archive_rel']} => {item['target_rel']} (sim={item['similarity']})\n")
        f.write('\n')
        f.write('## Exact / Case-insensitive\n')
        for item in results:
            if item['category'] in ('exact', 'case-insensitive'):
                f.write(f"- {item['archive_rel']} => {item['target_rel']} ({item['category']})\n")

    csv_path = os.path.join(out_dir, 'full_integration_map.csv')
    with open(csv_path, 'w', encoding='utf-8', newline='') as f:
        w = csv.writer(f)
        w.writerow(['archive_rel', 'size_bytes', 'category', 'target_rel', 'similarity'])
        for item in results:
            w.writerow([item['archive_rel'], item['size_bytes'], item['category'], item['target_rel'] or '', item['similarity'] if item['similarity'] is not None else ''])

    print(json_path)
    print(md_path)
    print(csv_path)
    print('DONE')
    return 0


if __name__ == '__main__':
    raise SystemExit(main())