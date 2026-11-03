"""Refresh short collection and facet labels from the public reference menus."""
import os, concurrent.futures, html, json, pathlib, re, subprocess
from html.parser import HTMLParser
ROOT = pathlib.Path(__file__).resolve().parents[1]
BASE_URL = os.environ.get('CATALOG_SOURCE_URL', '').rstrip('/')
if not BASE_URL: raise RuntimeError('Set CATALOG_SOURCE_URL to an authorized catalog endpoint.')
CACHE = ROOT / '.runtime/catalog-source'
manifest = json.loads((ROOT/'reference/collections.json').read_text())
languages = ['en','pl','es','fr','de','nl','it','sv','ja','ko','pt-pt','el','cs','hu']
class Menus(HTMLParser):
    def __init__(self): super().__init__(); self.slug = None; self.parts = []; self.links = {}
    def handle_starttag(self, tag, attrs):
        if tag != 'a': return
        path = dict(attrs).get('href','').split('?')[0].rstrip('/')
        self.slug = path.split('/')[-1] if '/collections/' in path else None
        self.parts = []
    def handle_data(self, data):
        if self.slug: self.parts.append(data)
    def handle_endtag(self, tag):
        if tag == 'a' and self.slug:
            label = ' '.join(''.join(self.parts).split())
            if self.slug in manifest and label and len(label) < 90: self.links.setdefault(self.slug,label)
            self.slug = None
def fetch(lang):
    path = CACHE/f'menu-{lang}.html'
    if not path.exists():
        prefix = '' if lang == 'en' else '/'+{'pt-pt':'pt'}.get(lang,lang)
        result = subprocess.run(['curl','-fLsS','--retry','2','--max-time','40',f'{BASE_URL}{prefix}/collections/necklaces'],capture_output=True)
        if result.returncode: return lang, {}, {}
        path.write_bytes(result.stdout)
    source = path.read_text(); parser = Menus(); parser.feed(source)
    if not parser.links: raise RuntimeError(f'No collection labels found for {lang}; preserving existing translations.')
    labels = {manifest[slug]:label for slug,label in parser.links.items()}
    facets = dict(re.findall(r'data-filter-value="([^"]+)"[^>]*>\s*</button>\s*<span>([^<]+)',source,re.S))
    facets = {key:html.unescape(value).strip() for key,value in facets.items()}
    return lang, labels, facets
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool: results=list(pool.map(fetch,languages))
base = results[0][2]; output = {}
for lang,labels,facets in results:
    for key,value in base.items():
        if key in facets: labels[value] = facets[key]
    output[lang] = labels
    print(lang,len(labels),flush=True)
(ROOT/'theme/translations-extra.json').write_text(json.dumps(output,ensure_ascii=False,separators=(',',':')))
