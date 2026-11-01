<?php
if(PHP_SAPI!=='cli')exit;
require '/srv/enroute-preview/jewelry/wp-load.php';
require_once ABSPATH.'wp-admin/includes/user.php';
$emails=['codex-account-check-1793509260330@example.invalid','codex-account-check-1793509337973@example.invalid','codex-account-check-1793509373374@example.invalid','codex-account-check-1793509464100@example.invalid'];
$count=0;
foreach($emails as $email){$user=get_user_by('email',$email);if(!$user)continue;if(!in_array('customer',$user->roles,true)||wc_get_orders(['customer_id'=>$user->ID,'limit'=>1]))throw new RuntimeException('Test customer has unexpected role or orders; cleanup stopped.');if(wp_delete_user($user->ID))$count++;}
echo "Removed $count disposable account-check customers.\n";
