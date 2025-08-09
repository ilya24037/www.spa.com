import os
import json
from collections import defaultdict

ROOT = '/workspace'
ARCHIVE = os.path.join(ROOT, 'Backap', 'js')
CURRENT = os.path.join(ROOT, 'resources')
EXTS = {'.vue', '.js', '.ts', '.tsx', '.scss', '.css', '.sass'}


def collect_files_with_sizes(base_dir: str, exts: set[str]) -> list[tuple[int, str]]:
    results: list[tuple[int, str]] = []
    for dirpath, dirnames, filenames in os.walk(base_dir):
        # skip heavy/system dirs inside CURRENT if any
        if base_dir == CURRENT:
            parts = set(dirpath.split(os.sep))
            if parts & {'.git', 'node_modules', 'vendor', 'storage'}:
                continue
        for filename in filenames:
            if os.path.splitext(filename)[1].lower() not in exts:
                continue
            full_path = os.path.join(dirpath, filename)
            try:
                size = os.path.getsize(full_path)
            except OSError:
                continue
            results.append((size, os.path.relpath(full_path, base_dir)))
    return results


def infer_purpose(rel_path: str) -> str:
    parts = rel_path.replace('\\', '/').split('/')
    lower = [p.lower() for p in parts]
    name = os.path.basename(rel_path).lower()
    if 'pages' in lower:
        return 'Страница'
    if 'components' in lower:
        return 'Компонент UI'
    if 'features' in lower:
        return 'Фича-модуль'
    if 'entities' in lower:
        return 'Сущность/UI'
    if name.endswith(('.scss', '.css')):
        return 'Стили'
    if name.endswith('.vue'):
        return 'Компонент Vue'
    if name.endswith(('.js', '.ts', '.tsx')):
        return 'Логика/утилиты'
    return 'Файл'


def main() -> int:
    arch = collect_files_with_sizes(ARCHIVE, EXTS)
    arch.sort(reverse=True)
    arch_top = arch[:10]

    cur_files = collect_files_with_sizes(CURRENT, EXTS)
    cur_rel = [p for _, p in cur_files]
    cur_set = set(cur_rel)
    cur_lower_map = {p.lower(): p for p in cur_rel}

    by_basename: dict[str, list[str]] = defaultdict(list)
    for p in cur_rel:
        base = os.path.splitext(os.path.basename(p))[0].lower()
        by_basename[base].append(p)

    report_rows = []

    for size, rel in arch_top:
        guess1 = os.path.join('js', rel)
        guess2 = guess1.replace('Components' + os.sep, 'components' + os.sep)
        status = 'missing'
        target = None
        probable: list[str] = []

        if guess1 in cur_set:
            status = 'exact'
            target = guess1
        elif guess1.lower() in cur_lower_map:
            status = 'case-insensitive'
            target = cur_lower_map[guess1.lower()]
        elif guess2 in cur_set:
            status = 'exact'
            target = guess2
        elif guess2.lower() in cur_lower_map:
            status = 'case-insensitive'
            target = cur_lower_map[guess2.lower()]
        else:
            base = os.path.splitext(os.path.basename(rel))[0].lower()
            probable = by_basename.get(base, [])
            if probable:
                status = 'basename'

        row = {
            'archive_rel': rel,
            'size_bytes': size,
            'size_human': f'{size/1024:.1f} KB',
            'purpose': infer_purpose(rel),
            'status': status,
            'new_target': target,
            'probable_matches': probable[:10],
        }
        report_rows.append(row)

    # Print concise report
    print('Top 10 largest files in Backap/js')
    print()
    for r in report_rows:
        print(f"- {r['archive_rel']}  [{r['size_human']}]")
        print(f"  purpose: {r['purpose']}")
        if r['status'] in ('exact', 'case-insensitive'):
            print(f"  status: {r['status']}, new: {r['new_target']}")
        elif r['status'] == 'basename':
            more = f" (+{len(r['probable_matches'])-3} more)" if len(r['probable_matches']) > 3 else ''
            print('  status: probable matches -> ' + '; '.join(r['probable_matches'][:3]) + more)
        else:
            print('  status: missing')
    print()

    # Save JSON
    out_json = os.path.join(ROOT, 'Backap', 'top10_analysis.json')
    with open(out_json, 'w', encoding='utf-8') as f:
        json.dump(report_rows, f, ensure_ascii=False, indent=2)
    print(out_json)

    return 0


if __name__ == '__main__':
    raise SystemExit(main())