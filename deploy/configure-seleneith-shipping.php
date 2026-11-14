<?php
defined('ABSPATH')||exit;
update_option('woocommerce_allowed_countries','specific');
update_option('woocommerce_specific_allowed_countries',['US','CA','AU']);
update_option('woocommerce_all_except_countries',[]);
update_option('woocommerce_ship_to_countries','specific');
update_option('woocommerce_specific_ship_to_countries',['US','CA','AU']);
$states=array_diff(array_keys(WC()->countries->get_states('US')),['AK','HI','AA','AE','AP']);
$ids=get_option('seleneith_shipping_zone_ids',[]);
if(!$ids){$existing=WC_Shipping_Zones::get_zones();if(count($existing)>1)throw new RuntimeException('Unexpected zones; review before migration.');if($existing){$first=reset($existing);if($first['zone_name']!=='Development — United States')throw new RuntimeException('Unexpected original zone; preserving it.');$ids[0]=$first['id'];}}
if(count($ids)>=4){
 $third_zone=new WC_Shipping_Zone($ids[2]);$has_uk=false;
 foreach($third_zone->get_zone_locations() as $location)if(($location->code??'')==='GB')$has_uk=true;
 if($has_uk){WC_Shipping_Zones::delete_zone((int)$ids[2]);$ids=[$ids[0],$ids[1],$ids[3]];}
 else $ids=array_slice($ids,0,4);
}
$definitions=[
 ['1 — United States (contiguous + DC)',array_map(fn($s)=>['code'=>'US:'.$s,'type'=>'state'],$states)],
 ['2 — Alaska & Hawaii',[['code'=>'US:AK','type'=>'state'],['code'=>'US:HI','type'=>'state']]],
 ['3 — Canada',[['code'=>'CA','type'=>'country']]],
 ['4 — Australia',[['code'=>'AU','type'=>'country']]]
];
foreach($definitions as $index=>[$name,$locations]){
 $zone=new WC_Shipping_Zone($ids[$index]??0);
 if(!isset($ids[$index]))$zone=new WC_Shipping_Zone();
 $zone->set_zone_name($name);$zone->set_zone_order($index+1);$zone->set_zone_locations(array_map(fn($location)=>(object)$location,$locations));$zone->save();$ids[$index]=$zone->get_id();
}
update_option('seleneith_shipping_zone_ids',array_values($ids));
$currency_settings=get_option('_wcml_settings',[]);
$currency_settings['currency_options']=array_intersect_key($currency_settings['currency_options']??[],array_flip(['USD','CAD','AUD']));
$currency_settings['currencies_order']=array_keys($currency_settings['currency_options']);
update_option('_wcml_settings',$currency_settings);
foreach(array_slice($ids,count($definitions)) as $obsolete){(new WC_Shipping_Zone($obsolete))->delete();}
$ids=array_slice($ids,0,count($definitions));
update_option('seleneith_shipping_zone_ids',$ids);
foreach($ids as $index=>$id){
 $zone=new WC_Shipping_Zone($id);$found=[];
 foreach($zone->get_shipping_methods() as $method)$found[$method->id]=$method->instance_id;
 foreach(['flat_rate','free_shipping'] as $name){
  $instance=$found[$name]??$zone->add_shipping_method($name);
  $shipping_cost=[0=>9.90,1=>9.90,2=>12.90,3=>15.90][$index];
  $settings=$name==='flat_rate'?['enabled'=>'yes','title'=>'Standard Shipping','tax_status'=>'none','cost'=>(string)$shipping_cost]:['enabled'=>'yes','title'=>'Free Shipping','requires'=>'min_amount','min_amount'=>'59','ignore_discounts'=>'no'];
  update_option('woocommerce_'.$name.'_'.$instance.'_settings',$settings);
 }
}
// Unsupported destinations must have no fallback shipping methods.
$fallback=new WC_Shipping_Zone(0);
foreach($fallback->get_shipping_methods() as $method){$fallback->delete_shipping_method($method->instance_id);}
WC_Cache_Helper::get_transient_version('shipping',true);
WP_CLI::success('US, CA and AU only; four shipping zones, no rest-of-world delivery. Below USD59: US USD9.90, CA USD12.90, AU USD15.90. Free from USD59 after discounts.');
