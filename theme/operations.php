<?php
defined('ABSPATH')||exit;
function seleneith_excluded_countries(){return ['IR','KP','SY','MM','VE','YE','AF'];}
// Keep customer replies on the confirmed support mailbox, with a same-domain sender.
add_filter('wp_mail_from',fn($from)=>'notifications@seleneith.com',20);
add_filter('wp_mail_from_name',fn($name)=>'Seleneith',20);
add_filter('wp_mail',function($args){
 $headers=$args['headers']??[];if(is_string($headers))$headers=preg_split('/\r?\n/',$headers,-1,PREG_SPLIT_NO_EMPTY);
 if(!preg_grep('/^reply-to:/i',$headers))$headers[]='Reply-To: Seleneith <seleneith@163.com>';
 $args['headers']=$headers;return $args;
});
add_filter('woocommerce_package_rates',function($rates,$package){return in_array($package['destination']['country']??'',seleneith_excluded_countries(),true)?[]:$rates;},200,2);
add_action('woocommerce_after_checkout_validation',function($data,$errors){
 $countries=[$data['billing_country']??''];if(!empty($data['ship_to_different_address']))$countries[]=$data['shipping_country']??'';
 if(array_intersect($countries,seleneith_excluded_countries()))$errors->add('seleneith_destination','We cannot accept orders for this destination. Please choose a supported country.');
},20,2);

add_action('wp_mail_failed',function($error){if(function_exists('wc_get_logger'))wc_get_logger()->error(sanitize_text_field($error->get_error_message()),['source'=>'seleneith-mail']);});
add_action('woocommerce_store_api_checkout_update_order_from_request',function($order){
 if(in_array($order->get_billing_country(),seleneith_excluded_countries(),true)||in_array($order->get_shipping_country(),seleneith_excluded_countries(),true))throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('seleneith_destination','We cannot accept orders for this destination.',400);
});
function seleneith_delivery_estimate(){
 echo '<tr class="seleneith-delivery-estimate"><th>'.esc_html(study_t('Estimated delivery')).'</th><td>'.esc_html(study_t('12–25 business days from order placement.')).'<br>'.esc_html(study_t('Import charges may apply.')).' <a href="'.esc_url(home_url('/pages/shipping-policy/')).'">'.esc_html(study_t('Shipping Policy')).'</a></td></tr>';
}
add_action('woocommerce_review_order_after_shipping','seleneith_delivery_estimate');
add_action('woocommerce_cart_totals_after_shipping','seleneith_delivery_estimate');
