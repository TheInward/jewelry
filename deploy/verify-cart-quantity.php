<?php
// Run with wp eval-file; uses unsaved products and an in-memory cart only.
defined('ABSPATH') || exit;
$product=new WC_Product_Simple();$product->set_id(99999999);$product->set_status('publish');$product->set_regular_price('10');$product->set_price('10');$product->set_manage_stock(true);$product->set_stock_quantity(5);$product->set_stock_status('instock');
$cart=new class($product){
 public $product;public $other=0;
 function __construct($p){$this->product=$p;}
 function get_cart_item($key){return $key==='test'?['data'=>$this->product,'quantity'=>2]:[];}
 function get_cart_item_quantities(){return [$this->product->get_stock_managed_by_id()=>2+$this->other];}
};
function expect_quantity($cart,$key,$raw,$expected){
 try{$actual=study_cart_quantity($cart,$key,$raw);if($expected===false)throw new LogicException('Expected rejection: '.json_encode($raw));if($actual!==$expected)throw new LogicException('Wrong validated quantity');}
 catch(LogicException $e){throw $e;}catch(Exception $e){if($expected!==false)throw $e;}
}
expect_quantity($cart,'test','0',0);expect_quantity($cart,'test','5',5);
foreach(['-2','1.5','not-a-number',[], '6'] as $value)expect_quantity($cart,'test',$value,false);
expect_quantity($cart,'missing','1',false);
$cart->other=3;expect_quantity($cart,'test','3',false);expect_quantity($cart,'test','2',2);
$cart->other=0;$product->set_sold_individually(true);expect_quantity($cart,'test','2',false);expect_quantity($cart,'test','1',1);
$product->set_sold_individually(false);$product->set_stock_status('outofstock');expect_quantity($cart,'test','1',false);expect_quantity($cart,'test','0',0);
WP_CLI::success('Quantity boundaries, shared stock, sold-individually, removal and stale keys passed without saving data.');
