<?php
declare(strict_types=1);

// A lightweight same-origin portal backed by the existing Seleneith WordPress store.
define('SELENEITH_RETURN_PORTAL', true);
$is_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$is_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
if ($is_ajax && !defined('DOING_AJAX')) define('DOING_AJAX', true);
require_once '/home/customer/www/seleneith.com/public_html/wp-load.php';

if ($is_post) {
    nocache_headers();
    if (($_POST['action'] ?? '') !== 'seleneith_return_request' || !function_exists('seleneith_return_endpoint')) {
        status_header(400);
        exit('Invalid request.');
    }
    seleneith_return_endpoint();
    exit;
}

nocache_headers();
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
$theme_uri = get_template_directory_uri();
$privacy_url = get_privacy_policy_url();
$policy_url = home_url('/pages/return-policy/');
$shop_url = home_url('/');
$form = do_shortcode('[seleneith_return_form]');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#faf8f5">
  <title>Returns &amp; Support | Seleneith</title>
  <link rel="stylesheet" href="<?php echo esc_url($theme_uri . '/returns.css'); ?>">
  <style>
    :root{color-scheme:light;--ink:#242321;--muted:#68635c;--line:#e5e1da;--paper:#faf8f5;--white:#fff}
    *{box-sizing:border-box}body{margin:0;color:var(--ink);background:var(--paper);font:15px/1.65 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
    a{color:inherit}.portal-header{height:76px;background:var(--white);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 max(24px,calc((100% - 1120px)/2));gap:20px}
    .brand{font-family:Georgia,serif;font-size:23px;letter-spacing:.17em;text-decoration:none}.portal-header>a:last-child{font-size:13px;text-decoration:none}.portal-main{max-width:1120px;margin:0 auto;padding:54px 24px 72px}
    .eyebrow{font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted)}h1{font:400 clamp(34px,5vw,52px)/1.15 Georgia,serif;margin:10px 0 14px}.portal-intro{max-width:680px;color:#514d47;margin:0 0 34px}.portal-card{background:var(--white);padding:clamp(22px,5vw,48px);border:1px solid var(--line)}
    .returns-portal{margin:0}.portal-footer{border-top:1px solid var(--line);background:var(--white);padding:22px 24px;text-align:center;color:var(--muted);font-size:12px}.portal-footer a{margin:0 9px}
    @media(max-width:700px){.portal-header{height:64px;padding:0 18px}.brand{font-size:19px}.portal-main{padding:34px 16px 48px}.portal-card{padding:20px 16px}.portal-intro{font-size:14px}}
  </style>
</head>
<body>
  <header class="portal-header"><a class="brand" href="<?php echo esc_url($shop_url); ?>">SELENEITH</a><a href="<?php echo esc_url($shop_url); ?>">Continue shopping&nbsp; →</a></header>
  <main class="portal-main">
    <div class="eyebrow">Customer care</div>
    <h1>Returns &amp; support</h1>
    <p class="portal-intro">Send us your order details and tell us how we can help. Our team will review your request and reply by email within 48 hours. Please wait for our instructions before sending any item.</p>
    <div class="portal-card"><?php echo $form; // WordPress shortcode outputs escaped, purpose-built form markup. ?></div>
  </main>
  <footer class="portal-footer"><a href="<?php echo esc_url($policy_url); ?>">Return Policy</a><a href="<?php echo esc_url($privacy_url); ?>">Privacy Policy</a><a href="mailto:seleneith@163.com">Contact</a><span>© <?php echo esc_html(gmdate('Y')); ?> Seleneith</span></footer>
  <script>window.seleneithReturns={ajax:<?php echo wp_json_encode('https://returns.seleneith.com/'); ?>};</script>
  <script src="<?php echo esc_url($theme_uri . '/returns.js'); ?>" defer></script>
</body>
</html>
