<?php
defined('ABSPATH') || exit;
// Validate shape and size before sanitizers can silently coerce malformed input.
function study_post_text($key,$maximum=100){
 $raw=$_POST[$key]??'';
 if(!is_string($raw)||strlen($raw)>$maximum)throw new InvalidArgumentException('Invalid request field: '.sanitize_key($key).'.');
 return wp_unslash($raw);
}
function study_product_ids_input($raw){
 if($raw==='')return [];
 $parts=explode(',',$raw);
 if(count($parts)>100)throw new InvalidArgumentException('Please select no more than 100 products.');
 foreach($parts as $part)if(!preg_match('/^[1-9][0-9]{0,9}$/D',$part))throw new InvalidArgumentException('Please choose valid products.');
 return array_values(array_unique(array_map('intval',$parts)));
}
