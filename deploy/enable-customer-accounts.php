<?php
if(PHP_SAPI!=='cli')exit;
$_SERVER['HTTP_HOST']='39.106.108.70:8080';$_SERVER['REQUEST_URI']='/jewelry/';
require '/srv/enroute-preview/jewelry/wp-load.php';
update_option('woocommerce_enable_myaccount_registration','yes');
update_option('woocommerce_registration_generate_username','yes');
update_option('woocommerce_registration_generate_password','no');
update_option('woocommerce_enable_signup_and_login_from_checkout','yes');
update_option('woocommerce_enable_guest_checkout','yes');
update_option('default_role','customer');
update_option('users_can_register',0);
$privacy=get_page_by_path('privacy-notice');
if(!$privacy){$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Privacy Notice — Development Preview','post_name'=>'privacy-notice','post_content'=>'<p>This development store uses account information to provide sign-in, order history and saved addresses. Payment processing and marketing email services are not connected.</p><p>Do not submit real payment details or sensitive personal information during development. Account and order information is stored on this store’s server.</p><p>This is a temporary development notice. The final business privacy policy will be published before commercial launch.</p>']);update_option('wp_page_for_privacy_policy',$id);}else{update_option('wp_page_for_privacy_policy',$privacy->ID);}
flush_rewrite_rules();
echo 'Customer login, email registration, account dashboard and checkout signup enabled.';
