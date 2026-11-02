(()=>{
'use strict';
const normalize=s=>s.replace(/[^\p{L}\p{N}]/gu,'').toLowerCase();
const normalizedStrings=Object.fromEntries(Object.entries(studyLocale.strings).map(([key,value])=>[normalize(key),value]));
const $=s=>document.querySelector(s), t=s=>studyLocale.strings[s]||normalizedStrings[normalize(s)]||s;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=n=>new Intl.NumberFormat(studyLocale.locale,{style:'currency',currency:studyLocale.currency,minimumFractionDigits:studyLocale.decimals,maximumFractionDigits:studyLocale.decimals}).format(Number(n));
document.querySelectorAll('.market-search').forEach(input=>input.addEventListener('input',()=>{
  input.closest('.country-menu').querySelectorAll('[data-country]').forEach(link=>link.hidden=!link.textContent.toLowerCase().includes(input.value.toLowerCase()));
}));
document.addEventListener('click',e=>document.querySelectorAll('.market-dropdown[open]').forEach(menu=>{if(!menu.contains(e.target))menu.open=false}));
document.querySelectorAll('.market-dropdown').forEach(menu=>menu.addEventListener('toggle',()=>{
  if(menu.open)document.querySelectorAll('.market-dropdown').forEach(other=>{if(other!==menu)other.open=false});
}));
if(!$('header .tools'))return;
let wishlistReady=Promise.resolve();
let products=JSON.parse($('#catalog-data')?.textContent||'[]'),limit=24,saved=[];
try{const stored=JSON.parse(localStorage.getItem('study-wishlist')||'[]');if(Array.isArray(stored))saved=stored.filter(Number.isInteger).slice(0,100)}catch{}
const image=(p,n=0)=>{const src=p.images[n]||p.images[0]||'';return src?src+(src.includes('?')?'&':'?')+'width=650':''};
function colorLinks(p){const colors=p.colors;if(!colors?.values?.length)return '';const palette={gold:'#ecd78d',silver:'#d2d3d5','rose gold':'#d6aaa0',black:'#222',white:'#f5f5f5',green:'#629276',blue:'#638bba','marine blue':'#79b8ca'};return '<div class="card-colors">'+colors.values.map(color=>{const url=new URL(p.url);url.searchParams.set('attribute_'+colors.attribute,color);return '<a class="card-color" style="--swatch:'+(palette[color.toLowerCase()]||'#ddd')+'" href="'+esc(url.href)+'" aria-label="'+esc(p.title+' — '+color)+'" title="'+esc(color)+'"></a>'}).join('')+'</div>';}
function card(p){
 return '<article class="product-card"><button class="wish '+(saved.includes(p.id)?'selected':'')+'" data-wish="'+p.id+'" aria-label="'+esc(t('Your Wishlist')+': '+p.title)+'">♡</button>'+
 (p.tags.some(tag=>tag.includes('waterproof'))?'<span class="badge">'+esc(t('Waterproof'))+'</span>':'')+
 '<a class="product-image" href="'+esc(p.url)+'"><img src="'+esc(image(p))+'" alt="'+esc(p.title)+'" loading="lazy">'+
 (p.images[1]?'<img class="second" src="'+esc(image(p,1))+'" alt="" loading="lazy">':'')+'</a>'+
 (p.type==='variable'?'<a class="quick-add home-add" href="'+esc(p.url)+'">'+esc(t('Choose options'))+'</a>':'<button class="quick-add" style="position:relative;width:100%" data-add="'+p.id+'" '+(p.available?'':'disabled')+'>'+esc(t(p.available?'Add to bag':'Sold out'))+'</button>')+
 '<a class="product-name" href="'+esc(p.url)+'">'+esc(p.title)+'</a><span class="price">'+esc(money(p.price))+(p.regular>p.price?'<del>'+esc(money(p.regular))+'</del>':'')+'</span>'+colorLinks(p)+'</article>';
}
function render(){
 if(!$('#product-grid'))return;
 const grouped={};
 document.querySelectorAll('[data-filter]:checked').forEach(input=>(grouped[input.value.split('__')[0]]??=[]).push(input.value));
 let rows=products.filter(p=>Object.values(grouped).every(group=>group.some(tag=>p.tags.includes(tag)))&&p.price>=Number($('#min-price').value||0)&&p.price<=Number($('#max-price').value||Infinity));
 const sort=$('#sort').value;
 if(sort==='price-up')rows.sort((a,b)=>a.price-b.price);
 if(sort==='price-down')rows.sort((a,b)=>b.price-a.price);
 if(sort==='title-up')rows.sort((a,b)=>a.title.localeCompare(b.title,studyLocale.locale));
 if(sort==='title-down')rows.sort((a,b)=>b.title.localeCompare(a.title,studyLocale.locale));
 if(sort==='new')rows.sort((a,b)=>b.created-a.created);
 if(sort==='old')rows.sort((a,b)=>a.created-b.created);
 if(sort==='bestselling')rows.sort((a,b)=>b.sales-a.sales);
 $('#product-grid').innerHTML=rows.slice(0,limit).map(card).join('')||'<p class="empty">'+esc(t('No matching products.'))+'</p>';
 $('#result-count').textContent=rows.length+' '+t('products');
 $('.load-more').hidden=rows.length<=limit;
}
if($('#product-grid')&&$('#filter-groups')){
 const groups={'discount':'Discount','highlight':'Highlight','metal_color_v2':'Metal Color','necklace_style_v2':'Necklace Style','ring_style_v2':'Rings','earring_style_v2':'Earrings','stone_v2':'Stone','accent_color_v2':'Accent Color','theme_v2':'Theme'};
 $('#filter-groups').innerHTML=Object.entries(groups).map(([key,label])=>{
  const tags=[...new Set(products.flatMap(p=>p.tags).filter(tag=>tag.startsWith(key+'__')))];
  return tags.length?'<details><summary>'+esc(t(label))+'</summary>'+tags.map(tag=>'<label><input type="checkbox" data-filter value="'+esc(tag)+'"> '+esc(t(tag.split('__')[1].replaceAll('-',' ').replace(/\b\w/g,c=>c.toUpperCase())))+' ('+products.filter(p=>p.tags.includes(tag)).length+')</label>').join('')+'</details>':'';
 }).join('');
 $('#filter-toggle').onclick=()=>{$('#filter-toggle').setAttribute('aria-expanded',$('.catalog-layout').classList.toggle('filter-open'))};
 $('#sort').onchange=()=>{limit=24;render()};
 $('.filters').onchange=()=>{limit=24;render()};
 $('#clear-filters').onclick=()=>{document.querySelectorAll('[data-filter]').forEach(i=>i.checked=false);$('#min-price').value=$('#max-price').value='';limit=24;render()};
 $('.load-more').onclick=()=>{limit+=24;render()};
 render();
}
function toast(message){$('.toast').textContent=message;$('.toast').classList.add('visible');setTimeout(()=>$('.toast').classList.remove('visible'),2500)}
let previousFocus,searchTimer,searchVersion=0;
function openDrawer(title,body){
 if(!$('#overlay').classList.contains('open'))previousFocus=document.activeElement;
 $('#drawer-title').textContent=t(title);$('#drawer-body').innerHTML=body;$('#overlay').classList.add('open');document.body.style.overflow='hidden';$('#close-drawer').focus();
}
function close(){searchVersion++;clearTimeout(searchTimer);$('#overlay').classList.remove('open');document.body.style.overflow='';previousFocus?.focus()}
$('#close-drawer').onclick=close;
$('#overlay').onclick=e=>{if(e.target===$('#overlay'))close()};
document.addEventListener('keydown',e=>{
 if(e.key==='Escape'){close();document.querySelectorAll('.market-dropdown').forEach(d=>d.open=false)}
 if(e.key==='Tab'&&$('#overlay').classList.contains('open')){
  const nodes=[...$('.drawer').querySelectorAll('button,a,input,select')].filter(node=>!node.disabled&&!node.hidden),first=nodes[0],last=nodes.at(-1);
  if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
 }
});
$('.mobile-menu-toggle').onclick=()=>{const open=$('.nav').classList.toggle('mobile-open');$('.mobile-menu-toggle').setAttribute('aria-expanded',open);document.body.classList.toggle('mobile-nav-open',open);if(open)$('#mobile-menu-close')?.focus()};
async function api(action,values={}){
 const response=await fetch(studyConfig.ajax,{method:'POST',body:new URLSearchParams({action,nonce:studyConfig.nonce,...values}),credentials:'same-origin',cache:'no-store'});
 const payload=await response.json();
 if(!response.ok||!payload.success)throw Error(typeof payload.data==='string'?payload.data:'Request failed. Please refresh and retry.');
 return payload.data;
}
let cartQueue=Promise.resolve();
function cart(values={}){
 const pending=cartQueue.then(()=>api('study_cart',values)).then(data=>{if(data.currency!==studyLocale.currency)throw Error('Currency changed. Please refresh this page.');$('#cart-count').textContent=data.count;$('#cart-count').hidden=data.count===0;return data});
 cartQueue=pending.catch(()=>{});return pending;
}
function cartView(c){
 openDrawer('Your Bag','<p class="shipping-progress">'+esc(t('Shipping calculated at checkout.'))+'</p>'+
 (c.items.map(i=>'<div class="cart-item"><img src="'+esc(i.image)+'" alt=""><div><h3>'+esc(i.name)+'</h3><p>'+esc(money(i.price))+' × '+i.quantity+'</p><button data-qty="'+(i.quantity-1)+'" data-key="'+esc(i.key)+'" aria-label="−">−</button> '+i.quantity+' <button data-qty="'+(i.quantity+1)+'" data-key="'+esc(i.key)+'" aria-label="+">+</button> <button data-remove="'+esc(i.key)+'">'+esc(t('Remove'))+'</button> <button data-move="'+i.product_id+'" data-key="'+esc(i.key)+'">'+esc(t('Move to wishlist'))+'</button></div></div>').join('')||'<p>'+esc(t('Your bag is empty.'))+'</p>')+
 (c.items.length?'<p>'+esc(t('Subtotal'))+' <strong>'+esc(money(c.total))+'</strong></p><a class="primary" href="'+esc(studyConfig.checkout)+'">'+esc(t('Checkout'))+'</a>':''));
}
const results=rows=>rows.map(p=>'<a class="search-result" href="'+esc(p.url)+'"><img src="'+esc(image(p))+'" alt="">'+esc(p.title)+' · '+esc(money(p.price))+'</a>').join('');
document.addEventListener('click',async e=>{
 const button=e.target.closest('button');if(!button)return;
 try{
  if(button.dataset.wish||button.dataset.open==='wishlist'||button.dataset.move)await wishlistReady;
  if(button.dataset.move){const id=Number(button.dataset.move);saved=[...new Set([...saved,id])];if(studyConfig.loggedIn)saved=await api('study_wishlist',{add:id});else try{localStorage.setItem('study-wishlist',JSON.stringify(saved))}catch{};cartView(await cart({remove:button.dataset.key}));return;}
  if(button.dataset.add||button.dataset.remove||button.hasAttribute('data-qty')){
   button.disabled=true;
   try{
    let values=button.dataset.add?{add:button.dataset.add}:button.dataset.remove?{remove:button.dataset.remove}:{quantity:button.dataset.qty,key:button.dataset.key};
    // Disable all quantity controls until the response replaces their stale values.
    if(!button.dataset.add)$('#drawer-body').querySelectorAll('button').forEach(node=>node.disabled=true);
    cartView(await cart(values));
   }finally{button.disabled=false;$('#drawer-body').querySelectorAll('button').forEach(node=>node.disabled=false)}
  }
  if(button.dataset.wish){
   const id=Number(button.dataset.wish);saved=saved.includes(id)?saved.filter(x=>x!==id):[...saved,id].slice(-100);
   if(studyConfig.loggedIn){saved=await api('study_wishlist',saved.includes(id)?{add:id}:{remove:id})}else{try{localStorage.setItem('study-wishlist',JSON.stringify(saved))}catch{}}
   button.classList.toggle('selected',saved.includes(id));toast(t(saved.includes(id)?'Saved to your wishlist':'Removed from wishlist'));
  }
  if(button.dataset.open==='cart')cartView(await cart());
  if(button.dataset.open==='wishlist'){
   const rows=await api('study_products',{ids:saved.join(',')});
   openDrawer('Your Wishlist',results(rows)||'<p>'+esc(t('Your wishlist is empty.'))+'</p>');
  }
  if(button.dataset.open==='search'){
   openDrawer('Search','<input class="searchbox" placeholder="'+esc(t('Search jewelry'))+'" aria-label="'+esc(t('Search jewelry'))+'"><div id="search-results" aria-live="polite"></div>');
   $('.searchbox').focus();
   $('.searchbox').oninput=e=>{
    clearTimeout(searchTimer);const query=e.target.value,version=++searchVersion;$('#search-results').innerHTML='';
    if(query.trim().length<2)return;
    searchTimer=setTimeout(async()=>{try{
     const rows=await api('study_products',{search:query});
     if(version===searchVersion&&$('#search-results'))$('#search-results').innerHTML=(results(rows)||'<p>'+esc(t('No matching products.'))+'</p>')+'<a class="outline-link" href="'+esc(new URL('?post_type=product&s='+encodeURIComponent(query),location.origin).href)+'">'+esc(t('View all'))+' ↗</a>';
    }catch(err){if(version===searchVersion)toast(err.message)}},250);
   };
  }
 }catch(err){toast(err.message)}
});

if(studyConfig.loggedIn)wishlistReady=api('study_wishlist',{merge:saved.join(',')}).then(ids=>{saved=ids;try{localStorage.removeItem('study-wishlist')}catch{}document.querySelectorAll('[data-wish]').forEach(b=>b.classList.toggle('selected',saved.includes(Number(b.dataset.wish))))}).catch(()=>{});
cart().catch(()=>{});
})();
