<?php
defined('ABSPATH')||exit;
$business=seleneith_business_details();
$replacement='<section class="business-information"><h2>Business information</h2><p>Operated by '.esc_html($business['legal_name']).'</p><address>'.esc_html($business['address']).'</address><p>This is our business contact address. For returns, please submit a request and wait for the return instructions and appropriate return address.</p></section>';
$pages=get_posts(['post_type'=>'page','post_status'=>['publish','draft','private'],'posts_per_page'=>-1,'fields'=>'all']);
$updated=0;
foreach($pages as $page){
 if(strpos($page->post_content,'class="business-information"')===false)continue;
 $count=0;$inserted=false;
 $content=preg_replace_callback('~<section class="business-information">.*?</section>~s',function()use($replacement,&$inserted){if($inserted)return ''; $inserted=true;return $replacement;},$page->post_content,-1,$count);
 if($count<1)throw new RuntimeException('Could not safely replace business information on page '.$page->ID);
 $result=wp_update_post(wp_slash(['ID'=>$page->ID,'post_content'=>$content]),true);
 if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
 $updated++;
}
WP_CLI::success('Updated English business name and address on '.$updated.' customer information pages.');
