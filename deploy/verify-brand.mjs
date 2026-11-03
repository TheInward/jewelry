import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
const patterns=process.env.LEGACY_BRAND_PATTERNS?JSON.parse(process.env.LEGACY_BRAND_PATTERNS).map(p=>new RegExp(p,'i')):[];
const pages=['','collections/necklaces/','collections/rings/','product/waterdrop-necklace/','my-account/','my-account/?create_account=1','pages/about-us/','pages/privacy-policy/','pages/shipping-policy/','pages/return-policy/'];
for(const path of pages){const response=await fetch(new URL(path,base),{headers:{connection:'close'},signal:AbortSignal.timeout(45000)});assert.equal(response.status,200,path);const html=await response.text();assert(!patterns.some(p=>p.test(html)),path+' contains a legacy identity');assert(html.includes('/themes/seleneith/'),path+' missing new theme');
 const sources=[...html.matchAll(/(?:src|srcset)=["'](https?:\/\/[^"']+)["']/g)].flatMap(m=>m[1].split(/,\s*/).map(s=>s.split(/\s+/)[0]));assert(sources.every(url=>new URL(url).origin===new URL(base).origin),path+' has external media/script source');console.log(path||'Homepage','identity and asset origins OK')}
const manifest=JSON.parse(fs.readFileSync('reference/media-manifest.json'));const checks=[manifest[0],manifest.at(-1),manifest[Math.floor(manifest.length/2)]];for(const item of checks){const r=await fetch(new URL('wp-content/uploads/seleneith-media/'+item.file,base),{method:'HEAD',headers:{connection:'close'}});assert.equal(r.status,200);assert.equal(Number(r.headers.get('content-length')),item.bytes)}
console.log('Media samples match published manifest.');
