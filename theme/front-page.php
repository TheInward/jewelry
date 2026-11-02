<?php
defined('ABSPATH') || exit;
get_header();
?>
<main class="home-main">
  <a class="home-hero" href="<?php echo esc_url(study_collection_url('bestseller')); ?>" aria-label="<?php echo esc_attr(study_t('Shop Now')); ?> — <?php echo esc_attr(study_t('Bestsellers')); ?>">
    <picture>
      <source media="(min-width:1700px)" srcset="https://cdn.shopify.com/s/files/1/1782/4093/files/KV_wide.jpg?v=1793269788&amp;width=3000">
      <source media="(min-width:1025px)" srcset="https://cdn.shopify.com/s/files/1/1782/4093/files/KV_wide.jpg?v=1793269788&amp;width=2000">
      <source media="(min-width:768px) and (orientation:landscape)" srcset="https://cdn.shopify.com/s/files/1/1782/4093/files/KV_pc.jpg?v=1793269807&amp;width=1500">
      <img src="https://cdn.shopify.com/s/files/1/1782/4093/files/KV_1_mb.jpg?v=1793269798&amp;width=768" alt="The Holiday Dress Code — Jewelry required. Shop Now" fetchpriority="high">
    </picture>
  </a>
  <section class="home-categories" aria-label="<?php echo esc_attr(study_t('Shop by category')); ?>">
    <?php foreach (['necklaces','earrings','bracelet-anklet','rings'] as $category):
      $term = get_term_by('slug', $category, 'product_cat');
      $image = $term ? get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
      $image_url = $image ? wp_get_attachment_image_url($image, 'large') : '';
      if (!$image_url && $term) {
          $sample = wc_get_products(['category'=>[$category], 'limit'=>1, 'status'=>'publish']);
          $images = $sample ? $sample[0]->get_meta('_study_images') : [];
          $image_url = $images[0] ?? '';
      }
    ?>
      <a class="home-category" href="<?php echo esc_url(study_collection_url($category)); ?>">
        <?php if ($image_url): ?><img src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy"><?php endif; ?>
        <span><?php echo esc_html(study_t(study_collections()[$category])); ?></span>
      </a>
    <?php endforeach; ?>
  </section>
</main>
<?php get_footer();
