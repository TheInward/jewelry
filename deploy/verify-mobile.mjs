import {chromium} from 'playwright-core';
import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
fs.mkdirSync('.runtime/mobile-review',{recursive:true});
const browser=await chromium.launch({channel:'chrome',headless:true});
const errors=[];
try{
 for(const width of [360,390,430,767]){
  const context=await browser.newContext({viewport:{width,height:844},isMobile:true,hasTouch:true});const page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
  const visit=path=>page.goto(new URL(path,base).href,{waitUntil:'load',timeout:60000});
  const overflow=async()=>assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Overflow at '+width+' '+page.url());
  await visit('');await overflow();
  assert.equal(await page.locator('.utility').evaluate(e=>e.getBoundingClientRect().height),40);
  assert.equal(await page.locator('.site-header').evaluate(e=>e.getBoundingClientRect().height),37);
  assert.equal(await page.locator('.utility .market-controls').isVisible(),false);
  await page.locator('.mobile-menu-toggle').click();await page.locator('#mobile-menu-close').waitFor({state:'visible'});assert(await page.locator('.mobile-menu-markets').isVisible());await page.locator('#mobile-menu-close').click();
  await page.locator('#signup-trigger').click();await page.locator('#signup-dialog').waitFor({state:'visible'});await page.locator('#signup-close').click();
  if(width===390){await page.screenshot({path:'.runtime/mobile-review/after-home-top.png'});await page.evaluate(async()=>{for(let y=0;y<document.body.scrollHeight;y+=600){scrollTo(0,y);await new Promise(r=>setTimeout(r,90))}scrollTo(0,0)});await page.screenshot({path:'.runtime/mobile-review/after-home-full.png',fullPage:true})}
  await visit('collections/necklaces/');await overflow();
  assert.equal(await page.locator('#product-grid').evaluate(e=>getComputedStyle(e).gridTemplateColumns.split(' ').length),2);
  await page.locator('#filter-toggle').click();await page.locator('.mobile-filter-apply').waitFor({state:'visible'});await page.locator('.mobile-filter-apply').click();assert.equal(await page.locator('.filters').isVisible(),false);
  if(width===390)await page.screenshot({path:'.runtime/mobile-review/after-collection.png'});
  await visit('product/waterdrop-necklace/');await overflow();
  const form=page.locator('form.variations_form');const variants=await form.evaluate(e=>JSON.parse(e.dataset.product_variations));const chosen=variants.find(v=>v.is_in_stock&&v.display_price>27);assert(chosen);
  for(const[name,value]of Object.entries(chosen.attributes)){const group=page.locator('select[name="'+name+'"]').locator('..').locator('.mobile-variation-options');const buttons=group.locator('button');for(let i=0;i<await buttons.count();i++)if(await buttons.nth(i).getAttribute('data-value')===value){await buttons.nth(i).click();break}}
  await page.waitForFunction(id=>document.querySelector('input.variation_id').value===String(id),chosen.variation_id);
  assert((await page.locator('.mobile-selected-price').innerText()).includes('51.30'));
  assert.equal(await page.locator('.woocommerce div.product').evaluate(e=>getComputedStyle(e).display),'block');
  assert.equal((await page.locator('.summary>p.price').innerText()).trim(),'$51.30');
  if(width===390)await page.screenshot({path:'.runtime/mobile-review/after-product.png'});
  await page.locator('.reset_variations').click();await page.waitForFunction(()=>!document.querySelector('.summary>p.price').classList.contains('mobile-has-variation-price'));assert.equal(await page.locator('.mobile-selected-price').isVisible(),false);
  console.log('Mobile',width,'header/menu/signup/two-column catalog/filter/variant-price/overflow passed');await context.close();
 }
 const desktop=await browser.newPage({viewport:{width:1440,height:1000}});await desktop.goto(base,{waitUntil:'load'});assert.equal(await desktop.locator('.mobile-category-section').isVisible(),false);assert.equal(await desktop.locator('.mobile-menu-head').isVisible(),false);assert(await desktop.locator('.home-categories').isVisible());await desktop.goto(new URL('product/waterdrop-necklace/',base).href,{waitUntil:'load'});assert(await desktop.locator('select[name="attribute_color"]').isVisible());assert.equal(await desktop.locator('.mobile-variation-options').first().isVisible(),false);assert.equal(errors.length,0,errors.join('\n'));console.log('Desktop controls retained; no page JavaScript errors. No email, order or payment submitted.');
}finally{await browser.close()}
