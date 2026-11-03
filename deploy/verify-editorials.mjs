import {chromium} from 'playwright-core';
import assert from 'node:assert/strict';
import fs from 'node:fs';
const base=process.env.SELENEITH_PREVIEW_URL||'https://seleneith.com/';
fs.mkdirSync('.runtime/editorial-review',{recursive:true});
const browser=await chromium.launch({channel:'chrome',headless:true});const errors=[];
try{
 for(const width of (process.env.VERIFY_WIDTHS?.split(',').map(Number)||[1280,1440,1920,390])){
  const context=await browser.newContext({viewport:{width,height:1000},isMobile:width<768,hasTouch:width<768});const page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
  await page.goto(base+'?study_market='+(process.env.VERIFY_MARKET||'US')+'&study_language=en',{waitUntil:'load',timeout:60000});
  for(const name of ['gold','silver','sets']){
   const section=page.locator('.editorial-'+name);assert(await section.isVisible());assert.equal(await section.locator('.product-card').count(),4);
   const geometry=await section.evaluate(e=>{const banner=e.querySelector('.editorial-banner').getBoundingClientRect(),cards=[...e.querySelectorAll('.product-card')].map(n=>n.getBoundingClientRect());return {display:getComputedStyle(e).display,banner:{x:banner.x,y:banner.y,w:banner.width,h:banner.height},cards:cards.map(r=>({x:r.x,y:r.y,w:r.width,h:r.height}))}});
   if(width>=768){assert.equal(geometry.display,'grid');assert(geometry.cards[0].x>geometry.banner.x+geometry.banner.w);assert(Math.abs(geometry.cards[0].y-geometry.cards[1].y)<2);assert(geometry.cards[2].y>geometry.cards[0].y);assert(geometry.banner.h>geometry.cards[0].h*1.6)}else assert.equal(geometry.display,'block');
   await section.scrollIntoViewIfNeeded();await section.locator('.editorial-banner img').evaluate(i=>i.decode().catch(()=>{}));
  }
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'Overflow '+width);
  if(width===1440)await page.locator('.editorial-sets').screenshot({path:'.runtime/editorial-review/desktop-jewelry-sets'+(process.env.VERIFY_MARKET?'-'+process.env.VERIFY_MARKET:'')+'.png'});
  if(width===390)await page.locator('.editorial-sets').screenshot({path:'.runtime/editorial-review/mobile-jewelry-sets.png'});
  const available=page.locator('.editorial-sets .editorial-card').filter({has:page.locator('button[data-add]')});assert(await available.count(),'No default variation available for quick add');
  const checks=width===1440?await available.count():1;
  for(let cardIndex=0;cardIndex<checks;cardIndex++){
  const card=available.nth(cardIndex);
  const expected=Number((await card.locator('.editorial-current .woocommerce-Price-amount').innerText()).replace(/[^0-9.]/g,''));
  await card.locator('button[data-add]').click();await page.locator('#overlay.open').waitFor();
  const cart=await page.evaluate(async()=>{const r=await fetch(studyConfig.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:studyConfig.nonce})});return r.json()});
  try{assert(cart.success);assert.equal(cart.data.count,1);assert.equal(cart.data.items[0].price,expected)}finally{for(const item of cart.data.items||[])await page.evaluate(async key=>fetch(studyConfig.ajax,{method:'POST',body:new URLSearchParams({action:'study_cart',nonce:studyConfig.nonce,remove:key})}),item.key)}
  await page.locator('#close-drawer').click();
  }
  console.log(width,'three editorials, four cards each, layout, image, overflow and default-variation quick-add price passed');await context.close();
 }
 assert.deepEqual(errors,[]);console.log('No JavaScript errors. Test carts cleared; no order or payment submitted.');
}finally{await browser.close()}
