<?php if(!is_user_logged_in()): ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class('account-entry'); ?>><?php wp_body_open(); ?>
<a class="account-back" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Back to shop">←</a>
<div class="account-markets"><?php get_template_part('market-switcher'); ?></div>
<main class="account-entry-layout"><div class="account-entry-image"><img src="https://cdn.shopify.com/s/files/1/1782/4093/files/Login_20260921-095710.jpg?v=1789955996&width=1440" alt="Jewelry styling" fetchpriority="high"></div><div class="account-entry-form woocommerce account-shell"><img class="account-logo" src="https://cdn.shopify.com/s/files/1/1782/4093/t/650/assets/logo-new.svg?v=80110147922362024401770694563" width="160" height="32" alt="en route jewelry"><?php echo do_shortcode('[woocommerce_my_account]'); ?></div></main>
<?php wp_footer(); ?></body></html>
<?php else: get_header(); ?>
<main class="content woocommerce account-shell">
<?php echo do_shortcode('[woocommerce_my_account]'); ?>
</main>
<?php get_footer(); endif; ?>
