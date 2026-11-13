<?php
defined('ABSPATH')||exit;
if(get_option('woocommerce_currency')!=='USD')throw new RuntimeException('Expected USD base currency.');
$ids=get_option('seleneith_shipping_zone_ids',[]);
if(count($ids)!==4)throw new RuntimeException('Expected four supported shipping zones.');
// Validate every zone before changing any price.
$updates=[];
foreach($ids as $id){
 $zone=new WC_Shipping_Zone($id);$found=[];
 foreach($zone->get_shipping_methods(true) as $method){
  if(!in_array($method->id,['flat_rate','free_shipping'],true))continue;
  $found[$method->id]=true;$updates[]=$method;
 }
 if(empty($found['flat_rate'])||empty($found['free_shipping']))throw new RuntimeException('Missing shipping method for zone '.$id);
}
foreach($updates as $method){
 $key='woocommerce_'.$method->id.'_'.$method->instance_id.'_settings';$settings=get_option($key,[]);
 if($method->id==='flat_rate')$settings['cost']='9.90';
 else{$settings['min_amount']='79';$settings['requires']='min_amount';$settings['ignore_discounts']='no';}
 update_option($key,$settings);
}
foreach(['shipping-policy','shipping-return','faq'] as $slug){
 $page=get_page_by_path('pages/'.$slug);if(!$page)throw new RuntimeException('Missing page '.$slug);
 $content=str_replace(['US$7.99','US$100'],['US$9.90','US$79'],$page->post_content);
 $result=wp_update_post(wp_slash(['ID'=>$page->ID,'post_content'=>$content]),true);
 if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
}
WC_Cache_Helper::get_transient_version('shipping',true);
WP_CLI::success('Four zones: USD9.90 below USD79 after discounts; free from USD79. Shipping pages and FAQ updated; tax settings unchanged.');
