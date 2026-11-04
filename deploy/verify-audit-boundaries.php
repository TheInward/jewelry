<?php
// wp --skip-themes eval-file this-file /absolute/candidate/theme
// Uses in-memory carts and reads existing products; no customer, email or order writes.
defined('ABSPATH') || exit;
if(!empty($args[0]))require $args[0].'/functions.php';
function audit_assert($condition,$message){if(!$condition)throw new RuntimeException($message);}
function audit_reject($callback){try{$callback();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Expected validation rejection');}
foreach([['add'=>'1','remove'=>'x'],['add'=>'1','quantity'=>'1','key'=>'x'],['key'=>'x'],['quantity'=>'1']] as $input)audit_reject(fn()=>study_cart_operation($input));
audit_assert(study_cart_operation([])===''&&study_cart_operation(['add'=>'1'])==='add','Operation parsing');
foreach(['-1','1.2','0','1,2,',str_repeat('1,',100).'1'] as $raw)audit_reject(fn()=>study_product_ids_input($raw));
$_POST=['add'=>['1']];audit_reject(fn()=>study_post_text('add'));
audit_assert(study_product_ids_input('1,2,1')===[1,2],'Unique ID parsing');
$post=get_page_by_path('waterdrop-necklace',OBJECT,'product');audit_assert((bool)$post,'Existing fixture product');
$parent=wc_get_product($post->ID);$product=null;
foreach($parent->get_children() as $id){$candidate=wc_get_product($id);if($candidate&&$candidate->is_in_stock()&&$candidate->is_purchasable()){$product=$candidate;break;}}
audit_assert((bool)$product,'Available variation fixture');
$cart=new class{public $calls=[];function add_to_cart(...$args){$this->calls[]=$args;return 'memory-only';}};
foreach(['-'.$product->get_id(),'1.5','0',[],(string)$parent->get_id()] as $raw)audit_reject(fn()=>study_cart_add($cart,$raw));
$observed=null;$block=function($pass,$id,$quantity,$variation,$attributes)use(&$observed){$observed=[$id,$quantity,$variation,$attributes];return false;};
add_filter('woocommerce_add_to_cart_validation',$block,999,5);
audit_reject(fn()=>study_cart_add($cart,(string)$product->get_id()));remove_filter('woocommerce_add_to_cart_validation',$block,999);
audit_assert(!$cart->calls,'Blocked add must not mutate cart');
audit_assert($observed===[$parent->get_id(),1,$product->get_id(),$product->get_variation_attributes()],'Validation must receive parent, quantity and variation');
study_cart_add($cart,(string)$product->get_id());audit_assert(count($cart->calls)===1&&$cart->calls[0]===$observed,'Allowed add contract');
$valid=['register'=>'1','first_name'=>'Test','last_name'=>'Shopper','password'=>'TestPassword123!','password_confirmation'=>'TestPassword123!','agree_privacy'=>'1'];
foreach([['password'=>null,'password_confirmation'=>null],['password'=>['bad'],'password_confirmation'=>['bad']],['first_name'=>'   '],['last_name'=>['bad']],['agree_privacy'=>['1']]] as $bad){
 $_POST=array_merge($valid,$bad);$errors=apply_filters('woocommerce_registration_errors',new WP_Error(),'test','test@example.invalid');audit_assert($errors->has_errors(),'Malformed registration must fail');
}
$_POST=$valid;$errors=apply_filters('woocommerce_registration_errors',new WP_Error(),'test','test@example.invalid');audit_assert(!$errors->has_errors(),'Valid registration fields must pass');$_POST=[];
$_GET['study_language']='fr';$translations=$parent->get_meta('_study_translations');$title=$translations['fr']??'';audit_assert($title!=='','French title fixture');
$ids=study_search_product_ids($title);audit_assert(in_array($parent->get_id(),$ids,true),'Localized search must find translated title');
audit_assert(study_search_product_ids('  ')==[],'Empty search');
WP_CLI::success('Cart validation hooks, mutation boundaries, bounded IDs, registration fields and localized search passed without saving data.');
