<?php
defined('ABSPATH') || exit;
function study_product_card($p, $editorial = false) {
    $product=wc_get_product($p['id']);
    if(!$product)return;
    $add_id=$editorial?study_editorial_add_id($product):0;
    $featured=$add_id?wc_get_product($add_id):$product;
    if($editorial && $add_id)$p['price']=(float)$featured->get_price();
    $offer=$editorial?study_editorial_offer($featured,(float)$p['price']):null;
    ?>
    <article class="product-card<?php if($editorial)echo ' editorial-card'; ?>">
      <button class="wish" data-wish="<?php echo (int)$p['id']; ?>" aria-label="<?php echo esc_attr(study_t('Your Wishlist').': '.$p['title']); ?>">♡</button>
      <?php if($editorial && $offer['discount']): ?><span class="editorial-sale-badge"><?php echo esc_html(study_t(in_array($product->get_slug(),['waterdrop-whimsical-romance-stone-necklace-set','pink-petal-layered-set'],true)?'Bundle Price':'Sale')); ?></span><?php endif; ?>
      <a class="product-image" href="<?php echo esc_url($p['url']); ?>"><?php foreach(array_slice($p['images'],0,2) as $n=>$image): ?><img <?php if($n)echo 'class="second"'; ?> src="<?php echo esc_url($image.(str_contains($image,'?')?'&':'?').'width=650'); ?>" alt="<?php echo $n?'':esc_attr($p['title']); ?>" loading="lazy"><?php endforeach; ?></a>
      <?php if($product->is_type('variable') && !$add_id): ?><a class="quick-add home-add" href="<?php echo esc_url($p['url']); ?>"><?php echo esc_html(study_t('Choose options')); ?></a><?php else: ?><button class="quick-add home-add" data-add="<?php echo (int)($add_id?:$p['id']); ?>" <?php disabled(!$p['available']); ?>><?php echo esc_html(($editorial&&$p['available']?'+ ':'').study_t($p['available']?'Add to bag':'Sold out')); ?></button><?php endif; ?>
      <a class="product-name" href="<?php echo esc_url($p['url']); ?>"><?php echo esc_html($p['title']); ?></a>
      <?php if(array_filter($p['tags'],function($tag){return str_contains(strtolower($tag),'waterproof');})): ?><span class="card-detail"><?php echo esc_html(study_t('Waterproof')); ?></span><?php endif; ?>
      <span class="price"><?php if($editorial): ?>
        <?php if($offer['discount']): ?><del class="editorial-regular"><?php echo wp_kses_post(wc_price($offer['regular'])); ?></del><?php endif; ?>
        <span class="editorial-current<?php if($offer['discount'])echo ' discounted'; ?>"><?php echo wp_kses_post(wc_price($p['price'])); ?><?php if($offer['discount']): ?> <span class="editorial-discount">(-<?php echo (int)$offer['discount']; ?>% OFF)</span><?php endif; ?></span>
      <?php else: ?><span class="desktop-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></span><span class="mobile-card-price"><?php echo wp_kses_post(wc_price($p['price'])); ?></span><?php endif; ?></span>
      <?php study_card_swatches($product); ?>
    </article><?php
}
// Use the same variation for the displayed price and its comparison price.
function study_editorial_offer($product,$price){
 $regular=(float)$product->get_regular_price();
 if($product->is_type('variable')){
  $regular=$price;
  foreach($product->get_children() as $id){$variation=wc_get_product($id);if($variation && abs((float)$variation->get_price()-$price)<0.001){$regular=(float)$variation->get_regular_price();break;}}
 }
 return ['regular'=>$regular,'discount'=>$regular>$price&&$regular>0?(int)round(100*(1-$price/$regular)):0];
}
// The card and quick-add both use the complete, available default variation.
function study_editorial_add_id($product){
 if(!$product->is_type('variable'))return $product->is_in_stock()?$product->get_id():0;
 $defaults=$product->get_default_attributes();if(!$defaults||count($defaults)!==count($product->get_variation_attributes()))return 0;
 foreach($product->get_children() as $id){
  $variation=wc_get_product($id);if(!$variation||!$variation->is_in_stock()||!$variation->is_purchasable())continue;
  $attrs=$variation->get_attributes();$matches=true;foreach($defaults as $key=>$value)if(!isset($attrs[$key])||sanitize_title($attrs[$key])!==sanitize_title($value)){$matches=false;break;}
  if($matches)return $id;
 }
 return 0;
}
add_action('wp_enqueue_scripts',function(){
    wp_enqueue_style('study-storefront',get_template_directory_uri().'/storefront.css',['study-home'],filemtime(__DIR__.'/storefront.css'));
    wp_enqueue_style('study-mobile',get_template_directory_uri().'/mobile.css',['study-storefront'],filemtime(__DIR__.'/mobile.css'));
    wp_enqueue_style('study-editorial',get_template_directory_uri().'/editorial.css',['study-mobile'],filemtime(__DIR__.'/editorial.css'));
    wp_enqueue_script('study-mobile',get_template_directory_uri().'/mobile.js',['study-storefront'],filemtime(__DIR__.'/mobile.js'),true);
    wp_enqueue_script('study-storefront',get_template_directory_uri().'/storefront.js',['study'],filemtime(__DIR__.'/storefront.js'),true);
});
add_action('init',function(){
    remove_action('woocommerce_before_single_product_summary','woocommerce_show_product_images',20);
    add_action('woocommerce_before_single_product_summary','study_product_gallery',20);
});
function study_product_gallery(){
    global $product;$images=$product->get_meta('_study_images');if(!$images)return;
    ?><div class="study-gallery"><button class="wish mobile-gallery-wish" data-wish="<?php echo (int)$product->get_id(); ?>" aria-label="<?php echo esc_attr(study_t('Your Wishlist')); ?>">♡</button><div class="gallery-main"><button type="button" class="gallery-zoom" aria-label="Zoom product image"><img id="gallery-active" src="<?php echo esc_url($images[0]); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" fetchpriority="high"></button></div><div class="gallery-thumbnails" aria-label="Product images"><?php foreach($images as $i=>$image): ?><button type="button" data-gallery-image="<?php echo esc_url($image); ?>" aria-label="<?php echo esc_attr('Image '.($i+1)); ?>" aria-pressed="<?php echo $i?'false':'true'; ?>"><img src="<?php echo esc_url($image.(str_contains($image,'?')?'&':'?').'width=150'); ?>" alt="" loading="lazy"></button><?php endforeach; ?></div><dialog class="gallery-dialog"><button type="button" class="gallery-close" aria-label="Close">×</button><img alt="<?php echo esc_attr($product->get_name()); ?>"></dialog></div><?php
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

function study_mobile_editorial($slug,$title,$image,$class){
 $desktop=['gold'=>'ad32d5c992d4f65933fc3156.jpg','silver'=>'aad805bc4a4071ab15032616.jpg','sets'=>'3801171b7c3ae50ea085d2d3.png'];
 ?><section class="mobile-editorial editorial-<?php echo esc_attr($class); ?>" aria-label="<?php echo esc_attr($title); ?>"><a class="editorial-banner" href="<?php echo esc_url(study_collection_url($slug)); ?>"><picture><source media="(min-width:768px)" srcset="https://seleneith.com/wp-content/uploads/seleneith-media/<?php echo esc_attr($desktop[$class]); ?>?width=1400"><img src="https://seleneith.com/wp-content/uploads/seleneith-media/<?php echo esc_attr($image); ?>?width=768" alt="<?php echo esc_attr($title); ?>" loading="lazy"></picture><span class="editorial-caption"><?php echo esc_html(study_language()==='en'?$title:study_t($title)); ?><small><?php echo esc_html(study_t('SHOP THE COLLECTION')); ?></small></span></a><div class="editorial-products home-product-track"><?php foreach(study_home_products($slug) as $item)study_product_card($item,true); ?></div></section><?php
}
function study_product_colors($product){
 if(!$product->is_type('variable'))return ['attribute'=>'','values'=>[]];
 foreach($product->get_variation_attributes() as $name=>$values)if(in_array(strtolower($name),['color','colour','metal color'],true))return ['attribute'=>sanitize_title($name),'values'=>array_values($values)];
 return ['attribute'=>'','values'=>[]];
}
function study_color_hex($name){return ['gold'=>'#ecd78d','silver'=>'#d2d3d5','rose gold'=>'#d6aaa0','black'=>'#222','white'=>'#f5f5f5','green'=>'#629276','blue'=>'#638bba','marine blue'=>'#79b8ca'][strtolower($name)]??'#d5d5d5';}
function study_card_swatches($product){$colors=study_product_colors($product);if(!$colors['values'])return;echo '<div class="card-colors">';foreach($colors['values'] as $color)echo '<a class="card-color" style="--swatch:'.esc_attr(study_color_hex($color)).'" href="'.esc_url(add_query_arg('attribute_'.$colors['attribute'],$color,get_permalink($product->get_id()))).'" aria-label="'.esc_attr($product->get_name().' — '.$color).'" title="'.esc_attr($color).'"></a>';echo '</div>';}
