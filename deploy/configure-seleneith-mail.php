<?php
defined('ABSPATH')||exit;
update_option('woocommerce_email_from_name','Seleneith');
update_option('woocommerce_email_from_address','notifications@seleneith.com');
foreach(['new_order','cancelled_order','failed_order'] as $id){$settings=get_option('woocommerce_'.$id.'_settings',[]);$settings['recipient']='seleneith@163.com';update_option('woocommerce_'.$id.'_settings',$settings);}
WP_CLI::success('Domain-aligned notification sender configured; replies use seleneith@163.com via the theme. Inbox receipt requires separate verification.');
