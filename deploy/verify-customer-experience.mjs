import {chromium} from 'playwright-core';
import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
fs.mkdirSync('.runtime/trust-review',{recursive:true});
const browser=await chromium.launch({channel:'chrome',headless:true});
const results=[];
try {
 for(const width of [390,1440]){
  const context=await browser.newContext({viewport:{width,height:950}});const page=await context.newPage();const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  let fakeOffers=0;
  await page.route('**/wp-admin/admin-ajax.php',async route=>{
   const form=new URLSearchParams(route.request().postData()||'');
   if(form.get('action')!=='study_signup')return route.continue();
   fakeOffers++;return route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{code:'UI-TEST-NOT-A-REAL-COUPON',emailed:false}})});
  });
  const visit=async path=>{const r=await page.goto(new URL(path,base).href,{waitUntil:'load',timeout:60000});assert.equal(r.status(),200);assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,`Overflow: ${width}/${path}`);};
  await visit('');
  assert.equal(await page.locator('.dev-bar').count(),0);
  assert.equal(await page.locator('.store-assurances a').count(),3);
  assert(!/Development Preview|PAYMENTS NOT CONNECTED|Membership & Rewards/.test(await page.locator('body').innerText()));
  for(const slug of ['press','rewards','blogs','a-letter-from-us'])assert.equal(await page.locator(`.footer a[href*="/pages/${slug}/"]`).count(),0);
  for(const slug of ['rustic-soul','by-the-beach-collection-1','waterproof-jewelry'])assert.equal(await page.locator(`.nav a[href*="/collections/${slug}/"]`).count(),0);
  if(width===390){await page.locator('.mobile-menu-toggle').click();assert(await page.locator('.nav').isVisible());await page.locator('#mobile-menu-close').click();}
  await page.screenshot({path:`.runtime/trust-review/${width}-home-top.png`});
  await page.evaluate(async()=>{for(let y=0;y<document.body.scrollHeight;y+=650){scrollTo(0,y);await new Promise(r=>setTimeout(r,100))}scrollTo(0,0)});
  await page.screenshot({path:`.runtime/trust-review/${width}-home-full.png`,fullPage:true});
  // UI-only offer response: no real coupon is issued and no email is sent.
  await page.locator('#newsletter input[type=email]').fill('trust-review@example.invalid');
  await page.locator('#newsletter input[type=checkbox]').check();await page.locator('#newsletter button').click();
  await page.locator('#signup-dialog').waitFor({state:'visible'});
  assert.equal(await page.locator('#signup-form input').inputValue(),'trust-review@example.invalid');
  assert(!/marketing emails/i.test(await page.locator('.signup-privacy').innerText()));
  await page.locator('#signup-form button').click();await page.locator('#signup-result').waitFor({state:'visible'});
  assert.equal(fakeOffers,1);await page.locator('#signup-close').click();
  await visit('collections/necklaces/');await page.locator('#product-grid .product-card').first().waitFor();
  assert((await page.locator('#product-grid .price').first().innerText()).includes('From'));
  assert.equal(await page.locator('#product-grid .badge').count(),0);
  await visit('product/waterdrop-necklace/');
  const facts=await page.locator('.purchase-information').innerText();assert(facts.includes('$79.00')&&facts.includes('$9.90')&&facts.includes('12–25')&&facts.includes('14 days'));
  await page.locator('.gallery-zoom').click();await page.locator('.gallery-dialog').waitFor({state:'visible'});await page.keyboard.press('Escape');
  const config=await page.evaluate(()=>window.studyConfig);
  const variants=await page.locator('form.variations_form').evaluate(e=>JSON.parse(e.dataset.product_variations));const v=variants.find(v=>v.is_in_stock&&v.display_price===27);assert(v);
  let key;
  async function cart(form={}){const r=await context.request.post(config.ajax,{form:{action:'study_cart',nonce:config.nonce,...form}});assert.equal(r.status(),200);const p=await r.json();assert(p.success);return p.data;}
  try{
   const c=await cart({add:String(v.variation_id)});key=c.items[0].key;
   await page.locator('[data-open=cart]').click();await page.locator('#overlay.open').waitFor();
   const shipping=await page.locator('.shipping-progress').innerText();assert(shipping.includes('$79.00')&&shipping.includes('$9.90'));
   await page.locator('#close-drawer').click();
   await page.screenshot({path:`.runtime/trust-review/${width}-product.png`,fullPage:true});
  }finally{if(key)await cart({remove:key});assert.equal((await cart()).count,0);}
  for(const path of ['pages/about-us/','pages/contact/','pages/faq/','pages/terms-and-conditions/','pages/privacy-policy/']){
   await visit(path);const text=await page.locator('main').innerText();assert(!/development preview|before commercial launch|being prepared for launch|payments are not available|used for testing/i.test(text),path);
   if(path.includes('faq')){await page.locator('summary').filter({hasText:'How can I pay?'}).click();assert(await page.getByText('Payments are processed through PayPal.',{exact:false}).isVisible());}
  }
  assert.deepEqual(errors,[]);results.push({width,passed:true,mockedOfferRequests:fakeOffers,noRealEmailOrderPayment:true});console.log('PASS',width,'customer pages, menus, disclosure, offer UI, pricing, gallery, guest cart, no JS errors');
  await context.close();
 }
} finally {fs.writeFileSync('.runtime/trust-review/browser-results.json',JSON.stringify(results,null,2));await browser.close();}
