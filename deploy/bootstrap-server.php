<?php
// Run once, by root, on the new isolated preview installation only.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = '/srv/seleneith-preview/jewelry';
$access = '/root/seleneith-preview-access.json';
if (file_exists($root.'/wp-config.php') || file_exists($access)) {
    throw new RuntimeException('Existing preview installation found; bootstrap stopped.');
}
$dbpass=bin2hex(random_bytes(24));
$adminpass=bin2hex(random_bytes(16));
$previewpass=bin2hex(random_bytes(12));
$pdo=new PDO('mysql:unix_socket=/var/lib/mysql/mysql.sock','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ($pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='seleneith_preview'")->fetch()) {
    throw new RuntimeException('Database seleneith_preview already exists; stopped.');
}
$pdo->exec('CREATE DATABASE seleneith_preview CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("CREATE USER 'seleneith_preview'@'localhost' IDENTIFIED BY ".$pdo->quote($dbpass));
$pdo->exec("GRANT ALL PRIVILEGES ON seleneith_preview.* TO 'seleneith_preview'@'localhost'");
$credentials=['preview_url'=>'http://39.106.108.70:8080/jewelry/','preview_user'=>'preview','preview_password'=>$previewpass,'admin_user'=>'seleneith_admin','admin_password'=>$adminpass];
file_put_contents($access,json_encode($credentials,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));chmod($access,0600);
$config="<?php\n";
$constants=['DB_NAME'=>'seleneith_preview','DB_USER'=>'seleneith_preview','DB_PASSWORD'=>$dbpass,'DB_HOST'=>'localhost','DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_HOME'=>'http://39.106.108.70:8080/jewelry','WP_SITEURL'=>'http://39.106.108.70:8080/jewelry'];
foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)$constants[$key]=bin2hex(random_bytes(48));
foreach($constants as $name=>$value)$config.='define('.var_export($name,true).', '.var_export($value,true).");\n";
$config.="define('DISALLOW_FILE_EDIT',true);\ndefine('DISABLE_WP_CRON',true);\ndefine('WP_MEMORY_LIMIT','128M');\ndefine('WP_MAX_MEMORY_LIMIT','256M');\n\$table_prefix = 'erj_';\nif (!defined('ABSPATH')) define('ABSPATH', __DIR__.'/');\nrequire_once ABSPATH.'wp-settings.php';\n";
file_put_contents($root.'/wp-config.php',$config);chmod($root.'/wp-config.php',0640);
define('WP_INSTALLING',true);
$_SERVER['HTTP_HOST']='39.106.108.70:8080';$_SERVER['REQUEST_URI']='/jewelry/';
require $root.'/wp-load.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
wp_install('Seleneith — Development Preview','seleneith_admin','admin@example.invalid',false,'',$adminpass);
require_once ABSPATH.'wp-admin/includes/plugin.php';
$result=activate_plugin('woocommerce/woocommerce.php');
if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
echo "WordPress installed; WooCommerce activated.\n";
