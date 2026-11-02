(()=>{
 const mobile=()=>matchMedia('(max-width:767px)').matches;
 const nav=document.getElementById('main-navigation'),toggle=document.querySelector('.mobile-menu-toggle');
 const closeMenu=()=>{nav?.classList.remove('mobile-open');toggle?.setAttribute('aria-expanded','false');document.body.classList.remove('mobile-nav-open');toggle?.focus()};
 document.getElementById('mobile-menu-close')?.addEventListener('click',closeMenu);
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(nav?.classList.contains('mobile-open'))closeMenu();closeFilters()}if(e.key==='Tab'&&mobile()&&nav?.classList.contains('mobile-open')){const nodes=[...nav.querySelectorAll('a,button,summary,input')].filter(n=>n.getClientRects().length&&!n.disabled);const first=nodes[0],last=nodes.at(-1);if(e.shiftKey&&document.activeElement===first){e.preventDefault();last?.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first?.focus()}}});
 const header=document.querySelector('.site-header');const scrollHeader=()=>header?.classList.toggle('mobile-scrolled',scrollY>70);addEventListener('scroll',scrollHeader,{passive:true});scrollHeader();
 const layout=document.querySelector('.catalog-layout');
 function closeFilters(){layout?.classList.remove('filter-open');document.getElementById('filter-toggle')?.setAttribute('aria-expanded','false');document.body.classList.remove('filter-modal-open')}
 document.querySelectorAll('[data-close-filters]').forEach(b=>b.addEventListener('click',()=>{closeFilters();document.getElementById('filter-toggle')?.focus()}));
 if(layout)new MutationObserver(()=>document.body.classList.toggle('filter-modal-open',mobile()&&layout.classList.contains('filter-open'))).observe(layout,{attributes:true,attributeFilter:['class']});
 addEventListener('resize',()=>{if(!mobile()){document.body.classList.remove('mobile-nav-open','filter-modal-open');nav?.classList.remove('mobile-open');toggle?.setAttribute('aria-expanded','false')}});
 const track=document.querySelector('.mobile-category-track');if(track)track.addEventListener('scroll',()=>{const progress=track.parentElement.querySelector('.mobile-category-progress');progress?.style.setProperty('--progress',String(track.scrollLeft/Math.max(1,track.scrollWidth-track.clientWidth)))},{passive:true});
 const gallery=document.querySelector('.gallery-main');let startX=0,startY=0;
 gallery?.addEventListener('touchstart',e=>{startX=e.changedTouches[0].clientX;startY=e.changedTouches[0].clientY},{passive:true});gallery?.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-startX,dy=e.changedTouches[0].clientY-startY;if(Math.abs(dx)<45||Math.abs(dx)<Math.abs(dy))return;const buttons=[...document.querySelectorAll('[data-gallery-image]')];let index=buttons.findIndex(b=>b.getAttribute('aria-pressed')==='true');buttons[(index+(dx<0?1:-1)+buttons.length)%buttons.length]?.click()},{passive:true});
 const form=document.querySelector('form.variations_form');if(!form)return;
 let variations=[];try{variations=JSON.parse(form.dataset.product_variations||'[]');if(!Array.isArray(variations))variations=[]}catch{}
 const palette={gold:'#ecd78d',silver:'#d2d3d5','rose gold':'#d6aaa0',black:'#222',white:'#f5f5f5',green:'#629276',blue:'#638bba','marine blue':'#79b8ca'};
 const money=value=>new Intl.NumberFormat(studyLocale.locale,{style:'currency',currency:studyLocale.currency,minimumFractionDigits:studyLocale.decimals,maximumFractionDigits:studyLocale.decimals}).format(value);
 const enhanced=[];
 form.querySelectorAll('.variations select').forEach(select=>{
  const name=select.name,colors=/colou?r/i.test(name),styles=/style/i.test(name);
  const group=document.createElement('div');group.className='mobile-variation-options'+(styles?' variation-style-options':'');group.setAttribute('role','group');group.setAttribute('aria-label',select.closest('tr')?.querySelector('label')?.textContent||name);
  const choices=[];
  [...select.options].filter(o=>o.value).forEach(option=>{
   const button=document.createElement('button');button.type='button';button.className='variation-choice'+(colors?' variation-color-choice':'');button.setAttribute('aria-label',option.textContent);button.title=option.textContent;button.dataset.value=option.value;
   if(colors)button.style.setProperty('--swatch',palette[option.value.toLowerCase()]||'#d5d5d5');else{const label=document.createElement('span');label.textContent=option.textContent;button.append(label)}
   if(styles){const price=document.createElement('span');price.className='variation-choice-price';button.append(price)}
   button.addEventListener('click',()=>{select.value=option.value;if(window.jQuery)window.jQuery(select).trigger('change');else select.dispatchEvent(new Event('change',{bubbles:true}));sync()});
   group.append(button);choices.push(button);
  });
  const access=()=>{select.tabIndex=mobile()?-1:0;select.setAttribute('aria-hidden',String(mobile()))};access();addEventListener('resize',access);
  select.classList.add('native-options-enhanced');select.insertAdjacentElement('afterend',group);enhanced.push({select,choices,styles});
 });
 function sync(){const selected=Object.fromEntries([...form.querySelectorAll('.variations select')].map(s=>[s.name,s.value]));for(const {select,choices,styles}of enhanced)for(const button of choices){button.setAttribute('aria-pressed',String(select.value===button.dataset.value));const option=[...select.options].find(o=>o.value===button.dataset.value);const matching=variations.filter(v=>Object.entries(v.attributes).every(([name,value])=>!value||value===(name===select.name?button.dataset.value:selected[name])||(!selected[name]&&name!==select.name)));button.disabled=!option||option.disabled||(variations.length>0&&!matching.some(v=>v.is_in_stock));if(styles){const variant=matching.find(v=>v.is_in_stock);button.querySelector('.variation-choice-price').textContent=variant?money(variant.display_price):''}}}
 form.addEventListener('change',()=>requestAnimationFrame(sync));
 if(window.jQuery)window.jQuery(form).on('reset_data',()=>document.querySelector('.summary>p.price')?.classList.remove('mobile-has-variation-price')).on('woocommerce_update_variation_values reset_data',()=>requestAnimationFrame(sync)).on('found_variation',(event,variation)=>{sync();const price=document.querySelector('.summary>p.price');if(price){let text=price.querySelector('.mobile-selected-price');if(!text){const original=document.createElement('span');original.className='mobile-original-price';while(price.firstChild)original.append(price.firstChild);price.append(original);text=document.createElement('span');text.className='mobile-selected-price';price.append(text)}text.textContent=money(variation.display_price);price.classList.add('mobile-has-variation-price')}});
 sync();

})();
