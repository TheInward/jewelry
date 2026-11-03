<?php if(!is_user_logged_in()): ?>
<!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head><body <?php body_class('account-entry'); ?>><?php wp_body_open(); ?>
<a class="account-back" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Back to shop">←</a>
<div class="account-markets"><?php get_template_part('market-switcher'); ?></div>
<main class="account-entry-layout"><div class="account-entry-image"><img src="https://seleneith.com/wp-content/uploads/seleneith-media/a9c3040f8d01246e6035d36b.jpg" alt="Jewelry styling" fetchpriority="high"></div><div class="account-entry-form woocommerce account-shell"><img class="account-logo" src="<?php echo esc_url(get_template_directory_uri().'/assets/seleneith-wordmark.svg'); ?>" width="160" height="32" alt="Seleneith"><?php echo do_shortcode('[woocommerce_my_account]'); ?></div></main>
<?php wp_footer(); ?></body></html>
<?php else: get_header(); ?>
<main class="content woocommerce account-shell">
<?php echo do_shortcode('[woocommerce_my_account]'); ?>
</main>
<?php get_footer(); endif; ?>
