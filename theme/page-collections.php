<?php
get_header();
$featured = ['sub-rosa','eternal-sweetness','nocturnal-glimmer','sunfire-mirage','soft-rituals','vida-del-mar','necklaces','bracelet-anklet','rings','earrings'];
?>
<main class="catalog"><h1 class="collection-title"><?php echo esc_html(study_t('Collections')); ?></h1><div class="grid">
<?php foreach ($featured as $slug):
    $term = get_term_by('slug',$slug,'product_cat');
    if (!$term || !$term->count) continue;
    $products = wc_get_products(['category'=>[$slug],'status'=>'publish','limit'=>1]);
    $images = $products ? $products[0]->get_meta('_study_images') : [];
?>
<article class="product-card"><a class="product-image" href="<?php echo esc_url(study_collection_url($slug)); ?>"><?php if ($images): ?><img loading="lazy" src="<?php echo esc_url($images[0]); ?>" alt="<?php echo esc_attr(study_t(study_collections()[$slug])); ?>"><?php endif; ?></a><a class="product-name" href="<?php echo esc_url(study_collection_url($slug)); ?>"><?php echo esc_html(study_t(study_collections()[$slug])); ?></a><p><?php echo esc_html($term->count.' '.study_t('products')); ?></p></article>
<?php endforeach; ?></div></main>
<?php get_footer(); ?>
