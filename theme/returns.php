<?php
defined('ABSPATH') || exit;

function seleneith_return_reasons() {
    return ['change_of_mind'=>'Changed my mind / unsuitable', 'damaged'=>'Arrived damaged or defective', 'wrong_item'=>'Received the wrong item', 'other'=>'Other'];
}
function seleneith_return_form_url() {
    return defined('SELENEITH_RETURN_PORTAL') ? 'https://returns.seleneith.com/' : home_url('/pages/start-a-return/');
}
function seleneith_return_public_url() {
    return 'https://returns.seleneith.com/';
}
function seleneith_return_form_action() {
    return defined('SELENEITH_RETURN_PORTAL') ? seleneith_return_form_url() : admin_url('admin-post.php');
}
add_filter('allowed_redirect_hosts', function($hosts) {
    $hosts[] = 'returns.seleneith.com';
    return array_values(array_unique($hosts));
});
add_action('init', function() {
    $capabilities = array_fill_keys(['edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts'], 'manage_woocommerce');
    $capabilities['create_posts'] = 'do_not_allow';
    register_post_type('seleneith_return', [
        'labels'=>['name'=>'Return Requests','singular_name'=>'Return Request','menu_name'=>'Return Requests'],
        'public'=>false,'publicly_queryable'=>false,'exclude_from_search'=>true,'show_ui'=>true,
        'show_in_menu'=>'woocommerce','show_in_rest'=>false,'rewrite'=>false,'query_var'=>false,
        'supports'=>['title'],'capabilities'=>$capabilities,'map_meta_cap'=>false
    ]);
});
function seleneith_return_fields($input) {
    $read = function($key, $limit) use ($input) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || strlen($value) > $limit) throw new InvalidArgumentException('Please check the form fields and their maximum lengths.');
        return trim(wp_unslash($value));
    };
    if ($read('website', 200) !== '') throw new InvalidArgumentException('We could not submit this request. Please contact customer service.');
    $email = strtolower($read('email', 254));
    if (!is_email($email)) throw new InvalidArgumentException('Please enter a valid contact email.');
    $order = sanitize_text_field($read('order_number', 80));
    $items = sanitize_textarea_field($read('items', 2000));
    $reason = $read('reason', 40);
    $details = sanitize_textarea_field($read('details', 4000));
    if ($order === '' || $items === '') throw new InvalidArgumentException('Please enter your order number and the items you wish to return.');
    if (!isset(seleneith_return_reasons()[$reason])) throw new InvalidArgumentException('Please choose a return reason.');
    if ($reason === 'other' && $details === '') throw new InvalidArgumentException('Please describe the reason for your request.');
    if (($input['acknowledge'] ?? '') !== '1') throw new InvalidArgumentException('Please read and acknowledge the privacy notice and return instructions.');
    return ['email'=>$email,'order_number'=>$order,'items'=>$items,'reason'=>$reason,'details'=>$details];
}
function seleneith_return_rate_keys($email, $ip) {
    return array_map(fn($scope)=>'seleneith_return_'.substr(hash_hmac('sha256', $scope, wp_salt('auth')), 0, 40), ['ip:'.$ip, 'email:'.$email]);
}
function seleneith_return_with_limits($email, $ip, $callback) {
    global $wpdb;
    $keys = seleneith_return_rate_keys($email, $ip); $held = [];
    try {
        foreach ($keys as $key) {
            $lock = 'sr_'.substr(hash('sha256', DB_NAME.'|'.$wpdb->prefix.'|'.$key), 0, 50);
            if ((string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock)) !== '1') return new WP_Error('return_busy', 'A request is being processed. Please wait a moment and try again.', ['status'=>429]);
            $held[] = $lock;
        }
        return $callback($keys);
    } finally {
        foreach (array_reverse($held) as $lock) $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
    }
}
function seleneith_return_create($fields, $ip) {
    return seleneith_return_with_limits($fields['email'], $ip, function($keys) use ($fields) {
        $fingerprint = hash_hmac('sha256', wp_json_encode($fields), wp_salt('nonce'));
        $existing = get_posts(['post_type'=>'seleneith_return','post_status'=>'private','numberposts'=>1,'fields'=>'ids','meta_key'=>'_return_fingerprint','meta_value'=>$fingerprint]);
        if ($existing && get_post_time('U',true,$existing[0]) > time()-DAY_IN_SECONDS) return ['reference'=>get_post_field('post_title',$existing[0],'raw')];
        foreach ($keys as $i=>$key) {
            if ((int)get_transient($key) >= ($i === 0 ? 5 : 3)) return new WP_Error('return_limit', 'Too many requests. Please try again later or email customer service.', ['status'=>429]);
        }
        $reference = 'SR-'.strtoupper(bin2hex(random_bytes(6)));
        $meta = ['_return_fingerprint'=>$fingerprint, '_return_status'=>'new'];
        foreach ($fields as $key=>$value) $meta['_return_'.$key] = $value;
        $id = wp_insert_post(wp_slash(['post_type'=>'seleneith_return','post_status'=>'private','post_title'=>$reference,'post_content'=>'','meta_input'=>$meta]), true);
        if (!$id || is_wp_error($id)) return new WP_Error('return_save', 'We could not save your request. Please try again or email customer service.', ['status'=>503]);
        foreach ($keys as $i=>$key) set_transient($key, (int)get_transient($key) + 1, $i === 0 ? 15 * MINUTE_IN_SECONDS : DAY_IN_SECONDS);
        return ['reference'=>$reference];
    });
}
function seleneith_return_endpoint() {
    $result = null;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $result = new WP_Error('return_method','Please submit the return form.', ['status'=>405]);
    $nonce = $_POST['seleneith_return_nonce'] ?? '';
    if (!$result && (!is_string($nonce) || !wp_verify_nonce($nonce,'seleneith-return-request'))) $result = new WP_Error('return_nonce','Your form has expired. Reload the page and try again.', ['status'=>403]);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed_origins = [strtolower(untrailingslashit(home_url())), 'https://returns.seleneith.com'];
    if (!$result && $origin !== '' && !in_array(strtolower(untrailingslashit($origin)), $allowed_origins, true)) $result = new WP_Error('return_origin','Please submit this form from our website.', ['status'=>403]);
    if (!$result) {
        try { $result = seleneith_return_create(seleneith_return_fields($_POST), $_SERVER['REMOTE_ADDR'] ?? 'unknown'); }
        catch (InvalidArgumentException $e) { $result = new WP_Error('return_fields',$e->getMessage(), ['status'=>400]); }
        catch (Throwable $e) { $result = new WP_Error('return_error','We could not save your request. Please try again or email customer service.', ['status'=>503]); }
    }
    if (is_wp_error($result)) {
        $status = $result->get_error_data()['status'] ?? 400;
        if (wp_doing_ajax()) wp_send_json_error(['message'=>$result->get_error_message()], $status);
        wp_die(esc_html($result->get_error_message()).' <a href="'.esc_url(seleneith_return_form_url()).'">Return to the form</a>', 'Return request', ['response'=>$status]);
    }
    $result['message'] = 'Your request has been received. We will review your order and reply by email within 48 hours. Please wait for return instructions before sending an item.';
    if (wp_doing_ajax()) wp_send_json_success($result);
    wp_safe_redirect(add_query_arg(['return_request'=>'received','reference'=>$result['reference'],'receipt'=>wp_hash('return:'.$result['reference'])],seleneith_return_form_url()));exit;
}
foreach (['wp_ajax_seleneith_return_request','wp_ajax_nopriv_seleneith_return_request','admin_post_seleneith_return_request','admin_post_nopriv_seleneith_return_request'] as $hook) add_action($hook, 'seleneith_return_endpoint');
add_shortcode('seleneith_return_form', function() {
    $reference = $_GET['reference'] ?? '';
    $receipt = $_GET['receipt'] ?? '';
    $received = ($_GET['return_request'] ?? '') === 'received' && is_string($reference) && preg_match('/^SR-[A-F0-9]{12}$/', $reference) && is_string($receipt) && hash_equals(wp_hash('return:'.$reference), $receipt);
    ob_start(); ?>
    <section class="returns-portal" aria-label="Return request">
      <div class="returns-steps" aria-label="How returns work"><span><b>1</b> Send your request</span><span><b>2</b> We review within 48 hours</span><span><b>3</b> Receive return instructions</span></div>
      <div class="returns-layout"><div>
      <div id="return-result" class="return-result" role="status" tabindex="-1" <?php if(!$received)echo 'hidden'; ?>><h2>Request received</h2><p>Your reference: <strong id="return-reference"><?php if($received)echo esc_html($reference); ?></strong></p><p>We will review your order and reply by email within 48 hours. Keep this reference for any follow-up. Please wait for instructions before sending an item.</p></div>
      <form id="return-request-form" action="<?php echo esc_url(seleneith_return_form_action()); ?>" method="post" <?php if($received)echo 'hidden'; ?>>
        <input type="hidden" name="action" value="seleneith_return_request"><?php wp_nonce_field('seleneith-return-request','seleneith_return_nonce'); ?>
        <h2>Tell us about your order</h2><p class="return-required">All fields are required unless marked optional.</p>
        <label for="return-email">Contact email<input id="return-email" name="email" type="email" autocomplete="email" maxlength="254" required aria-describedby="return-email-help"></label><small id="return-email-help">Use your order email if possible. We will reply to this address.</small>
        <label for="return-order">Order number<input id="return-order" name="order_number" type="text" maxlength="80" required autocomplete="off" aria-describedby="return-order-help"></label><small id="return-order-help">You can find this in your order confirmation email.</small>
        <label for="return-items">Products to return<textarea id="return-items" name="items" maxlength="2000" rows="4" required aria-describedby="return-items-help"></textarea></label><small id="return-items-help">Include each product ID or SKU, selected options and quantity. If you cannot find the ID, include the product name.</small>
        <label for="return-reason">Reason<select id="return-reason" name="reason" required><option value="">Select a reason</option><?php foreach(seleneith_return_reasons() as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></label>
        <label for="return-details">Additional details <span>(optional unless you selected “Other”)</span><textarea id="return-details" name="details" maxlength="4000" rows="4"></textarea></label>
        <div class="return-honeypot" aria-hidden="true"><label>Website<input name="website" type="text" tabindex="-1" autocomplete="off"></label></div>
        <label class="return-acknowledge"><input type="checkbox" name="acknowledge" value="1" required><span>I have read the <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacy Policy</a> and understand that I should wait for return instructions before sending an item.</span></label>
        <p id="return-form-status" role="status" aria-live="polite" tabindex="-1"></p><button type="submit" class="return-submit">Submit return request</button>
      </form></div><aside class="returns-help"><h2>Before you send a request</h2><p>For a voluntary return, contact us within 14 days of delivery. Conditions apply, and the buyer pays return shipping for a non-defective return.</p><p>If your item is damaged, defective or incorrect, tell us what happened so we can help. Submitting a request does not automatically approve a refund.</p><p>After reviewing your request, we will email you the next steps and the appropriate return address. Do not send items to a business or dispatch address without instructions.</p><a href="<?php echo esc_url(home_url('/pages/return-policy/')); ?>">Read the full Return Policy →</a><h3>Need help?</h3><a href="mailto:seleneith@163.com">seleneith@163.com</a></aside></div>
    </section><?php return ob_get_clean();
});
add_action('wp_enqueue_scripts', function() {
    if (!is_page('start-a-return')) return;
    wp_enqueue_style('seleneith-returns',get_template_directory_uri().'/returns.css',['seleneith-customer-info'],filemtime(__DIR__.'/returns.css'));
    wp_enqueue_script('seleneith-returns',get_template_directory_uri().'/returns.js',[],filemtime(__DIR__.'/returns.js'),true);
    wp_localize_script('seleneith-returns','seleneithReturns',['ajax'=>defined('SELENEITH_RETURN_PORTAL')?seleneith_return_form_url():admin_url('admin-ajax.php')]);
}, 40);
function seleneith_return_statuses() { return ['new'=>'待核查','reviewing'=>'处理中','responded'=>'已回复客户','closed'=>'已关闭']; }
add_filter('manage_seleneith_return_posts_columns', function($columns) { return ['cb'=>$columns['cb'],'title'=>'申请编号','return_order'=>'订单号','return_email'=>'联系邮箱','return_status'=>'处理状态','return_due'=>'回复截止（上海时间）','date'=>'收到时间']; });
add_action('manage_seleneith_return_posts_custom_column', function($column,$id) {
    if(!current_user_can('manage_woocommerce'))return;
    if($column==='return_order')echo esc_html(get_post_meta($id,'_return_order_number',true));
    if($column==='return_email')echo esc_html(get_post_meta($id,'_return_email',true));
    if($column==='return_status')echo esc_html(seleneith_return_statuses()[get_post_meta($id,'_return_status',true)]??'New');
    if($column==='return_due')echo esc_html(wp_date('Y-m-d H:i', get_post_time('U',true,$id)+48*HOUR_IN_SECONDS, new DateTimeZone('Asia/Shanghai')));
},10,2);
add_action('add_meta_boxes_seleneith_return', function() {
    add_meta_box('seleneith-return-details','顾客提交的信息',function($post) {
        foreach(['email'=>'Contact email','order_number'=>'Order number','items'=>'Requested items','reason'=>'Reason','details'=>'Details'] as $key=>$label){$value=get_post_meta($post->ID,'_return_'.$key,true);if($key==='reason')$value=seleneith_return_reasons()[$value]??$value;echo '<p><strong>'.esc_html($label).'</strong><br>'.nl2br(esc_html($value)).'</p>';}
        echo '<p><strong>Manual verification required.</strong> Review the order before providing return instructions or processing any refund. This request has not changed the order or payment.</p>';
    });
    add_meta_box('seleneith-return-review','人工审核与跟进',function($post) {
        wp_nonce_field('seleneith-return-review','seleneith_return_review_nonce');
        echo '<p>请在48小时内通过邮箱回复顾客；实际回复后将状态改为“已回复客户”。此页面不会自动发送邮件或退款。</p><label for="seleneith-return-status">处理状态</label><select id="seleneith-return-status" name="seleneith_return_status">';
        foreach(seleneith_return_statuses() as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected(get_post_meta($post->ID,'_return_status',true),$value,false).'>'.esc_html($label).'</option>';
        echo '</select><p><label for="seleneith-return-notes">内部备注（不发送给顾客）</label></p><textarea id="seleneith-return-notes" name="seleneith_return_notes" rows="8" maxlength="10000" style="width:100%">'.esc_textarea(get_post_meta($post->ID,'_return_notes',true)).'</textarea>';
    });
});
add_action('save_post_seleneith_return',function($id) {
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;
    if(!current_user_can('manage_woocommerce')||!current_user_can('edit_post',$id))return;
    $nonce=$_POST['seleneith_return_review_nonce']??'';
    if(!is_string($nonce)||!wp_verify_nonce($nonce,'seleneith-return-review'))return;
    $status=$_POST['seleneith_return_status']??'';
    if(is_string($status)&&isset(seleneith_return_statuses()[$status]))update_post_meta($id,'_return_status',$status);
    $notes=$_POST['seleneith_return_notes']??'';
    if(is_string($notes)&&strlen($notes)<=10000)update_post_meta($id,'_return_notes',sanitize_textarea_field(wp_unslash($notes)));
});
add_action('wp_dashboard_setup',function(){
    if(!current_user_can('manage_woocommerce'))return;
    wp_add_dashboard_widget('seleneith-return-overview','退货申请 · 48小时回复',function(){
        $open=['post_type'=>'seleneith_return','post_status'=>'private','posts_per_page'=>1,'fields'=>'ids','meta_query'=>[['key'=>'_return_status','value'=>['new','reviewing'],'compare'=>'IN']]];
        $query=new WP_Query($open);$count=$query->found_posts;
        $open['date_query']=[['column'=>'post_date_gmt','before'=>gmdate('Y-m-d H:i:s',time()-48*HOUR_IN_SECONDS)]];
        $overdue=new WP_Query($open);
        echo '<p>待核查/处理中：<strong>'.(int)$count.'</strong> · 已超过48小时：<strong>'.(int)$overdue->found_posts.'</strong></p><p><a href="'.esc_url(admin_url('edit.php?post_type=seleneith_return')).'">查看退货申请 →</a></p><p>请人工核对订单、通过邮箱回复，再更新申请状态。顾客提交申请不会自动发送邮件、批准退货或退款。</p>';
    });
});
