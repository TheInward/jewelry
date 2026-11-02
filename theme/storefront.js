(()=>{
 const image=document.getElementById('gallery-active');
 if(!image)return;
 const dialog=document.querySelector('.gallery-dialog');
 document.querySelectorAll('[data-gallery-image]').forEach(button=>button.addEventListener('click',()=>{
  image.src=button.dataset.galleryImage;
  document.querySelectorAll('[data-gallery-image]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
 }));
 document.querySelector('.gallery-zoom').addEventListener('click',()=>{dialog.querySelector('img').src=image.src;dialog.showModal()});
 document.querySelector('.gallery-close').addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close()});
})();
document.querySelectorAll('[data-buy-now]').forEach(button=>button.addEventListener('click',()=>{
 let marker=button.form.querySelector('[name="study_buy_now"]');if(!marker){marker=document.createElement('input');marker.type='hidden';marker.name='study_buy_now';button.form.append(marker)}marker.value='1';
}));
document.querySelectorAll('.single_add_to_cart_button').forEach(button=>button.addEventListener('click',()=>button.form.querySelector('[name="study_buy_now"]')?.remove()));
if(window.jQuery)window.jQuery(document).on('found_variation','form.variations_form',function(e,variation){const image=document.getElementById('gallery-active');if(image&&variation.study_image){image.src=variation.study_image;document.querySelectorAll('[data-gallery-image]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.galleryImage===variation.study_image)))}});

document.querySelectorAll('[data-slide]').forEach(button=>button.addEventListener('click',()=>{const track=button.closest('.home-products-section').querySelector('.home-product-track');track.scrollBy({left:track.clientWidth*Number(button.dataset.slide),behavior:'smooth'})}));
