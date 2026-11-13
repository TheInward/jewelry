<?php
defined('ABSPATH')||exit;
function return_assert($ok,$message){if(!$ok)throw new RuntimeException($message);}
$marker='RETURN-UNIT-'.strtoupper(bin2hex(random_bytes(8)));$email=strtolower($marker).'@example.invalid';$ids=[];$keys=seleneith_return_rate_keys($email,'2001:db8::test');
$input=['email'=>$email,'order_number'=>$marker,'items'=>'TEST-SKU, Gold, quantity 1','reason'=>'other','details'=>'Test request — not a real return.','website'=>'','acknowledge'=>'1'];
$original_user=get_current_user_id();$original_post=$_POST;
try{
 foreach([['email'=>['array']],['email'=>'bad email'],['order_number'=>''],['items'=>str_repeat('a',2001)],['reason'=>'invalid'],['reason'=>'other','details'=>''],['acknowledge'=>'0'],['website'=>'bot.example']] as $bad){$rejected=false;try{seleneith_return_fields(array_replace($input,$bad));}catch(InvalidArgumentException $e){$rejected=true;}return_assert($rejected,'Invalid form accepted');}
 $fields=seleneith_return_fields($input);$first=seleneith_return_create($fields,'2001:db8::test');return_assert(!is_wp_error($first),'Could not create test request');
 $ids=get_posts(['post_type'=>'seleneith_return','post_status'=>'private','fields'=>'ids','numberposts'=>-1,'meta_key'=>'_return_order_number','meta_value'=>$marker]);return_assert(count($ids)===1,'Missing private ticket');$id=$ids[0];
 return_assert(get_post_status($id)==='private','Ticket not private');return_assert(get_post_meta($id,'_return_email',true)===$email,'Email not saved');return_assert(get_post_meta($id,'_return_items',true)===$input['items'],'Items not saved');
 $second=seleneith_return_create($fields,'2001:db8::test');return_assert($second['reference']===$first['reference'],'Duplicate generated new reference');
 for($i=2;$i<=3;$i++){$next=$fields;$next['details']='Test request '.$i;$result=seleneith_return_create($next,'2001:db8::test');return_assert(!is_wp_error($result),'Quota blocked too early');}
 $next['details']='Must be limited';$limited=seleneith_return_create($next,'2001:db8::test');return_assert(is_wp_error($limited)&&$limited->get_error_code()==='return_limit','Quota not enforced');
 wp_set_current_user(0);return_assert(!current_user_can('read_post',$id)&&!current_user_can('edit_post',$id),'Guest can read or edit ticket');
 foreach(['customer','subscriber'] as $role)if(get_role($role))return_assert(!get_role($role)->has_cap('manage_woocommerce'),'Customer role can manage returns');
 $type=get_post_type_object('seleneith_return');return_assert(!$type->public&&!$type->publicly_queryable&&!$type->show_in_rest,'Ticket API is public');
 $admins=get_users(['role'=>'administrator','fields'=>'ID','number'=>1]);return_assert((bool)$admins,'No administrator found');wp_set_current_user($admins[0]);return_assert(current_user_can('edit_post',$id),'Administrator cannot manage ticket');
 $_POST=['seleneith_return_review_nonce'=>'invalid','seleneith_return_status'=>'closed'];do_action('save_post_seleneith_return',$id);return_assert(get_post_meta($id,'_return_status',true)==='new','Invalid admin nonce changed status');
 $_POST=['seleneith_return_review_nonce'=>wp_create_nonce('seleneith-return-review'),'seleneith_return_status'=>'responded','seleneith_return_notes'=>'TEST note <script>alert(1)</script>'];do_action('save_post_seleneith_return',$id);return_assert(get_post_meta($id,'_return_status',true)==='responded','Status not updated');return_assert(strpos(get_post_meta($id,'_return_notes',true),'<script>')===false,'Notes contain HTML');
 WP_CLI::success('Return validation, private storage, deduplication, quota, guest/customer permissions and admin nonce/status/notes passed. No real order, email or refund.');
}finally{
 wp_set_current_user($original_user);$_POST=$original_post;
 $ids=get_posts(['post_type'=>'seleneith_return','post_status'=>'any','fields'=>'ids','numberposts'=>-1,'meta_key'=>'_return_order_number','meta_value'=>$marker]);
 foreach($ids as $id)if(get_post_meta($id,'_return_email',true)===$email)wp_delete_post($id,true);
 foreach($keys as $key)delete_transient($key);
 WP_CLI::log('Test tickets and test rate counters removed.');
}
