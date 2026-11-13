<?php
defined('ABSPATH')||exit;
function market_assert($condition,$message){if(!$condition)throw new RuntimeException($message);}
$expected=['AU','CA','GB','US'];
foreach([WC()->countries->get_allowed_countries(),WC()->countries->get_shipping_countries(),study_markets()] as $list){$codes=array_keys($list);sort($codes);market_assert($codes===$expected,'Unexpected country list');}
$_GET['study_language']='fr';$_COOKIE['study_language']='ja';market_assert(study_language()==='en','Old language preference bypass');
$_GET['study_market']='DE';market_assert(study_market()==='US','Old market bypass');unset($_GET['study_market']);
foreach(array_keys(WC()->countries->get_countries()) as $country){
 $supported=in_array($country,$expected,true);
 $errors=new WP_Error();do_action('woocommerce_after_checkout_validation',['billing_country'=>$country],$errors);
 market_assert($errors->has_errors()!==$supported,'Classic checkout: '.$country);
 $rates=apply_filters('woocommerce_package_rates',['test'=>(object)['method_id'=>'flat_rate']],['destination'=>['country'=>$country]]);
 market_assert((bool)$rates===$supported,'Shipping rates: '.$country);
 $order=new WC_Order();$order->set_billing_country($country);$rejected=false;
 try{do_action('woocommerce_store_api_checkout_update_order_from_request',$order,new WP_REST_Request('POST','/wc/store/v1/checkout'));}catch(\Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e){$rejected=true;}
 market_assert($rejected!==$supported,'Store API: '.$country);
}
foreach(['US'=>'NY','GB'=>'','CA'=>'ON','AU'=>'NSW'] as $country=>$state){$zone=WC_Shipping_Zones::get_zone_matching_package(['destination'=>['country'=>$country,'state'=>$state,'postcode'=>'']]);market_assert($zone->get_id()>0,'No zone for '.$country);market_assert(count($zone->get_shipping_methods(true))>0,'No shipping for '.$country);}
market_assert(count((new WC_Shipping_Zone(0))->get_shipping_methods(true))===0,'Fallback shipping still enabled');
ob_start();include get_template_directory().'/market-switcher.php';$html=ob_get_clean();market_assert(strpos($html,'study_language')===false,'Language switch remains');market_assert(substr_count($html,'data-country')===4,'Wrong market menu');
WP_CLI::success('Four markets, English-only preferences, all other countries blocked in classic/Store API checkout and shipping; supported zones and selector verified. No order created.');
