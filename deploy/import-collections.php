<?php
/** Run with wp eval-file after placing reference/catalog.json in wp-content. */
defined('ABSPATH') || exit;
if (!class_exists('WC_Product_Simple')) throw new RuntimeException('WooCommerce required.');
$manifest = json_decode(file_get_contents(WP_CONTENT_DIR.'/reference/collections.json'), true, 512, JSON_THROW_ON_ERROR);
$rows = json_decode(file_get_contents(WP_CONTENT_DIR.'/reference/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($rows)<175) throw new RuntimeException('Incomplete catalog; aborting.');
$parent = get_page_by_path('collections');
if (!$parent) throw new RuntimeException('Collections parent page is missing.');
$terms = []; $counts = []; $created = 0; $updated = 0;
foreach ($manifest as $slug=>$title) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) {
        $result = wp_insert_term($title, 'product_cat', ['slug'=>$slug]);
        if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
        $terms[$slug] = (int)$result['term_id'];
    } else $terms[$slug] = (int)$term->term_id;
    $page = get_page_by_path('collections/'.$slug);
    $id = $page ? $page->ID : wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_parent'=>$parent->ID],true);
    if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
    update_post_meta($id,'_wp_page_template','page-collection.php');
    $counts[$slug] = 0;
}
foreach ($rows as $index=>$row) {
    $id = wc_get_product_id_by_sku('REF-'.$row['source_id']);
    if (!$id) { $existing = get_page_by_path($row['handle'], OBJECT, 'product'); $id = $existing ? $existing->ID : 0; }
    $product = $id ? wc_get_product($id) : new WC_Product_Simple();
    if (!$id) {
        $product->set_name($row['title']); $product->set_slug($row['handle']);
        $product->set_status('publish'); $product->set_sku('REF-'.$row['source_id']);
        $product->set_regular_price($row['regular'] ?: $row['price']);
        if ((float)$row['regular']>(float)$row['price']) $product->set_sale_price($row['price']);
        $product->set_stock_status($row['available']?'instock':'outofstock');
        $product->set_menu_order($index); $created++;
    } else $updated++;
    $category_ids = array_values(array_diff($product->get_category_ids(), array_values($terms)));
    foreach ($row['collections'] as $slug) { $category_ids[] = $terms[$slug]; $counts[$slug]++; }
    $product->set_category_ids(array_values(array_unique($category_ids)));
    $product->update_meta_data('_study_images',$row['images']);
    $product->update_meta_data('_study_tags',$row['tags']);
    $product->update_meta_data('_study_translations',$row['translations']);
    $product->update_meta_data('_study_source','https://enroutejewelry.com/products/'.$row['handle']);
    $product->save();
}
wc_delete_product_transients(); flush_rewrite_rules();
echo wp_json_encode(['created'=>$created,'updated'=>$updated,'collections'=>$counts],JSON_PRETTY_PRINT).PHP_EOL;
