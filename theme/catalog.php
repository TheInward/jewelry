<?php
defined('ABSPATH') || exit;
function study_collections() {
    static $collections;
    if ($collections === null) $collections = json_decode(file_get_contents(WP_CONTENT_DIR.'/reference/collections.json'), true) ?: [];
    return $collections;
}
function study_collection_slug() {
    if (is_product_category()) return get_queried_object()->slug;
    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        if ($slug === 'collections' || isset(study_collections()[$slug])) return $slug;
    }
    return is_shop() ? 'all-products-collection' : 'necklaces';
}
function study_collection_url($slug) { if($slug==='about-us')return home_url('/pages/about-us/'); return home_url($slug === 'collections' ? '/collections/' : '/collections/'.sanitize_title($slug).'/'); }
function study_product_tags($p) {
    $tags=$p->get_meta('_study_tags')?:[];
    $regular=(float)$p->get_regular_price();$price=(float)$p->get_price();
    if($p->is_type('variable')){$prices=$p->get_variation_prices(false);foreach($prices['price'] as $key=>$value){$r=(float)($prices['regular_price'][$key]??0);if($r>(float)$value && $r>0){$tags[]='discount__'.round(100*(1-(float)$value/$r)).'-off';$tags[]='highlight__sale';}}}
    if(!$p->is_type('variable') && $regular>$price && $regular>0){$tags[]='discount__'.round(100*(1-$price/$regular)).'-off';$tags[]='highlight__sale';}
    if(in_array('celebrity',$tags,true)||in_array('celebrity pick',$tags,true))$tags[]='highlight__celeb-pick';
    return array_values(array_unique($tags));
}
function study_catalog_data($scope = null, $ids = null) {
    $args = ['post_type'=>'product', 'posts_per_page'=>-1, 'orderby'=>['menu_order'=>'ASC','ID'=>'ASC'], 'post_status'=>'publish'];
    if ($scope !== null) $args['tax_query'] = [['taxonomy'=>'product_cat','field'=>'slug','terms'=>$scope,'include_children'=>false]];
    if ($ids !== null) $args['post__in'] = $ids ?: [0];
    $query = new WP_Query($args);
    $out = [];
    foreach ($query->posts as $post) {
        $p = wc_get_product($post->ID);
        if (!$p || !$p->is_visible()) continue;
        $out[] = ['id'=>$p->get_id(), 'source_id'=>(int)preg_replace('/^REF-/', '', $p->get_sku()), 'title'=>wp_specialchars_decode($p->get_name(), ENT_QUOTES), 'url'=>get_permalink($p->get_id()), 'price'=>(float)$p->get_price(), 'regular'=>(float)($p->is_type('variable')?$p->get_variation_regular_price('min',false):$p->get_regular_price()), 'images'=>$p->get_meta('_study_images')?:[], 'tags'=>study_product_tags($p),'colors'=>study_product_colors($p),'type'=>$p->get_type(),'created'=>(int)($p->get_meta('_study_source_created')?:($p->get_date_created()?$p->get_date_created()->getTimestamp():0)),'sales'=>(int)$p->get_total_sales(), 'available'=>$p->is_in_stock()];
    }
    return $out;
}
add_action('wp_ajax_study_products', 'study_products_endpoint');
add_action('wp_ajax_nopriv_study_products', 'study_products_endpoint');
function study_products_endpoint() {
    check_ajax_referer('study-cart','nonce');
    if (isset($_POST['ids'])) {
        $ids = array_slice(array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_POST['ids']))))), 0, 100);
        wp_send_json_success(study_catalog_data(null, $ids));
    }
    $search = mb_strtolower(mb_substr(sanitize_text_field(wp_unslash($_POST['search'] ?? '')), 0, 100));
    if (mb_strlen($search) < 2) wp_send_json_success([]);
    $rows = array_filter(study_catalog_data(), function($p) use ($search) { return mb_strpos(mb_strtolower($p['title']), $search) !== false; });
    wp_send_json_success(array_slice(array_values($rows), 0, 12));
}
function study_navigation() {
    return [
        'BestSellers'=>['bestseller',[]], 'New In'=>['new-arrival',[]],
        'Shop By'=>['all-products-collection',['all-products-collection','gold-collection','silver-collection','sale','earth-stone-collection','waterproof-jewelry','jewelry-set','men-collection','other-cute-stuff','street-style','boho-baddie','floral-collection','celestial-motifs','starfish-collection','hearts-bows','9-to-5-collection']],
        'Necklaces'=>['necklaces',['necklaces','gold-necklace','silver-necklace','cord-necklace','layered-necklace','choker-necklace','lariat-necklace','pendant-necklaces-1','chain-necklace','statement-necklaces']],
        'Bracelets'=>['bracelet-anklet',['bracelet-anklet','bracelets','bangles','anklet','rings-and-bracelets']],
        'Rings'=>['rings',['rings','stackable-rings','statement-rings','gold-rings','silver-rings']],
        'Earrings'=>['earrings',['earrings','dangle-earrings','hoop-earrings','stud-earrings','statement-earrings','gold-earrings','silver-earrings-1']],
        'Collections'=>['collections',['sub-rosa','eternal-sweetness','nocturnal-glimmer','rustic-soul','by-the-beach-collection-1','sunfire-mirage','soft-rituals','vida-del-mar']],
        'About Us'=>['about-us',[]]
    ];
}
