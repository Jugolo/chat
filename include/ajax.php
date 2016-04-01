<?php
/**
Ajax sever handle. Offer option to handle ajax server.
*/
class Ajax{
/**
 Set headers from array. 
*/
 public static function header(array $data){
   foreach($data as $value){
      header($value[0].": ".$value[1], empty($value[2]) ? true : $value[2]);
   }
 }
}
