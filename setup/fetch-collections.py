"""Fetch public factual catalog records; never import customer or payment data.

Raw responses stay in ignored .runtime. Only titles, prices, image references,
availability and collection membership are retained in the reproducible seed.
"""
import os, concurrent.futures, json, pathlib, subprocess, time

ROOT = pathlib.Path(__file__).resolve().parents[1]
BASE_URL = os.environ.get('CATALOG_SOURCE_URL', '').rstrip('/')
if not BASE_URL: raise RuntimeError('Set CATALOG_SOURCE_URL to an authorized catalog endpoint.')
CACHE = ROOT / '.runtime/catalog-source'
CACHE.mkdir(parents=True, exist_ok=True)
COLLECTIONS = json.loads((ROOT / 'reference/collections.json').read_text())
LANGUAGES = ['pl', 'es', 'fr', 'de', 'nl', 'it', 'sv', 'ja', 'ko', 'pt-pt', 'el', 'cs', 'hu']

def fetch(slug, lang='en'):
    products = []
    for page in range(1, 60):
        cached = CACHE / f'{lang}-{slug}-{page}.json'
        if not cached.exists():
            prefix = '' if lang == 'en' else '/'+{'pt-pt':'pt'}.get(lang, lang)
            url = f'{BASE_URL}{prefix}/collections/{slug}/products.json?limit=100&page={page}'
            for attempt in range(4):
                response = subprocess.run(['curl', '-fLsS', '--max-time', '45', url], capture_output=True)
                try:
                    data = json.loads(response.stdout)
                    if response.returncode or 'products' not in data: raise ValueError()
                    cached.write_text(json.dumps(data, ensure_ascii=False))
                    break
                except (ValueError, KeyError):
                    if attempt == 3: raise RuntimeError(f'Catalog unavailable: {lang}/{slug}/{page}')
                    time.sleep(2 * (attempt + 1))
        rows = json.loads(cached.read_text())['products']
        products.extend(rows)
        if len(rows) < 100: return products
    raise RuntimeError('Pagination limit exceeded')

def minimal(p):
    variants = p['variants']
    available = [v for v in variants if v.get('available')]
    v = min(available or variants, key=lambda row: float(row['price']))
    return {'source_id': p['id'], 'title': p['title'], 'handle': p['handle'],
            'price': v['price'], 'regular': v.get('compare_at_price'),
            'available': bool(available), 'images': [i['src'] for i in p['images']],
            'tags': p['tags'], 'collections': [], 'translations': {}}

catalog = {}
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    futures = {pool.submit(fetch, slug): slug for slug in COLLECTIONS}
    for future in concurrent.futures.as_completed(futures):
        slug = futures[future]
        rows = future.result()
        print(f'{slug}: {len(rows)}', flush=True)
        for p in rows:
            record = catalog.setdefault(p['id'], minimal(p))
            record['collections'].append(slug)

# One translated collection covers most products; uncommon products keep their
# original proper name rather than inventing a translation.
def translated(lang):
    return lang, fetch('all-products-collection', lang)
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    futures = {pool.submit(translated, lang): lang for lang in LANGUAGES}
    for future in concurrent.futures.as_completed(futures):
        try:
            lang, rows = future.result()
        except RuntimeError as error:
            print(f'Translation fallback to original product names: {error}', flush=True)
            continue
        count = 0
        for p in rows:
            if p['id'] in catalog:
                catalog[p['id']]['translations'][lang] = p['title']
                count += 1
        print(f'translated {lang}: {count}', flush=True)

destination = ROOT / 'reference/catalog.json'
destination.write_text(json.dumps(list(catalog.values()), ensure_ascii=False, separators=(',', ':')))
print(f'Saved {len(catalog)} unique products to {destination}', flush=True)
