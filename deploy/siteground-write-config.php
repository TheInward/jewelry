<?php
// Run over SSH with database credentials supplied through stdin, never argv.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$input=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
$root='/home/customer/jewelry-migration/site';
if(!is_file($root.'/wp-load.php')||is_file($root.'/wp-config.php'))throw new RuntimeException('Expected fresh migration staging directory.');
foreach(['database','user','password'] as $key)if(empty($input[$key])||!is_string($input[$key]))throw new RuntimeException('Missing database credentials.');
foreach(['database','user'] as $key)if(!preg_match('/^[A-Za-z0-9_]+$/',$input[$key]))throw new RuntimeException('Invalid database identifier.');
$pdo=new PDO('mysql:host=localhost;dbname='.$input['database'].';charset=utf8mb4',$input['user'],$input['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($pdo->query('SHOW TABLES')->fetch())throw new RuntimeException('Destination database is not empty; stopped.');
$values=['DB_NAME'=>$input['database'],'DB_USER'=>$input['user'],'DB_PASSWORD'=>$input['password'],'DB_HOST'=>'localhost','DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_HOME'=>'https://seleneith.com','WP_SITEURL'=>'https://seleneith.com'];
foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)$values[$key]=bin2hex(random_bytes(48));
$config="<?php\n";
foreach($values as $key=>$value)$config.='define('.var_export($key,true).', '.var_export($value,true).");\n";
$config.="define('DISALLOW_FILE_EDIT',true);\ndefine('WP_MEMORY_LIMIT','128M');\ndefine('WP_MAX_MEMORY_LIMIT','256M');\n\$table_prefix='erj_';\nif(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');\nrequire_once ABSPATH.'wp-settings.php';\n";
file_put_contents($root.'/wp-config.php',$config);chmod($root.'/wp-config.php',0600);
echo "Destination database verified and staging WordPress configured.\n";
