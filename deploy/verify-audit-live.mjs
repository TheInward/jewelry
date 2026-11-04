import assert from 'node:assert/strict';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';const jar=new Map();
async function request(path,options={},depth=0){
 const url=new URL(path,base);assert.equal(url.origin,new URL(base).origin);
 const r=await fetch(url,{...options,headers:{cookie:[...jar].map(([k,v])=>k+'='+v).join('; '),...options.headers},redirect:'manual',signal:AbortSignal.timeout(45000)});
 for(const raw of r.headers.getSetCookie()){const pair=raw.split(';')[0],i=pair.indexOf('=');jar.set(pair.slice(0,i),pair.slice(i+1))}
 if([301,302,303].includes(r.status)&&depth<6)return request(r.headers.get('location'),{},depth+1);
 return {status:r.status,text:await r.text()};
}
const home=await request('');assert.equal(home.status,200);const cfg=JSON.parse(home.text.match(/var studyConfig = (.*?);/s)[1]);
async function api(action,values={},expected=200){const r=await request(cfg.ajax,{method:'POST',body:new URLSearchParams({action,nonce:cfg.nonce,...values})});assert.equal(r.status,expected,r.text.slice(0,200));const p=JSON.parse(r.text);assert.equal(p.success,expected===200,r.text.slice(0,200));return p.data}
const searchOnly=process.env.VERIFY_SEARCH_ONLY==='1';
let key;
try{
if(!searchOnly){
const pdp=await request('product/waterdrop-necklace/');const variants=JSON.parse(pdp.text.match(/data-product_variations="([^"]+)"/s)[1].replaceAll('&quot;','"').replaceAll('&#039;',"'").replaceAll('&amp;','&'));
const variation=variants.find(v=>v.is_in_stock&&v.display_price===27);assert(variation);
 const added=await api('study_cart',{add:variation.variation_id});key=added.items[0].key;assert.equal(added.count,1);
 for(const values of [{add:'-'+variation.variation_id},{add:variation.variation_id,remove:key},{remove:key,quantity:'2',key},{quantity:'2'},{key},{'add[]':variation.variation_id},{quantity:'-1',key},{quantity:'1.5',key},{quantity:'101',key},{remove:'missing'}]){
  await api('study_cart',values,400);assert.equal((await api('study_cart')).count,1,'Rejected request changed cart');
 }
 await api('study_cart',{quantity:'2',key});assert.equal((await api('study_cart')).count,2);
 const nonce=await request(cfg.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:'invalid',remove:key})});assert.equal(nonce.status,403);assert.equal((await api('study_cart')).count,2);
 await api('study_products',{'search[]':'ring'},400);await api('study_products',{ids:Array(101).fill('1').join(',')},400);
 await api('study_signup',{'email[]':'invalid'},400);await api('study_signup',{email:'name with spaces@example.invalid'},400);
 const anon=await request(cfg.ajax,{method:'POST',body:new URLSearchParams({action:'study_wishlist',nonce:cfg.nonce,add:variation.variation_id})});assert.equal(anon.status,400,'Anonymous wishlist must not reach account writes');
 console.log('PASS: invalid/mixed cart requests are atomic, stock/nonce/anonymous boundaries and malformed search/signup inputs.');
 }
 await request('?study_language=fr');
 const found=await api('study_products',{search:'collier'});assert(found.length>0);
 const full=await request('?post_type=product&s=collier');assert.equal(full.status,200);assert(full.text.includes('Collier Goutte'),'Full page must find translated product');
 assert((full.text.match(/class="product-card/g)||[]).length>0);assert(full.text.includes('page-numbers'),'Matching products must paginate');
 assert(!full.text.includes('id="catalog-data"'),'Search must not render the full catalog');
 const next=full.text.match(/<a class="next page-numbers" href="([^"]+)"/);assert(next,'Next page link');
 const second=await request(next[1].replace(/&(?:amp|#0*38|#x0*26);/gi,'&'));assert.equal(second.status,200);
 const links=text=>[...text.matchAll(/class="product-name" href="([^"]+)"/g)].map(m=>m[1]);
 assert(links(second.text).length>0);assert(!links(second.text).some(link=>links(full.text).includes(link)),'Pagination must show different results');
 const noResult=await request('?post_type=product&s=zzzauditnonexistentxyz');assert.equal((noResult.text.match(/class="product-card/g)||[]).length,0);
 const register=await request('my-account/?create_account=1');const regNonce=register.text.match(/name="woocommerce-register-nonce" value="([^"]+)"/)[1];
 // Deliberately invalid email ensures this probe cannot create an account even if
 // a later registration validation regresses. No mail-capable address is used.
 const bad=await request('my-account/?create_account=1',{method:'POST',body:new URLSearchParams({register:'1','woocommerce-register-nonce':regNonce,email:'invalid',first_name:'   ',last_name:'Test','password[]':'bad','password_confirmation[]':'bad',agree_privacy:'1'})});
 assert.equal(bad.status,200);assert(bad.text.includes('woocommerce-error'),'Malformed registration must produce form errors');
 console.log('PASS: French search/full-page routing, distinct pagination, empty results and malformed registration.');
}finally{if(key)await api('study_cart',{remove:key})}
console.log('Guest test cart cleaned when used; no order, payment, customer or email created.');
