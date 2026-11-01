<?php
if(PHP_SAPI!=='cli')exit;
$path='/srv/enroute-preview/jewelry/wp-config.php';
$source=file_get_contents($path);
$source=str_replace("\ndefine('ABSPATH', __DIR__.'/');", "\nif (!defined('ABSPATH')) define('ABSPATH', __DIR__.'/');",$source,$count);
if($count===1)file_put_contents($path,$source);
echo "Config adjustment count: $count\n";
