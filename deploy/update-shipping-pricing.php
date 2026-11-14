<?php
defined('ABSPATH')||exit;
if(get_option('woocommerce_currency')!=='USD')throw new RuntimeException('Expected USD base currency.');
$ids=get_option('seleneith_shipping_zone_ids',[]);
if(count($ids)!==4)throw new RuntimeException('Expected four supported shipping zones.');
// Validate every zone before changing any price.
$updates=[];
foreach($ids as $zone_index=>$id){
 $zone=new WC_Shipping_Zone($id);$found=[];
 foreach($zone->get_shipping_methods(true) as $method){
  if(!in_array($method->id,['flat_rate','free_shipping'],true))continue;
  $found[$method->id]=true;$updates[]=[$zone_index,$method];
 }
 if(empty($found['flat_rate'])||empty($found['free_shipping']))throw new RuntimeException('Missing shipping method for zone '.$id);
}
foreach($updates as [$zone_index,$method]){
 $key='woocommerce_'.$method->id.'_'.$method->instance_id.'_settings';$settings=get_option($key,[]);
 if($method->id==='flat_rate')$settings['cost']=(string)[9.90,9.90,12.90,15.90][$zone_index];
 else{$settings['min_amount']='59';$settings['requires']='min_amount';$settings['ignore_discounts']='no';}
 update_option($key,$settings);
}
require __DIR__.'/update-seleneith-policies.php';
WC_Cache_Helper::get_transient_version('shipping',true);
WP_CLI::success('Four zones: US USD9.90, CA USD12.90, AU USD15.90 below USD59; free from USD59 after discounts. Shipping pages and FAQ updated; tax settings unchanged.');
