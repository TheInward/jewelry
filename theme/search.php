<?php defined('ABSPATH') || exit;get_header(); ?>
<main class="content"><h1><?php echo esc_html(study_t('Search')); ?>: <?php echo esc_html(get_search_query()); ?></h1><form method="get" action="<?php echo esc_url(home_url('/')); ?>"><input type="hidden" name="post_type" value="product"><input type="search" name="s" class="searchbox" value="<?php echo esc_attr(get_search_query()); ?>" aria-label="<?php echo esc_attr(study_t('Search jewelry')); ?>"><button class="outline-link" type="submit"><?php echo esc_html(study_t('Search')); ?></button></form><div class="grid"><?php
$ids=[];while(have_posts()){the_post();if(get_post_type()==='product')$ids[]=get_the_ID();}
foreach(study_catalog_data(null,$ids) as $item)study_product_card($item);
if(!$ids)echo '<p>'.esc_html(study_t('No matching products.')).'</p>';
?></div><?php the_posts_pagination(); ?></main><?php get_footer(); ?>
