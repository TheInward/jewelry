<?php
/** One-time merchant-authorized inventory reset; never run as a scheduled job. */
defined('ABSPATH')||exit;
$raw=$args[0]??'';
if(!preg_match('/^[1-9][0-9]*$/D',(string)$raw))throw new RuntimeException('Pass the intended positive integer stock quantity.');
$quantity=(int)$raw;$parents=[];$counts=['simple'=>0,'variation'=>0,'variable_parent'=>0];
$ids=get_posts(['post_type'=>['product','product_variation'],'post_status'=>['publish','private','draft','pending','future'],'numberposts'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC']);
if(($args[1]??'')!=='verify'){
foreach($ids as $id){
 $product=wc_get_product($id);if(!$product)throw new RuntimeException('Cannot load product '.$id);
 if($product->is_type('variable')){$parents[]=$id;continue;}
 if(!$product->is_type(['simple','variation']))throw new RuntimeException('Unsupported product type at '.$id);
 $product->set_manage_stock(true);$product->set_stock_quantity($quantity);$product->set_backorders('no');$product->set_stock_status('instock');$product->save();
 $counts[$product->is_type('variation')?'variation':'simple']++;
 if(array_sum($counts)%100===0){wp_cache_flush();if(function_exists('gc_collect_cycles'))gc_collect_cycles();}
 if(array_sum($counts)%500===0)WP_CLI::log('Updated '.array_sum($counts).' selling SKUs');
}
foreach($parents as $id){
 $product=wc_get_product($id);$product->set_manage_stock(false);$product->set_stock_quantity(null);$product->set_backorders('no');$product->set_stock_status('instock');$product->save();WC_Product_Variable::sync($id);wc_delete_product_transients($id);$counts['variable_parent']++;if($counts['variable_parent']%100===0){wp_cache_flush();if(function_exists('gc_collect_cycles'))gc_collect_cycles();}
}
}else{
 $parents=wc_get_products(['type'=>'variable','status'=>['publish','private','draft','pending','future'],'limit'=>-1,'return'=>'ids']);
 $variations=get_posts(['post_type'=>'product_variation','post_status'=>['publish','private','draft','pending','future'],'numberposts'=>-1,'fields'=>'ids']);
 $counts=['simple'=>count($ids)-count($parents)-count($variations),'variation'=>count($variations),'variable_parent'=>count($parents)];
}
wc_delete_product_transients();wp_cache_flush();
// Verify all rows directly, without rebuilding thousands of product objects.
global $wpdb;
$all=implode(',',array_map('absint',$ids));$selling=implode(',',array_map('absint',array_diff($ids,$parents)));
$bad=$wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_stock_status' LEFT JOIN {$wpdb->wc_product_meta_lookup} l ON l.product_id=p.ID WHERE p.ID IN ($all) AND (s.meta_value IS NULL OR s.meta_value!='instock' OR l.stock_status IS NULL OR l.stock_status!='instock')");
if($selling){$invalid=$wpdb->get_col($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_manage_stock' LEFT JOIN {$wpdb->postmeta} q ON q.post_id=p.ID AND q.meta_key='_stock' WHERE p.ID IN ($selling) AND (m.meta_value IS NULL OR m.meta_value!='yes' OR q.meta_value IS NULL OR CAST(q.meta_value AS DECIMAL(20,4))!=%d)",$quantity));$bad=array_merge($bad,$invalid);}
if($bad)throw new RuntimeException('Inventory verification failed: '.implode(',',$bad));
WP_CLI::success(wp_json_encode(['quantity_per_selling_sku'=>$quantity,'counts'=>$counts,'out_of_stock'=>0]));
