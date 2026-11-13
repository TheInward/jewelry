<?php
defined('ABSPATH') || exit;
$parent=get_page_by_path('pages');
$parent_id=$parent?$parent->ID:wp_insert_post(['post_type'=>'page','post_name'=>'pages','post_title'=>'Information','post_status'=>'publish']);
$pages=require __DIR__.'/customer-content.php';
foreach($pages as $slug=>[$title,$content]){
 $existing=get_page_by_path('pages/'.$slug);
 $args=['post_type'=>'page','post_status'=>'publish','post_parent'=>$parent_id,'post_name'=>$slug,'post_title'=>$title,'post_content'=>$content];
 if($existing)$args['ID']=$existing->ID;
 $id=wp_insert_post($args,true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
 update_post_meta($id,'_wp_page_template','page-information.php');
 if($slug==='privacy-policy')update_option('wp_page_for_privacy_policy',$id);
}
flush_rewrite_rules();
WP_CLI::success('Created customer information pages.');

// Keep current merchant-approved delivery and return policies on fresh installs.
require __DIR__.'/update-seleneith-policies.php';
