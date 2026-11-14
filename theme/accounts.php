<?php
defined('ABSPATH')||exit;
add_action('wp_enqueue_scripts',function(){
 if(!function_exists('is_account_page')||!is_account_page())return;
 wp_enqueue_style('study-account',get_template_directory_uri().'/account.css',['study'],filemtime(__DIR__.'/account.css'));
 wp_enqueue_script('study-account',get_template_directory_uri().'/account.js',[],filemtime(__DIR__.'/account.js'),true);
});
// This hook runs before WooCommerce sanitizes the email or creates a customer.
add_filter('woocommerce_process_registration_errors',function($errors,$username,$password,$email){
 if(!is_string($email)||strlen($email)>254||!is_email(trim($email)))$errors->add('email_input','Please enter a valid email address.');
 if(!is_string($username)||!is_string($password)||strlen($password)>4096)$errors->add('registration_input','Please enter valid registration details.');
 return study_account_redirect_errors($errors);
},10,4);
add_filter('woocommerce_process_login_errors',function($errors){return study_account_redirect_errors($errors);});
function study_account_redirect_errors($errors){
 foreach(['redirect','_wp_http_referer'] as $key)if(isset($_POST[$key])&&(!is_string($_POST[$key])||strlen($_POST[$key])>2048))$errors->add('account_redirect','Please reload the form and try again.');
 return $errors;
}
add_filter('woocommerce_registration_errors',function($errors,$username,$email){
 if(!isset($_POST['register']))return $errors;
 foreach(['first_name'=>'First name','last_name'=>'Last name'] as $key=>$label){
  $raw=$_POST[$key]??'';
  if(!is_string($raw)||strlen($raw)>200||trim(sanitize_text_field(wp_unslash($raw)))==='')$errors->add($key,$label.' is required (maximum 200 characters).');
 }
 $password=$_POST['password']??null;$confirmation=$_POST['password_confirmation']??null;
 if(!is_string($password)||strlen(wp_unslash($password))<8)$errors->add('password_length','Please use a password with at least 8 characters.');
 if(!is_string($confirmation)||$password!==$confirmation)$errors->add('password_mismatch','Passwords do not match.');
 if(get_privacy_policy_url()&&($_POST['agree_privacy']??'')!=='1')$errors->add('privacy_consent','Please agree to the Privacy Policy.');
 return $errors;
},10,3);
add_action('woocommerce_created_customer',function($id){
 foreach(['first_name','last_name'] as $key){if(isset($_POST[$key])&&is_string($_POST[$key])){$value=sanitize_text_field(wp_unslash($_POST[$key]));update_user_meta($id,$key,$value);update_user_meta($id,'billing_'.$key,$value);}}
});
add_filter('woocommerce_account_menu_items',function($items){unset($items['downloads']);return $items;});
