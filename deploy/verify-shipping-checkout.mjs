import assert from 'node:assert/strict';
const base=process.env.ENROUTE_PREVIEW_URL||'https://seleneith.com/';const jar=new Map();
async function request(path,options={},depth=0){const r=await fetch(new URL(path,base),{...options,headers:{connection:'close',cookie:[...jar].map(([k,v])=>k+'='+v).join('; '),...options.headers},redirect:'manual',signal:AbortSignal.timeout(45000)});for(const c of r.headers.getSetCookie()){const pair=c.split(';')[0],i=pair.indexOf('=');jar.set(pair.slice(0,i),pair.slice(i+1))}if([301,302,303].includes(r.status)&&depth<6)return request(r.headers.get('location'),{},depth+1);assert.equal(r.status,200);return r.text()}
const home=await request('');const cfg=JSON.parse(home.match(/var studyConfig = (.*?);/s)[1]);
async function cart(values={}){const p=JSON.parse(await request(cfg.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:cfg.nonce,...values})}));assert(p.success,JSON.stringify(p));return p.data}
const product=await request('product/waterdrop-necklace/');const raw=product.match(/data-product_variations="([^"]+)"/s)[1];const variants=JSON.parse(raw.replaceAll('&quot;','"').replaceAll('&#039;',"'").replaceAll('&amp;','&'));const variation=variants.find(v=>v.is_in_stock&&v.display_price===27);assert(variation);
const added=await cart({add:variation.variation_id});const key=added.items[0].key;
const amount=html=>{const bdi=html.match(/<bdi>([\s\S]*?)<\/bdi>/)?.[1]||'';return Number(bdi.replace(/<[^>]+>|&[^;]+;/g,'').replace(/[^0-9.\-]/g,''))};
try{
for(const [market,currency] of [['US','USD'],['FR','EUR'],['JP','JPY'],['HK','HKD'],['GB','GBP']]){
 for(const quantity of [1,4]){
  await request('checkout/?study_market='+market);const c=await cart({key,quantity});assert.equal(c.currency,currency);
  const checkout=await request('checkout/');assert(!checkout.includes('wp-block-woocommerce-coming-soon'));const cp=JSON.parse(checkout.match(/var wc_checkout_params = (.*?);/s)[1]);
  const fields=new URLSearchParams({billing_first_name:'Shipping',billing_last_name:'Test',billing_country:'US',billing_state:'NY',billing_postcode:'10001',billing_city:'Test',billing_address_1:'Test address',billing_email:'shipping-test@example.invalid',ship_to_different_address:'0'});
  const response=JSON.parse(await request('?wc-ajax=update_order_review',{method:'POST',body:new URLSearchParams({security:cp.update_order_review_nonce,country:'US',state:'NY',postcode:'10001',city:'Test',address:'Test address',s_country:'US',s_state:'NY',s_postcode:'10001',post_data:fields.toString(),has_full_address:'true'})}));
  const table=response.fragments['.woocommerce-checkout-review-order-table'];assert(table);
  const free=quantity===4;assert(table.includes(free?'free_shipping':'flat_rate'));assert(!table.includes(free?'flat_rate':'free_shipping'));
  const shipping=free?0:amount(table.match(/class="woocommerce-shipping-totals shipping"[\s\S]*?<\/tr>/)[0]);const expected=free?0:7.99*(c.items[0].price/27);assert(Math.abs(shipping-expected)<(currency==='JPY'?1:0.03),currency+' converted shipping '+shipping+' / '+expected);
  const total=amount(table.match(/class="order-total"[\s\S]*?<\/tr>/)[0]);assert(Math.abs(total-c.total-shipping)<0.02,currency+' total mismatch');console.log(currency,'quantity',quantity,'subtotal',c.total,'shipping',shipping,'total',total,'OK');
 }
}
}finally{await cart({remove:key})}
console.log('Five-currency paid/free shipping and live checkout totals passed; no order or payment submitted, cart cleaned.');
