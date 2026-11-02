<?php
defined('ABSPATH') || exit;
global $study_catalog_rendered;
$study_catalog_rendered = true;
$slug = study_collection_slug();
$title = study_collections()[$slug] ?? single_term_title('', false);
?>
<main data-collection="<?php echo esc_attr($slug); ?>">
<?php if ($slug === 'necklaces'): ?>
<picture class="hero"><source media="(max-width:600px)" srcset="https://cdn.shopify.com/s/files/1/1782/4093/files/necklace_mb.jpg?v=1763728439&width=1000"><img src="https://cdn.shopify.com/s/files/1/1782/4093/files/necklace_70d5fca5-efc4-456f-8a8f-a36d416bf4e4.jpg?v=1763725677&width=2200" alt="<?php echo esc_attr(study_t($title)); ?>"></picture>
<?php endif; ?>
<h1 class="collection-title"><?php echo esc_html(study_t($title)); ?></h1>
<section class="catalog"><div class="catalog-toolbar"><select id="sort" aria-label="<?php echo esc_attr(study_t('Sort by')); ?>">
<?php foreach (['featured'=>'Featured','price-up'=>'Price, low to high','price-down'=>'Price, high to low','title-up'=>'Alphabetically, A-Z','title-down'=>'Alphabetically, Z-A','new'=>'Date, new to old'] as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html(study_t($label)); ?></option><?php endforeach; ?>
</select><span class="result-count" id="result-count" aria-live="polite"></span><button id="filter-toggle" aria-expanded="false"><?php echo esc_html(study_t('Filter')); ?> ☷</button></div>
<div class="catalog-layout"><aside class="filters" aria-label="<?php echo esc_attr(study_t('Filter')); ?>"><button id="clear-filters"><?php echo esc_html(study_t('Clear all')); ?></button><details open><summary><?php echo esc_html(study_t('Price')); ?></summary><input type="number" id="min-price" min="0" aria-label="<?php echo esc_attr(study_t('Minimum price')); ?>" placeholder="<?php echo esc_attr(study_t('From')); ?>"><input type="number" id="max-price" min="0" aria-label="<?php echo esc_attr(study_t('Maximum price')); ?>" placeholder="<?php echo esc_attr(study_t('To')); ?>"></details><div id="filter-groups"></div></aside>
<div><div class="grid" id="product-grid"></div><button class="load-more"><?php echo esc_html(study_t('Load more')); ?></button></div></div></section>
</main><script type="application/json" id="catalog-data"><?php echo wp_json_encode(study_catalog_data($slug), JSON_HEX_TAG|JSON_HEX_AMP); ?></script>
