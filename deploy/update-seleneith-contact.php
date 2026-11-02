<?php
defined('ABSPATH') || exit;
$contact = '<!-- seleneith-contact:start --><section class="seleneith-contact"><h2>Contact Seleneith</h2><p>Brand: Seleneith<br>Country: China<br>Email: <a href="mailto:seleneith@163.com">seleneith@163.com</a></p></section><!-- seleneith-contact:end -->';
foreach (['contact','about-us','privacy-policy','terms-and-conditions','shipping-return','start-a-return'] as $slug) {
    $page = get_page_by_path('pages/'.$slug);
    if (!$page) throw new RuntimeException('Missing information page: '.$slug);
    $content = preg_replace('~<!-- seleneith-contact:start -->.*?<!-- seleneith-contact:end -->~s', '', $page->post_content);
    $content = str_replace('<p>Customer service contact details will be published before launch.</p>', '', $content);
    $content = str_replace('The final business identity, contact details, retention periods and privacy terms will be published before commercial launch.', 'Additional business details, retention periods and final privacy terms will be published before commercial launch.', $content);
    $result = wp_update_post(wp_slash(['ID'=>$page->ID,'post_content'=>trim($content)."\n".$contact]), true);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
}
update_option('woocommerce_email_footer_text', '{site_title} · seleneith.com<br />Contact: seleneith@163.com · China');
// Public contact information only: SMTP sender, tax location and admin email are unchanged.
WP_CLI::success('Updated Seleneith public contact information and customer email footer.');
