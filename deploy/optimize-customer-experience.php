<?php
defined('ABSPATH')||exit;
$parent=get_page_by_path('pages');if(!$parent)throw new RuntimeException('Missing information parent.');
$pages=require __DIR__.'/customer-content.php';
foreach($pages as $slug=>[$title,$content]){
    $page=get_page_by_path('pages/'.$slug);
    if(!$page)throw new RuntimeException('Missing page '.$slug);
    $result=wp_update_post(wp_slash(['ID'=>$page->ID,'post_title'=>$title,'post_content'=>$content]),true);
    if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
}
foreach(['gift-card','physical-gift-card'] as $slug){
    $product=get_page_by_path($slug,OBJECT,'product');
    if(!$product||$product->post_status!=='publish')continue;
    $result=wp_update_post(['ID'=>$product->ID,'post_status'=>'draft'],true);
    if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
    wc_delete_product_transients($product->ID);
}
WP_CLI::success('Updated customer information; unverified gift cards moved to draft. Shipping, tax, payment and order settings unchanged.');
