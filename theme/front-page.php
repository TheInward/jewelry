<?php
defined('ABSPATH') || exit;
get_header();
?>
<main class="home-main">
  <a class="home-hero" href="<?php echo esc_url(study_collection_url('bestseller')); ?>" aria-label="<?php echo esc_attr(study_t('Shop Now')); ?> — <?php echo esc_attr(study_t('Bestsellers')); ?>">
    <picture>
      <source media="(min-width:1700px)" srcset="https://seleneith.com/wp-content/uploads/seleneith-media/ddf22cf1245ccd14e97db8ca.jpg">
      <source media="(min-width:1025px)" srcset="https://seleneith.com/wp-content/uploads/seleneith-media/ddf22cf1245ccd14e97db8ca.jpg">
      <source media="(min-width:768px) and (orientation:landscape)" srcset="https://seleneith.com/wp-content/uploads/seleneith-media/b0e9d5506bf4e738d4058fe5.jpg">
      <img src="https://seleneith.com/wp-content/uploads/seleneith-media/106065febc9afd8dfcda31e4.jpg" alt="The Holiday Dress Code — Jewelry required. Shop Now" fetchpriority="high">
    </picture>
  </a>
  <section class="mobile-category-section" aria-label="Shop by category"><div class="mobile-category-track">
  <?php foreach(['new-arrival'=>['New In','78167354b1acb661f361a7c6.png'],'bestseller'=>['Bestsellers','d2419590564f87b947610f9e.jpg'],'necklaces'=>['Necklaces','b0eecf35c4db4a113b458b51.jpg'],'bracelet-anklet'=>['Bracelets','87be7b3624a5fa4dc33f19b1.jpg'],'earrings'=>['Earrings','770dbd3d4ef315fb8005bbca.png'],'rings'=>['Rings','a07fce51780768cba27084b9.jpg']] as $slug=>[$label,$art]): ?><a class="mobile-category" href="<?php echo esc_url(study_collection_url($slug)); ?>"><img src="https://seleneith.com/wp-content/uploads/seleneith-media/<?php echo esc_attr($art); ?>" alt="" loading="lazy"><span><?php echo esc_html(study_t($label)); ?></span></a><?php endforeach; ?>
  </div><div class="mobile-category-progress" aria-hidden="true"></div></section>
  <section class="home-categories" aria-label="<?php echo esc_attr(study_t('Shop by category')); ?>">
    <?php foreach (['necklaces','earrings','bracelet-anklet','rings'] as $category):
      $term = get_term_by('slug', $category, 'product_cat');
      $image = $term ? get_term_meta($term->term_id, 'thumbnail_id', true) : 0;
       $art = ['necklaces'=>'b289fe7377f55b6cc1b24acd.webp','earrings'=>'47f229b8d650683997ce0c79.webp','bracelet-anklet'=>'a486cfcc02222e859892429e.png','rings'=>'707554ddf7596526bd1556a5.png'];
      $image_url = 'https://seleneith.com/wp-content/uploads/seleneith-media/'.$art[$category].'?width=1000';
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
  <?php study_mobile_editorial('gold-collection','The Gold Edit','bd8d9b9d93b88d368d7c0298.jpg','gold'); study_mobile_editorial('silver-collection','The Silver Side','105504cd19313e888dd65ee4.jpg','silver'); ?>
  <?php foreach (['new-arrival'=>'New In','bestseller'=>'Trending Now'] as $scope=>$heading): ?>
  <section class="home-products-section"><div class="section-heading"><h2><?php echo esc_html(study_t($heading)); ?></h2><a href="<?php echo esc_url(study_collection_url($scope)); ?>"><?php echo esc_html(study_t('Shop The Collection')); ?> ↗</a><div class="slider-controls"><button type="button" data-slide="-1" aria-label="Previous products">←</button><button type="button" data-slide="1" aria-label="Next products">→</button></div></div><div class="home-product-track"><?php foreach(study_home_products($scope) as $item) study_product_card($item); ?></div></section>
  <?php endforeach; ?>
  <?php study_mobile_editorial('jewelry-set','Jewelry Sets','930fd5adda2ff85ef0956d24.png','sets'); ?>
  <section class="reward-highlight"><h2>Seleneith</h2><p><?php echo esc_html(study_t('Jewelry for your everyday style')); ?></p><a class="outline-link" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php echo esc_html(study_t('Log In or Sign Up')); ?></a><a class="outline-link" href="<?php echo esc_url(home_url('/pages/track-my-order/')); ?>"><?php echo esc_html(study_t('Check Order Status')); ?></a></section>
  <section class="brand-values" aria-label="Explore Seleneith"><?php foreach(['about-us'=>'The Brand','jewelry-care'=>'Jewelry Care','shipping-return'=>'Shipping & Return','contact'=>'Contact Us'] as $slug=>$label): ?><a href="<?php echo esc_url(home_url('/pages/'.$slug.'/')); ?>"><?php echo esc_html(study_t($label)); ?></a><?php endforeach; ?></section>
</main>
<?php get_footer();
