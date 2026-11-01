<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$_SERVER['HTTP_HOST']='39.106.108.70:8080';$_SERVER['REQUEST_URI']='/jewelry/';
require '/srv/enroute-preview/jewelry/wp-load.php';
if(!class_exists('WC_Install'))throw new RuntimeException('WooCommerce unavailable');
WC_Install::install();
switch_theme('enroute-study');
require '/srv/enroute-preview/import.php';
echo "\nPublished products: ".wp_count_posts('product')->publish."\n";
