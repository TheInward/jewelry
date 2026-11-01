import http from 'node:http';
const request=(url,options={})=>{
 const target=new URL(url);
 const headers=new Headers(options.headers);
 let destination=url;
 if(process.env.ENROUTE_VERIFY_ORIGIN && target.port==='8080'){
  destination=new URL(target.pathname+target.search,process.env.ENROUTE_VERIFY_ORIGIN).href;
  headers.set('Host',target.host);
  const body=options.body?.toString();
  if(body){headers.set('Content-Type','application/x-www-form-urlencoded');headers.set('Content-Length',Buffer.byteLength(body).toString());}
  return new Promise((resolve,reject)=>{
   const req=http.request(destination,{method:options.method||'GET',headers:Object.fromEntries(headers),timeout:15000},res=>{
    const chunks=[];res.on('data',b=>chunks.push(b));res.on('end',()=>{
     const text=Buffer.concat(chunks).toString();
     resolve({status:res.statusCode,headers:{get:name=>res.headers[name.toLowerCase()]||null,getSetCookie:()=>res.headers['set-cookie']||[]},text:async()=>text,json:async()=>JSON.parse(text)});
    });
   });req.on('error',reject);req.on('timeout',()=>req.destroy(Error('Request timed out')));req.end(body);
  });
 }
 return fetch(destination,{...options,headers,signal:AbortSignal.timeout(15000)});
};
const access={preview_url:process.env.ENROUTE_PREVIEW_URL||'http://39.106.108.70:8080/jewelry/'};
const previewCookie='';
const home=await request(access.preview_url);
const html=await home.text();
if(home.status!==200)throw Error('Public homepage HTTP '+home.status);
const products=JSON.parse(html.match(/id="catalog-data">(.*?)<\/script>/s)?.[1]||'[]');
if(products.length!==175)throw Error('Unexpected product count: '+products.length);
console.log('Homepage HTTP 200; product count 175.');
const config=JSON.parse(html.match(/var studyConfig = (.*?);/s)[1]);
const add=await request(config.ajax,{method:'POST',headers:{cookie:previewCookie},body:new URLSearchParams({action:'study_cart',nonce:config.nonce,add:products[0].id})});
const cart=await add.json();
if(!cart.success||cart.data.count!==1)throw Error('Cart add failed');
const cookie=previewCookie+'; '+add.headers.getSetCookie().map(c=>c.split(';')[0]).join('; ');
const persist=await request(config.ajax,{method:'POST',headers:{cookie},body:new URLSearchParams({action:'study_cart',nonce:config.nonce})});
if((await persist.json()).data.count!==1)throw Error('Cart did not persist');
console.log('Add to cart and cross-request persistence passed.');
for(const [name,url] of [['product',products[0].url],['checkout',config.checkout],['login',access.preview_url+'wp-login.php']]){
 const r=await request(url,{headers:{cookie}});if(r.status!==200)throw Error(name+' HTTP '+r.status);console.log(name+' HTTP 200.');
}
const unauth=await request(access.preview_url);const publicHtml=await unauth.text();if(unauth.status!==200||!publicHtml.includes('catalog-data')||publicHtml.includes('name="password"'))throw Error('Catalog is not directly accessible');
console.log('Anonymous visitors see the catalog directly without a preview password.');
const current=await fetch('http://39.106.108.70/');if(current.status!==200)throw Error('Existing site unavailable');console.log('Existing Clipflow site HTTP 200.');
await request(config.ajax,{method:'POST',headers:{cookie},body:new URLSearchParams({action:'study_cart',nonce:config.nonce,remove:cart.data.items[0].key})});
