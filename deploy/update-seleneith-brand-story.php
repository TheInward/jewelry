<?php
defined('ABSPATH')||exit;
$pages=require __DIR__.'/customer-content.php';
if(empty($pages['about-us']))throw new RuntimeException('Missing Seleneith brand story content.');
[$title,$content]=$pages['about-us'];
$business=seleneith_business_details();
$content.='<section class="business-information"><h2>Business information</h2><p>Operated by '.esc_html($business['legal_name']).'</p><address>'.esc_html($business['address']).'</address><p>This is our business contact address. For returns, please submit a request and wait for the return instructions and appropriate return address.</p></section>';
$page=get_page_by_path('pages/about-us');
if(!$page)throw new RuntimeException('Missing About Seleneith page.');
$result=wp_update_post(wp_slash(['ID'=>$page->ID,'post_title'=>$title,'post_content'=>$content]),true);
if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
WP_CLI::success('Seleneith brand story updated.');
