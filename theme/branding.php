<?php
defined('ABSPATH') || exit;

// Local brand assets keep identity independent of the reference storefront.
function seleneith_site_icon() {
    echo '<link rel="icon" type="image/svg+xml" href="'.esc_url(get_template_directory_uri().'/assets/seleneith-icon.svg').'">' . "\n";
}
add_action('wp_head', 'seleneith_site_icon', 99);
add_action('login_head', 'seleneith_site_icon', 99);
add_filter('login_headerurl', function(){ return home_url('/'); });
add_filter('login_headertext', function(){ return 'Seleneith'; });
add_filter('wp_mail_from_name', function($name){ return $name === 'WordPress' ? 'Seleneith' : $name; });
add_action('login_enqueue_scripts', function(){
    echo '<style>.login h1 a{background-image:url('.esc_url(get_template_directory_uri().'/assets/seleneith-wordmark.svg').');background-size:contain;width:240px;height:60px}</style>';
});
