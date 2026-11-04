import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {chromium} from 'playwright-core';
// Deterministic UI failure/race tests. All cart/wishlist requests are intercepted;
// the logged-in flag is synthetic and no account or commerce data is written.
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
const browser=await chromium.launch({channel:'chrome',headless:true});
try{
 const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];
 page.on('pageerror',error=>errors.push(error.message));
 let saved=[],failWish=false,active=0,maxActive=0,removals=0,delayCart=0;
 const cart={currency:'USD',items:[{key:'memory-cart-key',id:123,product_id:123,name:'Test item',quantity:1,price:27,image:''}],total:27,count:1};
 await page.route('**/wp-content/themes/seleneith/app.js*',async route=>route.fulfill({contentType:'application/javascript',body:'studyConfig.loggedIn=true;\n'+await fs.readFile('theme/app.js','utf8')}));
 await page.route('**/wp-admin/admin-ajax.php',async route=>{
  const params=new URLSearchParams(route.request().postData());const action=params.get('action');
  if(action==='study_wishlist'){
   active++;maxActive=Math.max(maxActive,active);await new Promise(r=>setTimeout(r,100));
   const failed=failWish;failWish=false;
   if(!failed){if(params.has('add'))saved=[...new Set([...saved,Number(params.get('add'))])];if(params.has('remove'))saved=saved.filter(id=>id!==Number(params.get('remove')))}
   active--;await route.fulfill({status:failed?503:200,json:failed?{success:false,data:'Simulated save failure'}:{success:true,data:saved}});return;
  }
  if(action==='study_cart'){
   const delay=delayCart;delayCart=0;if(delay)await new Promise(r=>setTimeout(r,delay));
   if(params.has('remove')){removals++;cart.items=[];cart.count=0;cart.total=0}
   if(params.has('quantity')){cart.items[0].quantity=Number(params.get('quantity'));cart.count=cart.items[0].quantity;cart.total=27*cart.count}
   await route.fulfill({json:{success:true,data:cart}});return;
  }
  await route.abort();
 });
 await page.goto(base,{waitUntil:'load',timeout:60000});
 const ids=await page.locator('[data-wish]').evaluateAll(nodes=>[...new Set(nodes.map(n=>Number(n.dataset.wish)))].slice(0,2));assert.equal(ids.length,2);
 await page.waitForTimeout(200);
 const wish=id=>page.locator('[data-wish="'+id+'"]').first();
 failWish=true;await wish(ids[0]).click();await page.waitForTimeout(200);
 assert.equal(await wish(ids[0]).getAttribute('aria-pressed'),'false','Failed request must not commit local state');
 await wish(ids[0]).click();await page.waitForTimeout(200);assert(saved.includes(ids[0]),'Retry must add after a failed add');
 await page.evaluate(([a,b])=>{document.querySelector('[data-wish="'+b+'"]').click();document.querySelector('[data-wish="'+a+'"]').click();document.querySelector('[data-wish="'+a+'"]').click()},ids);
 await page.waitForTimeout(600);assert.equal(maxActive,1,'Wishlist mutations must be serialized');assert(saved.includes(ids[0])&&saved.includes(ids[1]));
 for(const id of ids)assert(await page.locator('[data-wish="'+id+'"]').evaluateAll(nodes=>nodes.every(n=>n.getAttribute('aria-pressed')==='true')));
 await page.locator('[data-open="cart"]').first().click();await page.waitForSelector('#overlay.open');
 delayCart=350;await page.locator('[data-qty="2"]').click();await page.locator('#close-drawer').click();await page.waitForTimeout(500);
 assert.equal(await page.locator('#overlay').evaluate(n=>n.classList.contains('open')),false,'Late cart response must not reopen closed drawer');
 await page.locator('[data-open="cart"]').first().click();await page.waitForSelector('#overlay.open');
 failWish=true;await page.locator('[data-move]').click();await page.waitForTimeout(250);assert.equal(removals,0,'Failed wishlist save must not remove the cart item');
 await page.locator('[data-move]').click();await page.waitForTimeout(300);assert.equal(removals,1);assert(saved.includes(123));
 assert.deepEqual(errors,[]);
 console.log('PASS: failed-save rollback/retry, serialized wishlist toggles, duplicate buttons, late drawer response, safe move-to-wishlist; no real mutations.');
}finally{await browser.close()}
