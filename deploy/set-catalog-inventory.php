<?php
/** One-time merchant-authorized inventory reset; never run as a scheduled job. */
defined('ABSPATH')||exit;
$raw=$args[0]??'';
if(!preg_match('/^[1-9][0-9]*$/D',(string)$raw))throw new RuntimeException('Pass the intended positive integer stock quantity.');
$quantity=(int)$raw;$parents=[];$counts=['simple'=>0,'variation'=>0,'variable_parent'=>0];
$ids=get_posts(['post_type'=>['product','product_variation'],'post_status'=>['publish','private','draft','pending','future'],'numberposts'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC']);
foreach($ids as $id){
 $product=wc_get_product($id);if(!$product)throw new RuntimeException('Cannot load product '.$id);
 if($product->is_type('variable')){$parents[]=$id;continue;}
 if(!$product->is_type(['simple','variation']))throw new RuntimeException('Unsupported product type at '.$id);
 $product->set_manage_stock(true);$product->set_stock_quantity($quantity);$product->set_backorders('no');$product->set_stock_status('instock');$product->save();
 $counts[$product->is_type('variation')?'variation':'simple']++;
 if(array_sum($counts)%500===0)WP_CLI::log('Updated '.array_sum($counts).' selling SKUs');
}
foreach($parents as $id){
 $product=wc_get_product($id);$product->set_manage_stock(false);$product->set_stock_quantity(null);$product->set_backorders('no');$product->set_stock_status('instock');$product->save();WC_Product_Variable::sync($id);wc_delete_product_transients($id);$counts['variable_parent']++;
}
wc_delete_product_transients();wp_cache_flush();
$bad=[];
foreach($ids as $id){$p=wc_get_product($id);if(!$p->is_in_stock()||(!$p->is_type('variable')&&(!$p->get_manage_stock()||(int)$p->get_stock_quantity()!==$quantity)))$bad[]=$id;}
if($bad)throw new RuntimeException('Inventory verification failed: '.implode(',',$bad));
WP_CLI::success(wp_json_encode(['quantity_per_selling_sku'=>$quantity,'counts'=>$counts,'out_of_stock'=>0]));
