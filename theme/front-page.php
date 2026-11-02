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
       $art = ['necklaces'=>'pc_20261012-113631.webp?v=1791777162','earrings'=>'pc_20261012-113722.webp?v=1791777204','bracelet-anklet'=>'Rectangle_94.png?v=1790668051','rings'=>'Rectangle_98.png?v=1790668051'];
      $image_url = 'https://cdn.shopify.com/s/files/1/1782/4093/files/'.$art[$category].'&width=1000';
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
  <?php foreach (['new-arrival'=>'New Drops Every Thursday','bestseller'=>'Trending Now'] as $scope=>$heading): ?>
  <section class="home-products-section"><div class="section-heading"><h2><?php echo esc_html(study_t($heading)); ?></h2><a href="<?php echo esc_url(study_collection_url($scope)); ?>"><?php echo esc_html(study_t('Shop The Collection')); ?> ↗</a><div class="slider-controls"><button type="button" data-slide="-1" aria-label="Previous products">←</button><button type="button" data-slide="1" aria-label="Next products">→</button></div></div><div class="home-product-track"><?php foreach(study_home_products($scope) as $item) study_product_card($item); ?></div></section>
  <?php endforeach; ?>
  <section class="reward-highlight"><h2><?php echo esc_html(study_t('Points on every order, gifts on us.')); ?></h2><p><?php echo esc_html(study_t('Explore membership benefits and your account.')); ?></p><a class="outline-link" href="<?php echo esc_url(home_url('/pages/rewards/')); ?>"><?php echo esc_html(study_t('Log In or Sign Up')); ?></a><a class="outline-link" href="<?php echo esc_url(home_url('/pages/track-my-order/')); ?>"><?php echo esc_html(study_t('Check Order Status')); ?></a><div class="reward-benefits"><?php foreach(['referral-earn.svg'=>'Earn Points','referral-jewelry.svg'=>'Redeem Free Jewelry','referral-birthday.svg'=>'Exclusive Birthday Gift'] as $icon=>$label): ?><div><img src="https://cdn.shopify.com/s/files/1/1782/4093/t/650/assets/<?php echo esc_attr($icon); ?>" alt="" loading="lazy"><p><?php echo esc_html(study_t($label)); ?></p></div><?php endforeach; ?></div></section>
  <section class="brand-values" aria-label="Brand values"><?php foreach(['Fair Pricing','Ethically Made','30-Day Return','100-Day Warranty'] as $label): ?><a href="<?php echo esc_url(home_url('/pages/shipping-return/')); ?>"><?php echo esc_html(study_t($label)); ?></a><?php endforeach; ?></section>
  <section class="press-section"><h2><?php echo esc_html(study_t('As seen on')); ?></h2><div><?php foreach(['elle.png'=>'ELLE','wwd.png'=>'WWD','nylon-logo.jpg'=>'NYLON','the-cut.png'=>'The Cut','instyle.png'=>'InStyle'] as $file=>$label): ?><img src="https://cdn.shopify.com/s/files/1/1782/4093/files/<?php echo esc_attr($file); ?>?width=300" alt="<?php echo esc_attr($label); ?>" loading="lazy"><?php endforeach; ?></div></section>
</main>
<?php get_footer();
