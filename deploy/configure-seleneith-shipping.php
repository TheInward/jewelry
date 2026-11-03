<?php
defined('ABSPATH')||exit;
$excluded=['IR','KP','SY','MM','VE','YE','AF'];
update_option('woocommerce_allowed_countries','all_except');
update_option('woocommerce_all_except_countries',$excluded);
update_option('woocommerce_ship_to_countries',''); // Inherit the permitted selling countries.
$states=array_diff(array_keys(WC()->countries->get_states('US')),['AK','HI','AA','AE','AP']);
$eu=['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','GB'];
$definitions=[
 ['1 — United States (contiguous + DC)',array_map(fn($s)=>['code'=>'US:'.$s,'type'=>'state'],$states)],
 ['2 — Alaska, Hawaii & Puerto Rico',[['code'=>'US:AK','type'=>'state'],['code'=>'US:HI','type'=>'state'],['code'=>'PR','type'=>'country']]],
 ['3 — European Union & United Kingdom',array_map(fn($c)=>['code'=>$c,'type'=>'country'],$eu)],
 ['4 — Canada, Australia & New Zealand',array_map(fn($c)=>['code'=>$c,'type'=>'country'],['CA','AU','NZ'])],
 ['5 — Southeast Asia',array_map(fn($c)=>['code'=>$c,'type'=>'country'],['BN','KH','ID','LA','MY','PH','SG','TH','TL','VN'])]
];
$ids=get_option('seleneith_shipping_zone_ids',[]);
if(!$ids){$existing=WC_Shipping_Zones::get_zones();if(count($existing)>1)throw new RuntimeException('Unexpected zones; review before migration.');if($existing){$first=reset($existing);if($first['zone_name']!=='Development — United States')throw new RuntimeException('Unexpected original zone; preserving it.');$ids[0]=$first['id'];}}
foreach($definitions as $index=>[$name,$locations]){
 $zone=new WC_Shipping_Zone($ids[$index]??0);
 if(!isset($ids[$index]))$zone=new WC_Shipping_Zone();
 $zone->set_zone_name($name);$zone->set_zone_order($index+1);$zone->set_zone_locations(array_map(fn($location)=>(object)$location,$locations));$zone->save();$ids[$index]=$zone->get_id();
}
update_option('seleneith_shipping_zone_ids',$ids);
foreach(array_merge($ids,[0]) as $id){
 $zone=new WC_Shipping_Zone($id);$found=[];
 foreach($zone->get_shipping_methods() as $method)$found[$method->id]=$method->instance_id;
 foreach(['flat_rate','free_shipping'] as $name){
  $instance=$found[$name]??$zone->add_shipping_method($name);
  $settings=$name==='flat_rate'?['enabled'=>'yes','title'=>'Standard Shipping','tax_status'=>'none','cost'=>'7.99']:['enabled'=>'yes','title'=>'Free Shipping','requires'=>'min_amount','min_amount'=>'100','ignore_discounts'=>'no'];
  update_option('woocommerce_'.$name.'_'.$instance.'_settings',$settings);
 }
}
WC_Cache_Helper::get_transient_version('shipping',true);
WP_CLI::success('Five ordered zones plus native rest-of-world configured. USD7.99 below USD100; free shipping at USD100 after discounts. Seven destinations excluded.');
