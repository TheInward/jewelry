<?php
defined('ABSPATH') || exit;
update_option('blogname', 'Seleneith');
update_option('blogdescription', 'Jewelry for your everyday style');
update_option('woocommerce_email_from_name', 'Seleneith');
// Do not invent a customer-service address or change the existing sender address.
update_option('woocommerce_email_footer_text', '{site_title} · seleneith.com');
foreach (get_posts(['post_type'=>['page','product'],'post_status'=>'publish','numberposts'=>-1]) as $post) {
    $content = $post->post_content;
    $content = str_replace('It boasts excellent color retention, with a simple design for easy layering.', 'Its simple design makes it easy to layer.', $content);
    $content = str_replace('This store is a development preview. Product imagery and branding are being prepared for launch.', 'Seleneith brings together necklaces, earrings, rings and bracelets to help you express your everyday style. This store is being prepared for launch.', $content);
    $content = str_replace('<h2>Jewelry for your everyday style</h2>', '<h2>Seleneith — jewelry for your everyday style</h2>', $content);
    $content = str_replace('We are preparing a collection of jewelry designed to make everyday styling more personal. Thank you for visiting while we build the store.', 'Welcome to Seleneith. We believe jewelry can make everyday styling more personal, whether you prefer a subtle accent or a statement piece. Thank you for being here as we prepare our store.', $content);
    $content = str_replace('Our final brand story and launch details will be published here.', 'Explore the collection and find the pieces that feel like you. — Seleneith', $content);
    $content = str_replace('Wishlist items are currently stored in your browser.', 'Guest wishlist items are stored in your browser; signed-in wishlists are saved to your account.', $content);
    if ($content !== $post->post_content) {
        $result=wp_update_post(wp_slash(['ID'=>$post->ID,'post_content'=>$content]),true);
        if(is_wp_error($result)) throw new RuntimeException($result->get_error_message());
        WP_CLI::log('Updated public content: '.$post->ID);
    }
}
WP_CLI::success('Seleneith settings and public content updated. Source metadata, orders, sender addresses and payment configuration preserved.');
