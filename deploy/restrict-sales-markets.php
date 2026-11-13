<?php
defined('ABSPATH')||exit;
require __DIR__.'/configure-seleneith-shipping.php';
$settings=get_option('_wcml_settings',[]);
$settings['currency_options']=array_intersect_key($settings['currency_options']??[],array_flip(['USD','GBP','CAD','AUD']));
$settings['currencies_order']=array_keys($settings['currency_options']);update_option('_wcml_settings',$settings);
$source=file_get_contents(__DIR__.'/update-seleneith-policies.php');
preg_match('~<table>.*?</table>~s',$source,$table);
foreach(['shipping-policy','shipping-return'] as $slug){
 $page=get_page_by_path('pages/'.$slug);if(!$page)throw new RuntimeException('Missing page '.$slug);
 $content=preg_replace('~<table>.*?</table>~s',$table[0],$page->post_content,1);
 $content=str_replace('We do not accept orders for Iran, North Korea, Syria, Myanmar, Venezuela, Yemen or Afghanistan.','We currently accept orders and ship only to the United States, United Kingdom, Canada and Australia. Other destinations are not supported.',$content);
 $result=wp_update_post(wp_slash(['ID'=>$page->ID,'post_content'=>$content]),true);if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
}
$faq=get_page_by_path('pages/faq');
if($faq){$content=preg_replace('~<details><summary>How can I change my language or currency\?</summary>.*?</details>~s','<details><summary>Which countries and currencies are supported?</summary><p>We sell and ship to the United States, United Kingdom, Canada and Australia. The store is available in English. Use the country selector to choose USD, GBP, CAD or AUD and check the currency at checkout.</p></details>',$faq->post_content);wp_update_post(wp_slash(['ID'=>$faq->ID,'post_content'=>$content]));}
WP_CLI::success('Shipping pages and FAQ updated for four English-language markets.');
