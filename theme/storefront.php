<?php
defined('ABSPATH') || exit;
function study_product_card($p) {
    $product=wc_get_product($p['id']);
    if(!$product)return;
    ?><article class="product-card"><button class="wish" data-wish="<?php echo (int)$p['id']; ?>" aria-label="<?php echo esc_attr(study_t('Your Wishlist').': '.$p['title']); ?>">♡</button><a class="product-image" href="<?php echo esc_url($p['url']); ?>"><?php foreach(array_slice($p['images'],0,2) as $n=>$image): ?><img <?php if($n)echo 'class="second"'; ?> src="<?php echo esc_url($image.(str_contains($image,'?')?'&':'?').'width=650'); ?>" alt="<?php echo $n?'':esc_attr($p['title']); ?>" loading="lazy"><?php endforeach; ?></a><?php if($product->is_type('variable')): ?><a class="quick-add home-add" href="<?php echo esc_url($p['url']); ?>"><?php echo esc_html(study_t('Choose options')); ?></a><?php else: ?><button class="quick-add home-add" data-add="<?php echo (int)$p['id']; ?>" <?php disabled(!$p['available']); ?>><?php echo esc_html(study_t($p['available']?'Add to bag':'Sold out')); ?></button><?php endif; ?><a class="product-name" href="<?php echo esc_url($p['url']); ?>"><?php echo esc_html($p['title']); ?></a><span class="price"><?php echo wp_kses_post($product->get_price_html()); ?></span></article><?php
}
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_style('study-storefront',get_template_directory_uri().'/storefront.css',['study-home'],filemtime(__DIR__.'/storefront.css'));
    wp_enqueue_script('study-storefront',get_template_directory_uri().'/storefront.js',['study'],filemtime(__DIR__.'/storefront.js'),true);
});
add_action('init',function(){
    remove_action('woocommerce_before_single_product_summary','woocommerce_show_product_images',20);
    add_action('woocommerce_before_single_product_summary','study_product_gallery',20);
});
function study_product_gallery(){
    global $product;$images=$product->get_meta('_study_images');if(!$images)return;
    ?><div class="study-gallery"><div class="gallery-main"><button type="button" class="gallery-zoom" aria-label="Zoom product image"><img id="gallery-active" src="<?php echo esc_url($images[0]); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" fetchpriority="high"></button></div><div class="gallery-thumbnails" aria-label="Product images"><?php foreach($images as $i=>$image): ?><button type="button" data-gallery-image="<?php echo esc_url($image); ?>" aria-label="<?php echo esc_attr('Image '.($i+1)); ?>" aria-pressed="<?php echo $i?'false':'true'; ?>"><img src="<?php echo esc_url($image.(str_contains($image,'?')?'&':'?').'width=150'); ?>" alt="" loading="lazy"></button><?php endforeach; ?></div><dialog class="gallery-dialog"><button type="button" class="gallery-close" aria-label="Close">×</button><img alt="<?php echo esc_attr($product->get_name()); ?>"></dialog></div><?php
}
add_action('woocommerce_single_product_summary',function(){
 global $product;$tags=$product->get_meta('_study_tags')?:[];$materials=[];
 foreach($tags as $tag)if(str_starts_with($tag,'product_materials__'))$materials[]=ucwords(str_replace('-',' ',substr($tag,19)));
 echo '<div class="product-information">';
 if($materials)echo '<details open><summary>'.esc_html(study_t('Materials')).'</summary><p>'.esc_html(implode(' · ',$materials)).'</p></details>';
 foreach(['Shipping & Return'=>'shipping-return','Jewelry Care'=>'jewelry-care'] as $label=>$slug)echo '<details><summary>'.esc_html(study_t($label)).'</summary><a href="'.esc_url(home_url('/pages/'.$slug.'/')).'">'.esc_html(study_t('View details')).' ↗</a></details>';
 echo '</div>';
},35);
add_action('wp_ajax_study_wishlist',function(){
 check_ajax_referer('study-cart','nonce');
 $ids=array_values(array_filter(array_map('absint',(array)get_user_meta(get_current_user_id(),'_study_wishlist',true))));
 if(isset($_POST['merge']))$ids=array_merge($ids,array_map('absint',explode(',',sanitize_text_field(wp_unslash($_POST['merge'])))));
 if(isset($_POST['add']))$ids[]=absint($_POST['add']);
 if(isset($_POST['remove']))$ids=array_diff($ids,[absint($_POST['remove'])]);
 $ids=array_slice(array_values(array_unique(array_filter($ids,function($id){return get_post_type($id)==='product'&&get_post_status($id)==='publish';}))),-100);
 update_user_meta(get_current_user_id(),'_study_wishlist',$ids);wp_send_json_success($ids);
});
add_filter('woocommerce_available_variation',function($data,$product,$variation){$images=$variation->get_meta('_study_images');if($images)$data['study_image']=$images[0];return $data;},10,3);
add_action('woocommerce_after_add_to_cart_button',function(){global $product;?><button type="submit" name="add-to-cart" value="<?php echo (int)$product->get_id(); ?>" class="study-buy-now" data-buy-now><?php echo esc_html(study_t('Buy Now')); ?></button><?php });
add_filter('woocommerce_add_to_cart_redirect',function($url){return !empty($_REQUEST['study_buy_now'])?wc_get_checkout_url():$url;});
function study_home_products($scope){
 $featured=json_decode(file_get_contents(__DIR__.'/home-featured.json'),true)?:[];$ids=[];
 foreach($featured[$scope]??[] as $slug){$post=get_page_by_path($slug,OBJECT,'product');if($post)$ids[]=$post->ID;}
 $rows=study_catalog_data(null,$ids);$index=array_flip($ids);usort($rows,function($a,$b)use($index){return ($index[$a['id']]??999)<=>($index[$b['id']]??999);});return $rows;
}

add_filter('woocommerce_attribute_label',function($label){return study_t($label);});
add_filter('woocommerce_variation_option_name',function($label){return study_t($label);});
