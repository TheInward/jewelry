<?php
if(PHP_SAPI!=='cli')exit;
$settingsPath='/srv/seleneith-preview/preview-gate-config.php';
if(file_exists($settingsPath))throw new RuntimeException('Gate config already exists; stopped.');
$access=json_decode(file_get_contents('/root/seleneith-preview-access.json'),true,512,JSON_THROW_ON_ERROR);
$settings=['password_hash'=>password_hash($access['preview_password'],PASSWORD_DEFAULT),'secret'=>bin2hex(random_bytes(32))];
file_put_contents($settingsPath,"<?php return ".var_export($settings,true).";\n");
chgrp($settingsPath,'seleneith');chmod($settingsPath,0640);
echo "Preview gate configured.\n";
