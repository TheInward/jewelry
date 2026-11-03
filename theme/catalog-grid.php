<?php
defined('ABSPATH') || exit;
global $study_catalog_rendered;
$study_catalog_rendered = true;
$slug = study_collection_slug();
$title = study_collections()[$slug] ?? single_term_title('', false);
?>
<main data-collection="<?php echo esc_attr($slug); ?>">
<?php if ($slug === 'necklaces'): ?>
<picture class="hero"><source media="(max-width:600px)" srcset="https://seleneith.com/wp-content/uploads/seleneith-media/c0fb48db1da58087135554d8.jpg"><img src="https://seleneith.com/wp-content/uploads/seleneith-media/90849fb1fd5a2871ea714806.jpg" alt="<?php echo esc_attr(study_t($title)); ?>"></picture>
<?php endif; ?>
<h1 class="collection-title"><?php echo esc_html(study_t($title)); ?></h1>
<section class="catalog"><div class="catalog-toolbar"><div class="sort-control"><span class="mobile-sort-caption"><?php echo esc_html(study_t('Sort by')); ?> ⌄</span><select id="sort" aria-label="<?php echo esc_attr(study_t('Sort by')); ?>">
<?php foreach (['featured'=>'Featured','relevant'=>'Most relevant','bestselling'=>'Best selling','old'=>'Date, old to new','price-up'=>'Price, low to high','price-down'=>'Price, high to low','title-up'=>'Alphabetically, A-Z','title-down'=>'Alphabetically, Z-A','new'=>'Date, new to old'] as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>"><?php echo esc_html(study_t($label)); ?></option><?php endforeach; ?>
</select></div><span class="result-count" id="result-count" aria-live="polite"></span><button id="filter-toggle" aria-expanded="false"><?php echo esc_html(study_t('Filter')); ?> ⌄</button></div>
<div class="catalog-layout"><aside class="filters" aria-label="<?php echo esc_attr(study_t('Filter')); ?>"><div class="mobile-filter-actions"><strong><?php echo esc_html(study_t('Filter')); ?></strong><button type="button" data-close-filters aria-label="Close filters">×</button></div><button id="clear-filters"><?php echo esc_html(study_t('Clear all')); ?></button><details open><summary><?php echo esc_html(study_t('Price')); ?></summary><input type="number" id="min-price" min="0" aria-label="<?php echo esc_attr(study_t('Minimum price')); ?>" placeholder="<?php echo esc_attr(study_t('From')); ?>"><input type="number" id="max-price" min="0" aria-label="<?php echo esc_attr(study_t('Maximum price')); ?>" placeholder="<?php echo esc_attr(study_t('To')); ?>"></details><div id="filter-groups"></div><button type="button" class="mobile-filter-actions mobile-filter-apply" data-close-filters><?php echo esc_html(study_t('Apply filters')); ?></button></aside>
<div><div class="grid" id="product-grid"></div><button class="load-more"><?php echo esc_html(study_t('Load more')); ?></button></div></div></section>
<section class="looking-for"><h2><?php echo esc_html(study_t('Looking for something else?')); ?></h2><?php foreach(['bestseller','gold-collection','silver-collection','sale'] as $suggestion): ?><a href="<?php echo esc_url(study_collection_url($suggestion)); ?>"><?php echo esc_html(study_t(study_collections()[$suggestion])); ?></a><?php endforeach; ?></section>
</main><script type="application/json" id="catalog-data"><?php echo wp_json_encode(study_catalog_data($slug), JSON_HEX_TAG|JSON_HEX_AMP); ?></script>
