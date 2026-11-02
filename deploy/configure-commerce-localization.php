<?php
defined('ABSPATH') || exit;
// Use WooCommerce's server-rendered forms, which share WCML's validated price,
// shipping and coupon hooks with the custom bag. Retain originals privately.
foreach (['cart'=>'[woocommerce_cart]','checkout'=>'[woocommerce_checkout]'] as $name=>$shortcode) {
    $id = wc_get_page_id($name); $page = get_post($id);
    if (!$page) throw new RuntimeException('Missing commerce page: '.$name);
    if (strpos($page->post_content,$shortcode)!==false) continue;
    if (strpos($page->post_content,'wp:woocommerce/'.$name)===false) throw new RuntimeException('Unexpected commerce page content; preserving '.$name);
    if (!metadata_exists('post',$id,'_study_original_commerce_blocks')) update_post_meta($id,'_study_original_commerce_blocks',$page->post_content);
    $result = wp_update_post(['ID'=>$id,'post_content'=>$shortcode],true);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
$settings = get_option('_wcml_settings',[]);
$settings['currency_switcher_product_visibility'] = 0;
update_option('_wcml_settings',$settings);
echo "Classic cart and checkout configured; header is the single currency selector.\n";
