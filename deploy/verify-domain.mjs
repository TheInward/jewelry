import assert from 'node:assert/strict';
const base=new URL(process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/');
const response=await fetch(base,{headers:{connection:'close'}});assert.equal(response.status,200);const html=await response.text();assert(!html.includes('mrnobodys1.sg-host.com'),'Old domain remains in generated HTML');
const css=[...html.matchAll(/<link[^>]+href=['"]([^'"]+)['"][^>]*>/g)].map(m=>m[1]).filter(u=>u.includes('.css'));
await Promise.all(css.map(async ref=>{const url=new URL(ref,base);assert.equal(url.origin,base.origin);const r=await fetch(url,{headers:{connection:'close'}});assert.equal(r.status,200,url.href);assert(r.headers.get('content-type')?.includes('text/css'),url.href);await r.arrayBuffer();console.log('CSS 200:',url.pathname.split('/').at(-1))}));
const config=JSON.parse(html.match(/var studyConfig = (.*?);/s)[1]);assert.equal(new URL(config.ajax).origin,base.origin);assert.equal(new URL(config.checkout).origin,base.origin);
const http=new URL(base);http.protocol='http:';const redirect=await fetch(http,{redirect:'manual'});assert([301,302,308].includes(redirect.status));assert.equal(new URL(redirect.headers.get('location')).origin,base.origin);
console.log('Production HTML, stylesheets, same-origin AJAX/checkout and HTTP-to-HTTPS redirect verified.');
