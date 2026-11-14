<?php
defined('ABSPATH') || exit;

function seleneith_business_details() {
    return ['legal_name'=>'Shanghai Chushou Delu Trading Co., Ltd.', 'address'=>'No. 37 Xinggong Road, Zhongxing Town, Chongming District, Shanghai, China'];
}

function seleneith_shipping_summary() {
    $threshold = 59.0; $market = study_market(); $zone_index = ['US'=>0,'CA'=>2,'AU'=>3][$market] ?? 0;
    $ids = get_option('seleneith_shipping_zone_ids', []); $zone = new WC_Shipping_Zone($ids[$zone_index] ?? 0); $fee = ['US'=>9.90,'CA'=>12.90,'AU'=>15.90][$market] ?? 9.90;
    foreach ($zone->get_shipping_methods(true) as $method) {
        $settings = get_option('woocommerce_'.$method->id.'_'.$method->instance_id.'_settings', []);
        if ($method->id === 'flat_rate' && is_numeric($settings['cost'] ?? null)) $fee = (float)$settings['cost'];
        if ($method->id === 'free_shipping' && is_numeric($settings['min_amount'] ?? null)) $threshold = (float)$settings['min_amount'];
    }
    $currency = get_woocommerce_currency();
    $rate = $currency === 'USD' ? 1 : (float)(study_currency_options()[$currency]['rate'] ?? 0);
    if ($rate <= 0) { $currency = 'USD'; $rate = 1; }
    $format = fn($value) => html_entity_decode(wp_strip_all_tags(wc_price($value * $rate, ['currency'=>$currency])), ENT_QUOTES, 'UTF-8');
    return ['threshold'=>$format($threshold), 'fee'=>$format($fee), 'currency'=>$currency];
}
function seleneith_shipping_sentence() {
    $shipping = seleneith_shipping_summary();
    return sprintf('Free standard shipping from %s after discounts; %s below. Amounts in %s.', $shipping['threshold'], $shipping['fee'], $shipping['currency']);
}
function seleneith_store_assurances() {
    $shipping = seleneith_shipping_summary();
    ?><section class="store-assurances" aria-label="Shopping information">
      <a href="<?php echo esc_url(home_url('/pages/shipping-policy/')); ?>"><strong><?php echo esc_html('Free shipping from '.$shipping['threshold'].' '.$shipping['currency']); ?></strong><span>After discounts · See shipping details</span></a>
      <a href="<?php echo esc_url(home_url('/pages/return-policy/')); ?>"><strong>14-day return requests</strong><span>Conditions and return costs apply</span></a>
      <a href="<?php echo esc_url(home_url('/pages/faq/')); ?>"><strong>Pay with PayPal</strong><span>Review payment options at checkout</span></a>
    </section><?php
}
add_action('woocommerce_single_product_summary', function() {
    ?><div class="purchase-information" aria-label="Delivery and returns">
      <p><a href="<?php echo esc_url(home_url('/pages/shipping-policy/')); ?>"><?php echo esc_html(seleneith_shipping_sentence()); ?></a></p>
      <p>Ships from China. Estimated delivery: 12–25 business days from ordering. Import charges may apply.</p>
      <p><a href="<?php echo esc_url(home_url('/pages/return-policy/')); ?>">Request a return within 14 days of delivery. See conditions and costs.</a></p>
      <a class="purchase-help" href="<?php echo esc_url(home_url('/pages/contact/')); ?>">Questions about this piece? Contact us →</a>
    </div><?php
}, 34);
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('seleneith-customer-info', get_template_directory_uri().'/customer-info.css', ['study-editorial'], filemtime(__DIR__.'/customer-info.css'));
    wp_localize_script('study', 'seleneithCustomerInfo', [
        'shippingSummary'=>seleneith_shipping_sentence(),
        'shippingUrl'=>home_url('/pages/shipping-policy/')
    ]);
}, 30);
