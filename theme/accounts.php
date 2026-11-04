<?php
defined('ABSPATH')||exit;
add_action('wp_enqueue_scripts',function(){
 if(!function_exists('is_account_page')||!is_account_page())return;
 wp_enqueue_style('study-account',get_template_directory_uri().'/account.css',['study'],filemtime(__DIR__.'/account.css'));
 wp_enqueue_script('study-account',get_template_directory_uri().'/account.js',[],filemtime(__DIR__.'/account.js'),true);
});
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
