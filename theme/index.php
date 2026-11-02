<?php
get_header();
if (is_front_page() || is_page('necklaces') || is_shop() || is_product_category()) {
    get_template_part('catalog-grid');
} else {
    echo '<main class="content woocommerce">';
    while (have_posts()) { the_post(); the_content(); }
    echo '</main>';
}
get_footer();
