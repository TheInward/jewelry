<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_study_signup', 'study_signup');
add_action('wp_ajax_nopriv_study_signup', 'study_signup');

function study_signup() {
    check_ajax_referer('study-cart', 'nonce');
    if (!class_exists('WC_Coupon')) wp_send_json_error(['message'=>'Coupons are unavailable.'], 503);
    try{$raw=study_post_text('email',254);}catch(InvalidArgumentException $e){wp_send_json_error(['message'=>'Please enter a valid email address.'],400);}
    $email = strtolower(trim($raw));
    if (!is_email($email)) wp_send_json_error(['message'=>'Please enter a valid email address.'], 400);

    try{
        $result=study_signup_with_locks($email,$_SERVER['REMOTE_ADDR']??'unknown',function()use($email){return study_signup_offer($email);});
    }catch(Throwable $e){$result=new WP_Error('signup_failed','Could not create your code. Please try again.',['status'=>500]);}
    // Send the response only after finally has released the database locks.
    if(is_wp_error($result))wp_send_json_error(['message'=>$result->get_error_message()],$result->get_error_data()['status']??500);
    wp_send_json_success($result);
}

function study_signup_lock_names($email,$ip){
    global $wpdb;
    return array_map(function($scope)use($wpdb){return 'study_signup_'.substr(hash_hmac('sha256',DB_NAME.'|'.$wpdb->prefix.'|'.$scope,wp_salt('auth')),0,48);},['ip:'.$ip,'email:'.$email]);
}

// Serialize both the per-IP quota and coupon creation for an email. MySQL
// releases named locks on disconnect, including after an interrupted request.
function study_signup_with_locks($email,$ip,$callback){
    global $wpdb;$held=[];
    try{
        foreach(study_signup_lock_names($email,$ip) as $name){
            $locked=$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$name));
            if((string)$locked!=='1')return new WP_Error('signup_busy','A signup is being processed. Please try again shortly.',['status'=>$locked===null?503:429]);
            $held[]=$name;
        }
        return $callback();
    }finally{
        foreach(array_reverse($held) as $name)$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$name));
    }
}

function study_signup_offer($email){
    $hash = hash_hmac('sha256', $email, wp_salt('auth'));
    $rate_key = 'study_signup_'.md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $attempts = (int) get_transient($rate_key);
    if ($attempts >= 5) return new WP_Error('signup_rate_limit','Please try again tomorrow.',['status'=>429]);
    set_transient($rate_key, $attempts + 1, DAY_IN_SECONDS);

    $existing = get_posts(['post_type'=>'shop_coupon','post_status'=>'publish','posts_per_page'=>1,
        'meta_key'=>'_study_signup_hash','meta_value'=>$hash,'fields'=>'ids']);
    if ($existing) {
        $coupon = new WC_Coupon($existing[0]);
        if (($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time()) || ($coupon->get_usage_limit() && $coupon->get_usage_count() >= $coupon->get_usage_limit())) {
            return new WP_Error('signup_used','This email has already received a signup code.',['status'=>409]);
        }
        return ['code'=>$coupon->get_code(),'emailed'=>false];
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
        return ['code'=>$coupon->get_code(),'emailed'=>(bool)$sent];
    } catch (Throwable $e) {
        return new WP_Error('signup_failed','Could not create your code. Please try again.',['status'=>500]);
    }
}
