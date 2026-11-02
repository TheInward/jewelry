<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_study_signup', 'study_signup');
add_action('wp_ajax_nopriv_study_signup', 'study_signup');

function study_signup() {
    check_ajax_referer('study-cart', 'nonce');
    if (!class_exists('WC_Coupon')) wp_send_json_error(['message'=>'Coupons are unavailable.'], 503);
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    if (!is_email($email)) wp_send_json_error(['message'=>'Please enter a valid email address.'], 400);

    $hash = hash_hmac('sha256', strtolower($email), wp_salt('auth'));
    $rate_key = 'study_signup_'.md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) wp_send_json_error(['message'=>'Please try again tomorrow.'], 429);
    set_transient($rate_key, $attempts + 1, DAY_IN_SECONDS);

    $existing = get_posts(['post_type'=>'shop_coupon','post_status'=>'publish','posts_per_page'=>1,
        'meta_key'=>'_study_signup_hash','meta_value'=>$hash,'fields'=>'ids']);
    if ($existing) {
        $coupon = new WC_Coupon($existing[0]);
        if ($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time()) {
            wp_send_json_error(['message'=>'This email has already received a signup code.'], 409);
        }
        wp_send_json_success(['code'=>$coupon->get_code(),'emailed'=>false]);
    }

    try {
        $coupon = new WC_Coupon();
        $coupon->set_code('WELCOME10-'.strtoupper(wp_generate_password(8, false, false)));
        $coupon->set_discount_type('percent');
        $coupon->set_amount(10);
        $coupon->set_usage_limit(1);
        $coupon->set_individual_use(true);
        $coupon->set_email_restrictions([$email]);
        $coupon->set_date_expires(time() + 30 * DAY_IN_SECONDS);
        $coupon->set_description('Seleneith welcome offer');
        $id = $coupon->save();
        if (!$id) throw new Exception('Could not create a coupon.');
        update_post_meta($id, '_study_signup_hash', $hash);
        $sent = wp_mail($email, 'Your Seleneith 10% discount code', "Welcome to Seleneith.\n\nYour code: {$coupon->get_code()}\n\nApply it at checkout within 30 days.");
        wp_send_json_success(['code'=>$coupon->get_code(),'emailed'=>(bool)$sent]);
    } catch (Throwable $e) {
        wp_send_json_error(['message'=>'Could not create your code. Please try again.'], 500);
    }
}
