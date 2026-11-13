<?php
defined('ABSPATH')||exit;
require __DIR__.'/verify-sales-markets.php';
function shipping_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
$ids=get_option('seleneith_shipping_zone_ids');
foreach([['US','CA',$ids[0]],['US','DC',$ids[0]],['US','AK',$ids[1]],['US','HI',$ids[1]],['GB','',$ids[2]],['CA','ON',$ids[3]],['AU','NSW',$ids[3]]] as [$country,$state,$expected]){
 $zone=WC_Shipping_Zones::get_zone_matching_package(['destination'=>['country'=>$country,'state'=>$state,'postcode'=>'10001']]);
 shipping_assert($zone->get_id()===$expected,$country.':'.$state.' wrong zone');
}
$_GET['study_market']='US';$_COOKIE['study_market']='US';
if(!WC()->session)WC()->initialize_session();if(!WC()->cart)WC()->initialize_cart();
// In-memory cart only; no product, coupon or order is saved.
WC()->cart->empty_cart();
try{
 foreach($ids as $id){
  $zone=new WC_Shipping_Zone($id);$flat=null;$free=null;
  foreach($zone->get_shipping_methods(true) as $method){if($method->id==='flat_rate')$flat=$method;if($method->id==='free_shipping')$free=$method;}
  shipping_assert($flat&&$free,'Missing methods');
  shipping_assert(abs((float)$flat->get_option('cost')-9.90)<0.001,'Wrong standard cost');
  shipping_assert((float)$free->get_option('min_amount')===79.0,'Wrong free threshold');
  shipping_assert($free->get_option('ignore_discounts')==='no','Must use discounted subtotal');
  foreach([[78.99,0,false],[79,0,true],[99.99,0,true],[100,0,true],[80,1,true],[79,0.01,false],[80,1.01,false]] as [$subtotal,$discount,$expected]){
   WC()->cart->set_subtotal($subtotal);WC()->cart->set_subtotal_tax(0);WC()->cart->set_discount_total($discount);WC()->cart->set_discount_tax(0);
   shipping_assert($free->is_available([])===$expected,'Threshold failed zone '.$id.' subtotal '.$subtotal.' discount '.$discount);
  }
 }
 foreach(['shipping-policy','shipping-return','faq'] as $slug){$page=get_page_by_path('pages/'.$slug);shipping_assert($page&&strpos($page->post_content,'US$79')!==false&&strpos($page->post_content,'US$9.90')!==false,'Wrong page '.$slug);shipping_assert(strpos($page->post_content,'US$100')===false&&strpos($page->post_content,'US$7.99')===false,'Stale page '.$slug);}
 WP_CLI::success('Seven regional addresses; four-zone USD78.99/79/99.99/100 and discounted boundaries passed. Policy prices verified.');
}finally{WC()->cart->empty_cart();WC()->session->destroy_session();}
