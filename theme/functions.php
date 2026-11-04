<?php
defined('ABSPATH') || exit;
require_once __DIR__.'/requests.php';
require_once __DIR__.'/branding.php';
require_once __DIR__.'/operations.php';
require_once __DIR__.'/accounts.php';
require_once __DIR__.'/localization.php';
require_once __DIR__.'/catalog.php';
require_once __DIR__.'/signup.php';
require_once __DIR__.'/storefront.php';
add_action('after_setup_theme', function(){ add_theme_support('title-tag'); add_theme_support('woocommerce'); add_theme_support('wc-product-gallery-slider'); });
add_action('wp_enqueue_scripts', function(){ wp_enqueue_style('study',get_stylesheet_uri(),[],filemtime(__DIR__.'/style.css')); wp_enqueue_style('study-home',get_template_directory_uri().'/home.css',['study','study-markets'],filemtime(__DIR__.'/home.css')); wp_enqueue_script('study',get_template_directory_uri().'/app.js',[],filemtime(__DIR__.'/app.js'),true); wp_enqueue_script('study-signup',get_template_directory_uri().'/signup.js',[],filemtime(__DIR__.'/signup.js'),true); wp_localize_script('study','studyConfig',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('study-cart'),'loggedIn'=>is_user_logged_in(),'checkout'=>function_exists('wc_get_checkout_url')?wc_get_checkout_url():'']); });
add_filter('wp_robots',function($r){$r['noindex']=true;$r['nofollow']=true;return $r;});
add_filter('woocommerce_product_get_image',function($html,$p){$images=$p->get_meta('_study_images');return $images?'<img src="'.esc_url($images[0]).'" alt="'.esc_attr($p->get_name()).'">':$html;},10,2);
add_filter('woocommerce_single_product_image_thumbnail_html',function($html,$id){global $product;$images=$product?$product->get_meta('_study_images'):[];if(!$images)return $html;return implode('',array_map(function($url){return '<img loading="lazy" src="'.esc_url($url).'" alt="">';},array_slice($images,0,5)));},10,2);
function study_cart(){ $items=[];if(WC()->cart){foreach(WC()->cart->get_cart() as $key=>$line){$p=$line['data'];$images=$p->get_meta('_study_images');$items[]=['key'=>$key,'id'=>$p->get_id(),'product_id'=>$line['product_id'],'name'=>wp_specialchars_decode($p->get_name(), ENT_QUOTES),'quantity'=>$line['quantity'],'price'=>(float)$p->get_price(),'image'=>$images[0]??''];}}return ['currency'=>get_woocommerce_currency(),'items'=>$items,'total'=>WC()->cart?(float)WC()->cart->get_cart_contents_total():0,'count'=>WC()->cart?WC()->cart->get_cart_contents_count():0];}
add_action('wp_ajax_study_cart','study_cart_endpoint');add_action('wp_ajax_nopriv_study_cart','study_cart_endpoint');
function study_cart_endpoint(){
 check_ajax_referer('study-cart','nonce');
 if(!function_exists('WC'))wp_send_json_error('WooCommerce unavailable',503);
 if(!WC()->cart)wc_load_cart();
 $notices=wc_get_notices();
 try{
  $operation=study_cart_operation($_POST);
  if($operation==='add')study_cart_add(WC()->cart,study_post_text('add',20));
  if($operation==='remove'){
   $key=study_post_text('remove',64);
   if(!WC()->cart->get_cart_item($key)||!WC()->cart->remove_cart_item($key))throw new InvalidArgumentException('This item is no longer in your bag. Please refresh.');
  }
  if($operation==='quantity'){
   $key=study_post_text('key',64);
   $quantity=study_cart_quantity(WC()->cart,$key,study_post_text('quantity',9));
   if(!WC()->cart->set_quantity($key,$quantity))throw new RuntimeException('Could not update this item.');
  }
  WC()->cart->calculate_totals();$data=study_cart();
 }catch(InvalidArgumentException $e){
  wc_set_notices($notices);wp_send_json_error(wp_strip_all_tags($e->getMessage()),400);
 }catch(Throwable $e){
  wc_set_notices($notices);wp_send_json_error('Could not update your bag. Please refresh and retry.',500);
 }
 wc_set_notices($notices);wp_send_json_success($data);
}
// Reject ambiguous requests before any cart mutation can happen.
function study_cart_operation($input){
 $operations=array_intersect(['add','remove','quantity'],array_keys($input));
 if(count($operations)>1||array_key_exists('quantity',$input)!==array_key_exists('key',$input))throw new InvalidArgumentException('Please submit one cart change at a time.');
 return $operations?reset($operations):'';
}
function study_cart_add($cart,$raw){
 if(!is_string($raw)||!preg_match('/^[1-9][0-9]{0,9}$/D',$raw))throw new InvalidArgumentException('Please choose a valid product.');
 $product=wc_get_product((int)$raw);
 if(!$product||!$product->is_type(['simple','variation'])||!$product->is_purchasable()||!$product->is_in_stock())throw new InvalidArgumentException('This item cannot be added. Please choose available options.');
 $variation_id=$product->is_type('variation')?$product->get_id():0;
 $product_id=$variation_id?$product->get_parent_id():$product->get_id();
 $variation=$variation_id?$product->get_variation_attributes():[];
 if(get_post_status($product_id)!=='publish'||post_password_required($product_id))throw new InvalidArgumentException('This item cannot be added.');
 // Match the native form contract, including variation data for extension rules.
 if(!apply_filters('woocommerce_add_to_cart_validation',true,$product_id,1,$variation_id,$variation,[]))throw new InvalidArgumentException('This item cannot be added. Please check the product options.');
 if(!$cart->add_to_cart($product_id,1,$variation_id,$variation))throw new InvalidArgumentException('This item cannot be added. Please check availability.');
}
// Validate drawer updates before changing the session, as the native cart form does.
function study_cart_quantity($cart,$key,$raw){
 if(!is_scalar($raw)||!preg_match('/^\d{1,9}$/D',(string)$raw))throw new InvalidArgumentException('Please enter a valid whole-number quantity.');
 $quantity=(int)$raw;$line=$cart->get_cart_item($key);
 if(!$line||empty($line['data']))throw new InvalidArgumentException('This item is no longer in your bag. Please refresh.');
 if($quantity===0)return 0;
 $product=$line['data'];$maximum=$product->get_max_purchase_quantity();
 if(!$product->is_purchasable()||!$product->is_in_stock()||($maximum>=0&&$quantity>$maximum))throw new InvalidArgumentException('The requested quantity is not available.');
 $quantities=$cart->get_cart_item_quantities();
 $stock_id=$product->get_stock_managed_by_id();
 $total=($quantities[$stock_id]??0)-(int)$line['quantity']+$quantity;
 if(!$product->has_enough_stock($total)||!apply_filters('woocommerce_update_cart_validation',true,$key,$line,$quantity))throw new InvalidArgumentException('The requested quantity is not available.');
 return $quantity;
}
