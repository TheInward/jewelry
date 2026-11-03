import assert from 'node:assert/strict';
import fs from 'node:fs';
const base = process.env.SELENEITH_PREVIEW_URL || 'https://seleneith.com/';
function client() {
 const jar=new Map();
 async function request(path,options={},depth=0) {
  const url=new URL(path,base);
  assert.equal(url.origin,new URL(base).origin);
  const response=await fetch(url,{...options,headers:{connection:'close',cookie:[...jar].map(([k,v])=>k+'='+v).join('; '),...options.headers},redirect:'manual',signal:AbortSignal.timeout(30000)});
  for(const raw of response.headers.getSetCookie()) {const pair=raw.split(';')[0],index=pair.indexOf('=');jar.set(pair.slice(0,index),pair.slice(index+1));}
  if([301,302,303].includes(response.status)&&depth<6)return request(response.headers.get('location'),{},depth+1);
  const html=await response.text();
  assert.equal(response.status,200,'HTTP '+response.status+' for '+url.pathname);
  return {html,headers:response.headers};
 }
 return {request};
}
const config=html=>JSON.parse(html.match(/var studyConfig = (\{[^\n]*\});/)[1]);
const locale=html=>JSON.parse(html.match(/var studyLocale = (\{[^\n]*\});/)[1]);
const catalog=html=>JSON.parse(html.match(/id="catalog-data">(.*?)<\/script>/s)[1]);
const seed=JSON.parse(fs.readFileSync(new URL('../reference/catalog.json',import.meta.url)));
const shopper=client();
let settings;
async function api(action,params={}) {const r=await shopper.request(settings.ajax,{method:'POST',body:new URLSearchParams({action,nonce:settings.nonce,...params})});const payload=JSON.parse(r.html);assert.equal(payload.success,true,JSON.stringify(payload));return payload.data;}
for(const slug of (process.env.VERIFY_COMMERCE_ONLY ? [] : ['necklaces','bracelet-anklet','rings','earrings','bestseller','new-arrival','all-products-collection','gold-necklace','stud-earrings','sub-rosa','rustic-soul'])) {
 const {html}=await shopper.request('collections/'+slug+'/');
 const rows=catalog(html),expected=seed.filter(p=>p.collections.includes(slug));
 assert.equal(rows.length,expected.length,slug+' count');
 const ids=new Set(expected.map(p=>p.source_id));
 assert(rows.every(p=>ids.has(p.source_id)),slug+' contains a product from another collection');
 assert.equal((html.match(/id="catalog-data"/g)||[]).length,1);
 if(slug==='necklaces')settings=config(html);
 console.log('Collection',slug,rows.length,'membership verified');
}
const home=await shopper.request('');
settings=config(home.html);
assert(home.headers.get('cache-control')?.includes('no-cache'),'HTML must not share cached language/currency');
assert(home.html.includes('class="home-hero"'),'Homepage campaign banner missing');
assert(home.html.includes('id="signup-trigger"'),'Signup trigger missing');
const listing=await shopper.request('collections/necklaces/');
const item=catalog(listing.html).find(p=>p.available&&p.price===27);
assert(item,'Baseline test product missing');
let key;
try {
 let addId=item.id;
 if(item.type==='variable'){const page=await shopper.request(item.url);const raw=page.html.match(/data-product_variations="([^"]+)"/s)?.[1]||page.html.match(/data-product_variations='([^']+)'/s)?.[1];assert(raw,'Native variation data missing');const variants=JSON.parse(raw.replaceAll('&quot;','\"').replaceAll('&#039;',"'").replaceAll('&amp;','&'));const v=variants.find(v=>v.is_in_stock&&Number(v.display_price)===27);assert(v,'Baseline variation missing');addId=v.variation_id;}
 const added=await api('study_cart',{add:addId,price:'0.01'});key=added.items[0].key;assert.equal(added.items[0].price,27);
 await api('study_cart',{key,quantity:2});
 for(const [market,currency] of (process.env.VERIFY_SKIP_CURRENCIES ? [] : [['HK','HKD'],['FR','EUR'],['JP','JPY'],['GB','GBP'],['US','USD']])) {
  const {html}=await shopper.request('collections/necklaces/?study_market='+market);
  assert.equal(locale(html).currency,currency,market+' selected currency');
  const shown=catalog(html).find(p=>p.id===item.id);
  const cart=await api('study_cart');
  assert.equal(cart.items[0].price,shown.price,currency+' product/cart amount');
  assert(Math.abs(cart.total-2*shown.price)<0.011,currency+' subtotal');
  const checkout=await shopper.request('checkout/');
  assert.equal(locale(checkout.html).currency,currency,currency+' checkout currency');
  assert(checkout.html.includes('name="checkout"'),'Server-rendered WooCommerce checkout form');
  const summary=checkout.html.match(/class="order-total"[\s\S]*?<\/tr>/)?.[0];
  assert(summary?.includes('woocommerce-Price-currencySymbol'),'Checkout has a calculated total');
  const shippingRow=checkout.html.match(/class="woocommerce-shipping-totals shipping"[\s\S]*?<\/tr>/)?.[0]||'';
  const shippingAmount=Number((shippingRow.match(/<bdi>([\s\S]*?)<\/bdi>/)?.[1]||'').replace(/<[^>]+>|&[^;]+;/g,'').replace(/[^0-9.\-]/g,''));
  assert(summary.includes(Number(cart.total+shippingAmount).toLocaleString('en-US',{minimumFractionDigits:Number(locale(checkout.html).decimals),maximumFractionDigits:Number(locale(checkout.html).decimals)})),'Native checkout total matches subtotal plus shipping');
  assert(!checkout.html.includes('id="filter-groups"'),'Checkout must not initialize catalog filters');
  console.log('Currency',currency,'product',shown.price,'subtotal',cart.total,'checkout verified');
 }
 for(const lang of (process.env.VERIFY_LANGUAGES?.split(',') || ['pl','es','fr','de','nl','it','sv','ja','ko','pt-pt','el','cs','hu','en'])) {
  const {html}=await shopper.request('collections/necklaces/?study_language='+lang);
  assert.equal(locale(html).language,lang);
  const translated=catalog(html).find(p=>p.id===item.id);
  assert.equal(translated.title,lang==='en'?item.title:seed.find(p=>p.handle==='waterdrop-necklace').translations[lang]);
  const account=await shopper.request('my-account/');
  assert.equal(locale(account.html).language,lang,'Account language persistence');
  assert(account.html.includes('woocommerce-login-nonce'),'Real WooCommerce login form');
  console.log('Language',lang,'catalog/product title/account persistence verified');
 }
 const cp=JSON.parse((await shopper.request('checkout/')).html.match(/var wc_checkout_params = (.*?);/s)[1]);
 const fields=new URLSearchParams({billing_first_name:'Preview',billing_last_name:'Check',billing_country:'US',billing_state:'NY',billing_postcode:'10001',billing_city:'Test',billing_address_1:'Test address',billing_email:'catalog-check@example.invalid',ship_to_different_address:'0'});
 const review=await shopper.request('?wc-ajax=update_order_review',{method:'POST',body:new URLSearchParams({security:cp.update_order_review_nonce,country:'US',state:'NY',postcode:'10001',city:'Test',address:'Test address',s_country:'US',s_state:'NY',s_postcode:'10001',post_data:fields.toString(),has_full_address:'true'})});
 const fragment=JSON.parse(review.html).fragments['.woocommerce-checkout-review-order-table'];
 assert(fragment.includes('flat_rate'),'USD54 must use standard shipping below USD100');
 assert(!fragment.includes('free_shipping'),'USD54 does not qualify for free shipping');
 console.log('USD54 standard shipping selected below USD100; no payment or order submitted.');
 const guest=client();const empty=await guest.request(settings.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:settings.nonce})});assert.equal(JSON.parse(empty.html).data.count,0,'Other guest isolation');
 console.log('Guest cart isolation verified.');
} finally {if(key)await api('study_cart',{remove:key});}
