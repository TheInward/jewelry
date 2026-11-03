<?php
defined('ABSPATH')||exit;
function shipping_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
$ids=get_option('seleneith_shipping_zone_ids');
$cases=[['US','CA',$ids[0]],['US','DC',$ids[0]],['US','AK',$ids[1]],['US','HI',$ids[1]],['PR','',$ids[1]],['DE','',$ids[2]],['GB','',$ids[2]],['CA','ON',$ids[3]],['AU','NSW',$ids[3]],['NZ','',$ids[3]],['SG','',$ids[4]],['MY','',$ids[4]],['JP','',0]];
foreach($cases as [$country,$state,$expected]){$zone=WC_Shipping_Zones::get_zone_matching_package(['destination'=>['country'=>$country,'state'=>$state,'postcode'=>'10001']]);shipping_assert($zone->get_id()===$expected,$country.':'.$state.' wrong zone');}
$selling=WC()->countries->get_allowed_countries();$shipping=WC()->countries->get_shipping_countries();
foreach(seleneith_excluded_countries() as $country){shipping_assert(!isset($selling[$country])&&!isset($shipping[$country]),'Excluded country selectable: '.$country);$error=new WP_Error();do_action('woocommerce_after_checkout_validation',['billing_country'=>$country],$error);shipping_assert($error->has_errors(),'Excluded checkout not rejected');shipping_assert(apply_filters('woocommerce_package_rates',['test'=>(object)['method_id'=>'flat_rate']],['destination'=>['country'=>$country]])===[],'Excluded destination has rates');}
foreach(seleneith_excluded_countries() as $country){$order=new WC_Order();$order->set_billing_country($country);$rejected=false;try{do_action('woocommerce_store_api_checkout_update_order_from_request',$order, null);}catch(\Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e){$rejected=true;}shipping_assert($rejected,'Store API destination bypass: '.$country);}
if(!WC()->session)WC()->initialize_session();if(!WC()->cart)WC()->initialize_cart();
WC()->cart->empty_cart();
$product=new WC_Product_Simple();$product->set_id(9999999);$product->set_status('publish');$product->set_price('99.99');
$line=['key'=>'test','product_id'=>9999999,'variation_id'=>0,'variation'=>[],'quantity'=>1,'data'=>$product,'line_total'=>99.99,'line_subtotal'=>99.99,'line_tax'=>0,'line_subtotal_tax'=>0,'line_tax_data'=>['subtotal'=>[],'total'=>[]]];
try{
foreach(array_merge($ids,[0]) as $id){
 $zone=new WC_Shipping_Zone($id);$methods=$zone->get_shipping_methods(true);$flat=null;$free=null;foreach($methods as $m){if($m->id==='flat_rate')$flat=$m;if($m->id==='free_shipping')$free=$m;}
 shipping_assert($flat&&$free,'Missing shipping methods');shipping_assert(abs((float)$flat->get_option('cost')-7.99)<0.001,'Wrong standard cost');shipping_assert((float)$free->get_option('min_amount')===100.0,'Wrong threshold');shipping_assert($free->get_option('ignore_discounts')==='no','Threshold must use discounted amount');
 foreach([[99.99,false],[100,true]] as [$amount,$expected]){WC()->cart->set_cart_contents(['test'=>$line]);WC()->cart->set_subtotal($amount);WC()->cart->set_subtotal_tax(0);WC()->cart->set_discount_total(0);WC()->cart->set_discount_tax(0);shipping_assert($free->is_available([])===$expected,'Threshold boundary failed '.$id.' '.$amount);}
 WC()->cart->set_subtotal(100);WC()->cart->set_discount_total(1);shipping_assert(!$free->is_available([]),'Discount below threshold must remove free shipping');
}
WP_CLI::success('13 regional addresses, seven blocked destinations, six zones and USD99.99/100/after-discount boundaries passed.');
}finally{WC()->cart->empty_cart();WC()->session->destroy_session();}
