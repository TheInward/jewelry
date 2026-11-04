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
function study_catalog_data($scope = null, $ids = null, $context = 'catalog') {
    $args = ['post_type'=>'product', 'posts_per_page'=>-1, 'orderby'=>['menu_order'=>'ASC','ID'=>'ASC'], 'post_status'=>'publish'];
    if ($scope !== null) $args['tax_query'] = [['taxonomy'=>'product_cat','field'=>'slug','terms'=>$scope,'include_children'=>false]];
    if ($ids !== null) $args['post__in'] = $ids ?: [0];
    $query = new WP_Query($args);
    $out = [];
    foreach ($query->posts as $post) {
        $p = wc_get_product($post->ID);
        if (!$p || post_password_required($post) || ($context === 'search' ? !study_product_searchable($p) : !$p->is_visible())) continue;
        $out[] = ['id'=>$p->get_id(), 'source_id'=>(int)preg_replace('/^REF-/', '', $p->get_sku()), 'title'=>wp_specialchars_decode($p->get_name(), ENT_QUOTES), 'url'=>get_permalink($p->get_id()), 'price'=>(float)$p->get_price(), 'regular'=>(float)($p->is_type('variable')?$p->get_variation_regular_price('min',false):$p->get_regular_price()), 'images'=>$p->get_meta('_study_images')?:[], 'tags'=>study_product_tags($p),'colors'=>study_product_colors($p),'type'=>$p->get_type(),'created'=>(int)($p->get_meta('_study_source_created')?:($p->get_date_created()?$p->get_date_created()->getTimestamp():0)),'sales'=>(int)$p->get_total_sales(), 'available'=>$p->is_in_stock()];
    }
    return $out;
}
add_action('wp_ajax_study_products', 'study_products_endpoint');
add_action('wp_ajax_nopriv_study_products', 'study_products_endpoint');
function study_product_searchable($product){
    return $product->get_status()==='publish' && in_array($product->get_catalog_visibility(),['visible','search'],true)
        && ($product->is_in_stock() || get_option('woocommerce_hide_out_of_stock_items')!=='yes');
}
function study_search_product_ids($raw){
    $search=mb_strtolower(trim(mb_substr(sanitize_text_field($raw),0,100)));
    if(mb_strlen($search)<2)return [];
    // Read only titles and their translations; hydrate prices/variations for the
    // returned page, rather than every product on each search keystroke.
    global $wpdb;
    $rows=$wpdb->get_results($wpdb->prepare("SELECT p.ID,p.post_title,m.meta_value FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key=%s WHERE p.post_type='product' AND p.post_status='publish' AND p.post_password='' ORDER BY p.menu_order,p.ID",'_study_translations'));
    $ids=[];
    foreach($rows as $row){
        $translations=maybe_unserialize($row->meta_value??'');
        $title=is_array($translations)?($translations[study_language()]??$row->post_title):$row->post_title;
        $title=wp_specialchars_decode($title,ENT_QUOTES);
        if(mb_strpos(mb_strtolower($title),$search)===false)continue;
        $product=wc_get_product($row->ID);
        if($product&&study_product_searchable($product))$ids[]=(int)$row->ID;
    }
    return array_values(array_unique($ids));
}
function study_products_endpoint() {
    check_ajax_referer('study-cart','nonce');
    try{
        if (isset($_POST['ids'])) {
            $ids=study_product_ids_input(study_post_text('ids',1100));
            wp_send_json_success(study_catalog_data(null,$ids));
        }
        $ids=study_search_product_ids(study_post_text('search',400));
        wp_send_json_success(study_catalog_data(null,array_slice($ids,0,12),'search'));
    }catch(InvalidArgumentException $e){wp_send_json_error($e->getMessage(),400);}
}
// The full search page and drawer must use the same localized title matching.
add_action('pre_get_posts',function($query){
    if(is_admin()||!$query->is_main_query()||!$query->is_search())return;
    $search=$query->get('s');
    $ids=study_search_product_ids(is_string($search)?$search:'');
    $query->set('post_type','product');$query->set('post__in',$ids?:[0]);
    $query->set('orderby','post__in');$query->set('study_product_search',true);
},20);
add_filter('posts_search',function($sql,$query){return $query->get('study_product_search')?'':$sql;},20,2);
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
