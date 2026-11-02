<?php
defined('ABSPATH') || exit;
$file=$args[0]??WP_CONTENT_DIR.'/reference/product-details.json';
$rows=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if(count($rows)<700)throw new RuntimeException('Incomplete detail data; refusing import.');
$updated=0;$variables=0;$variation_count=0;
foreach($rows as $row){
 $id=wc_get_product_id_by_sku('REF-'.$row['id']);if(!$id)continue;
 $options=$row['options'];$variants=$row['variants'];
 $is_variable=count($variants)>1 || (count($options)>0 && $options[0]['name']!=='Title');
 $p=$is_variable?new WC_Product_Variable($id):wc_get_product($id);
 $p->set_description(wp_kses_post($row['description']));
 $p->update_meta_data('_study_source_created',strtotime($row['created_at']));
 if(!$is_variable){$p->save();$updated++;continue;}
 $attributes=[];
 foreach($options as $index=>$opt){$attribute=new WC_Product_Attribute();$attribute->set_name($opt['name']);$attribute->set_options(array_map('strval',$opt['values']));$attribute->set_position($index);$attribute->set_visible(true);$attribute->set_variation(true);$attributes[]=$attribute;}
 $p->set_attributes($attributes);$p->save();$defaults=[];
 foreach($variants as $data){
  $sku='REFV-'.$data['id'];$vid=wc_get_product_id_by_sku($sku);
  $v=$vid?new WC_Product_Variation($vid):new WC_Product_Variation();
  $v->set_parent_id($id);$v->set_status('publish');$v->set_sku($sku);
  $selected=[];
  foreach($options as $index=>$opt)$selected[sanitize_title($opt['name'])]=(string)($data['options'][$index]??'');
  $v->set_attributes($selected);$v->set_manage_stock(false);$v->set_stock_status($data['available']?'instock':'outofstock');
  $price=(float)$data['price']/100;$regular=max($price,(float)($data['compare_at_price']??0)/100);
  $v->set_regular_price(wc_format_decimal($regular,2));$v->set_sale_price($regular>$price?wc_format_decimal($price,2):'');
  $v->update_meta_data('_study_images',[$data['image']]);$v->update_meta_data('_study_source_variant',$data['id']);$v->save();$variation_count++;
  if(!$defaults && $data['available'])$defaults=$selected;
 }
 $p->set_default_attributes($defaults);$p->save();WC_Product_Variable::sync($id);wc_delete_product_transients($id);$variables++;$updated++;
 if($updated%100===0)WP_CLI::log('Updated '.$updated.' products');
}
wc_delete_product_transients();wp_cache_flush();
WP_CLI::success(wp_json_encode(['products'=>$updated,'variable_products'=>$variables,'variations'=>$variation_count]));
