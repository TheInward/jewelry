import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import {chromium} from 'playwright-core';

// Run the actual signup script against an isolated DOM and fake AJAX responses.
// No website, customer, coupon or mail service is contacted.
const script=await fs.readFile('theme/signup.js','utf8');
const browser=await chromium.launch({channel:'chrome',headless:true});
try{
 const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 let delayed=false,release,requestStarted;
 const started=new Promise(resolve=>requestStarted=resolve);
 await page.route('https://signup-audit.invalid/**',async route=>{
  if(new URL(route.request().url()).pathname==='/ajax'){
   const email=new URLSearchParams(route.request().postData()).get('email');
   if(delayed){requestStarted();await new Promise(resolve=>release=resolve)}
   await route.fulfill({json:{success:true,data:{code:'CODE-'+email,emailed:true}}});return;
  }
  await route.fulfill({contentType:'text/html',body:`<!doctype html><button id="signup-trigger">Sign up</button>
   <form id="newsletter"><input type="email" required><button>Open</button></form>
   <dialog id="signup-dialog"><button id="signup-close" type="button">Close</button>
    <div id="signup-form-panel"><form id="signup-form"><input type="email" name="email" required><button>Get code</button></form><p id="signup-status"></p></div>
    <div id="signup-result" hidden><span id="signup-code"></span><button id="signup-copy">COPY</button></div>
   </dialog><script>window.studyConfig={ajax:'/ajax',nonce:'memory-only'};window.studyLocale={strings:{}};</script>`});
 });
 await page.goto('https://signup-audit.invalid/');await page.addScriptTag({content:script});
 await page.locator('#signup-trigger').click();await page.locator('#signup-form input').fill('first@example.invalid');
 await page.locator('#signup-form button').click();await page.locator('#signup-result').waitFor({state:'visible'});
 assert.equal(await page.locator('#signup-code').textContent(),'CODE-first@example.invalid');
 await page.locator('#signup-close').click();
 await page.locator('#newsletter input').fill('second@example.invalid');await page.locator('#newsletter button').click();
 assert.equal(await page.locator('#signup-form-panel').isVisible(),true,'A new email must show the form, not the previous code');
 assert.equal(await page.locator('#signup-code').textContent(),'','A new signup must clear the previous email code');
 delayed=true;await page.locator('#signup-form button').click();await started;
 await page.locator('#signup-close').click();
 await page.locator('#newsletter input').fill('third@example.invalid');await page.locator('#newsletter button').click();
 release();await page.waitForResponse(r=>r.url().endsWith('/ajax'));await page.waitForTimeout(50);
 assert.equal(await page.locator('#signup-form input').inputValue(),'third@example.invalid');
 assert.equal(await page.locator('#signup-form-panel').isVisible(),true,'An old response must not replace a new signup');
 assert.equal(await page.locator('#signup-code').textContent(),'');
 assert.equal(await page.locator('#signup-form button').isEnabled(),true);
 assert.deepEqual(errors,[]);
 console.log('PASS: signup reset for a new email and stale response isolation; all requests mocked.');
}finally{await browser.close()}
