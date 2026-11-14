import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
const jar=new Map();const report=[];
async function request(path,options={},depth=0){
 const url=new URL(path,base);assert.equal(url.origin,new URL(base).origin);
 const r=await fetch(url,{...options,headers:{connection:'close',cookie:[...jar].map(([k,v])=>`${k}=${v}`).join('; '),...options.headers},redirect:'manual',signal:AbortSignal.timeout(45000)});
 for(const c of r.headers.getSetCookie()){const pair=c.split(';')[0],i=pair.indexOf('=');jar.set(pair.slice(0,i),pair.slice(i+1));}
 if([301,302,303].includes(r.status)&&depth<6)return request(r.headers.get('location'),{},depth+1);
 assert.equal(r.status,200,`${url.pathname}: HTTP ${r.status}`);return r.text();
}
const home=await request('');const cfg=JSON.parse(home.match(/var studyConfig = (.*?);/s)[1]);
async function cart(values={}){const p=JSON.parse(await request(cfg.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:cfg.nonce,...values})}));assert(p.success,JSON.stringify(p));return p.data;}
const product=await request('product/waterdrop-necklace/');const raw=product.match(/data-product_variations="([^"]+)"/s)[1];
const variants=JSON.parse(raw.replaceAll('&quot;','"').replaceAll('&#039;',"'").replaceAll('&amp;','&'));
const variation=variants.find(v=>v.is_in_stock&&v.display_price===27);assert(variation,'USD27 baseline variation');
let key;
const text=html=>html.replace(/<[^>]+>/g,' ').replace(/&[^;]+;/g,' ').replace(/\s+/g,' ').trim();
const amount=html=>Number((html.match(/<bdi>([\s\S]*?)<\/bdi>/)?.[1]||'').replace(/<[^>]+>|&[^;]+;/g,'').replace(/[^0-9.\-]/g,''));
try{
 const added=await cart({add:variation.variation_id});key=added.items[0].key;
 for(const [country,currency,state,postcode,city] of [['US','USD','NY','10001','New York'],['CA','CAD','ON','M5V 3L9','Toronto'],['AU','AUD','NSW','2000','Sydney']]){
  for(const quantity of [1,3]){
   const ch=await request('checkout/?study_market='+country+'&study_language=fr');
   const locale=JSON.parse(ch.match(/var studyLocale = (\{[^\n]*\});/)[1]);assert.equal(locale.language,'en');assert.equal(locale.currency,currency);
   const c=await cart({key,quantity});assert.equal(c.currency,currency);
   const checkout=await request('checkout/');const cp=JSON.parse(checkout.match(/var wc_checkout_params = (.*?);/s)[1]);
   // Only refresh order review. Never call checkout submission or PayPal create-order.
   const fields=new URLSearchParams({billing_first_name:'Launch',billing_last_name:'Check',billing_country:country,billing_state:state,billing_postcode:postcode,billing_city:city,billing_address_1:'Test address',billing_email:'launch-check@example.invalid',ship_to_different_address:'0'});
   const response=JSON.parse(await request('?wc-ajax=update_order_review',{method:'POST',body:new URLSearchParams({security:cp.update_order_review_nonce,country,state,postcode,city,address:'Test address',s_country:country,s_state:state,s_postcode:postcode,post_data:fields.toString(),has_full_address:'true'})}));
   assert.notEqual(response.result,'failure',JSON.stringify(response.messages));
   const table=response.fragments['.woocommerce-checkout-review-order-table'];assert(table);
   const free=quantity===3;assert(table.includes(free?'free_shipping':'flat_rate'),`${country}: missing expected shipping`);
   assert(!table.includes(free?'flat_rate':'free_shipping'),`${country}: wrong shipping method`);
   const shipping=free?0:amount(table.match(/class="woocommerce-shipping-totals shipping"[\s\S]*?<\/tr>/)?.[0]||'');
   const baseShipping={US:9.90,CA:12.90,AU:15.90}[country];const expected=free?0:baseShipping*(c.items[0].price/27);assert(Math.abs(shipping-expected)<0.04,`${currency}: converted shipping ${shipping}/${expected}`);
   const taxRows=table.match(/<tr class="(?:tax-rate[^"<]*|tax-total)"[\s\S]*?<\/tr>/g)||[];
   const tax=taxRows.reduce((sum,row)=>sum+amount(row),0);const total=amount(table.match(/class="order-total"[\s\S]*?<\/tr>/)?.[0]||'');
   assert(Math.abs(total-c.total-shipping-tax)<0.04,`${currency}: total mismatch`);
   const payment=response.fragments['.woocommerce-checkout-payment']||'';
   const paypalListed=payment.includes('payment_method_ppcp-gateway');
   const row={country,currency,quantity,subtotal:c.total,shipping,tax,total,paypalListed,taxDetails:taxRows.map(text)};
   report.push(row);console.log(JSON.stringify(row));assert(paypalListed,`${country}: PayPal gateway not offered`);
  }
 }
}finally{
 if(key){await cart({remove:key});assert.equal((await cart()).count,0);}
 fs.mkdirSync('.runtime/launch-audit',{recursive:true});fs.writeFileSync('.runtime/launch-audit/checkout.json',JSON.stringify(report,null,2)+'\n');
}
console.log('Three countries / six checkout reviews passed; no order, payment or email submitted. Guest cart cleared.');
