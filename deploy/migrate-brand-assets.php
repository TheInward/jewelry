<?php
/** Apply a private migration manifest after uploading all replacement media and the new theme. */
defined('ABSPATH')||exit;
if(empty($args[0])||!is_readable($args[0]))throw new RuntimeException('Pass a private migration manifest.');
$config=json_decode(file_get_contents($args[0]),true,512,JSON_THROW_ON_ERROR);
$map=$config['urls'];$blocked=$config['blocked_hosts'];$link_hosts=$config['link_hosts'];$patterns=$config['legacy_patterns'];$omitted=array_flip($config['omitted_urls']);
function seleneith_media_key($url){$p=wp_parse_url(html_entity_decode($url));if(!$p)return $url;parse_str($p['query']??'',$query);return 'https://'.($p['host']??'').($p['path']??'').(isset($query['v'])?'?v='.rawurlencode($query['v']):'');}
$clean=function($value)use(&$clean,$map,$blocked,$link_hosts,$patterns){
 if(is_array($value)){foreach($value as $k=>$v)$value[$k]=$clean($v);return $value;}
 if(!is_string($value))return $value;
 $value=preg_replace_callback('~(?:https?:)?//[^\s<>"\x27]+~i',function($match)use($map,$blocked,$link_hosts){$url=$match[0];$key=seleneith_media_key($url);if(isset($map[$key]))return $map[$key];$host=wp_parse_url(str_starts_with($url,'//')?'https:'.$url:$url,PHP_URL_HOST);if(in_array($host,$link_hosts,true)){return home_url(str_replace('/products/','/product/',wp_parse_url($url,PHP_URL_PATH)?:'/'));}if(in_array($host,$blocked,true))throw new RuntimeException('Unmapped media URL; migration stopped.');return $url;},$value);
 foreach($patterns as $pattern)$value=preg_replace($pattern,'Seleneith',$value);
 return $value;
};
$count=0;$removed=0;
foreach(get_posts(['post_type'=>['product','product_variation','page','attachment','post'],'post_status'=>'any','numberposts'=>-1]) as $post){
 $change=['ID'=>$post->ID];foreach(['post_title','post_content','post_excerpt'] as $field){$next=$clean($post->$field);if($next!==$post->$field)$change[$field]=$next;}
 if(count($change)>1){$id=wp_update_post(wp_slash($change),true);if(is_wp_error($id))throw new RuntimeException($id->get_error_message());}
 $images=get_post_meta($post->ID,'_study_images',true);
 if(is_array($images)){$retained=[];foreach($images as $url){$key=seleneith_media_key($url);if(isset($omitted[$key])){$removed++;continue;}$retained[]=$clean($url);}if(!$retained)$retained=[home_url('/wp-content/themes/seleneith/assets/product-placeholder.svg')];update_post_meta($post->ID,'_study_images',array_values(array_unique($retained)));$count++;}
 foreach(['_study_translations','_wp_attachment_image_alt'] as $key){$value=get_post_meta($post->ID,$key,true);if($value)update_post_meta($post->ID,$key,$clean($value));}
 delete_post_meta($post->ID,'_study_source');
}
$user=get_user_by('id',$config['admin_id']);if(!$user||!in_array('administrator',$user->roles,true))throw new RuntimeException('Expected administrator missing.');
$occupied=get_user_by('login','seleneith_admin');if($occupied&&$occupied->ID!==$user->ID)throw new RuntimeException('Target login exists.');
global $wpdb;
$result=$wpdb->update($wpdb->users,['user_login'=>'seleneith_admin','user_nicename'=>'seleneith-admin','display_name'=>'Seleneith'],['ID'=>$user->ID]);if($result===false)throw new RuntimeException('Could not rename administrator.');
update_user_meta($user->ID,'nickname','Seleneith');clean_user_cache($user->ID);
$old=get_option('stylesheet');$mods=get_option('theme_mods_'.$old);switch_theme('seleneith');if($mods)update_option('theme_mods_seleneith',$mods);
if($old!=='seleneith')delete_option('theme_mods_'.$old);
delete_option('wcml_currency_switcher_template_objects');
update_option('current_theme','Seleneith');wp_clean_themes_cache();
foreach($wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'") as $option){foreach($patterns as $pattern)if(preg_match($pattern,$option->option_name.' '.$option->option_value)){delete_site_transient(substr($option->option_name,16));break;}}
WC_Cache_Helper::get_transient_version('product',true);wp_cache_flush();
WP_CLI::success('Migrated media for '.$count.' products/variations; omitted '.$removed.' branded gallery entries. Theme and administrator identity updated.');
