<?php
function database_config(){
  static $c = null;

  if($c != null)
   return $c;
 
  include(realpath(dirname(__FILE__)."../../config.php"));
  return $c = [
    "host" => $db_host,
    "user" => $db_user,
    "pass" => $db_pass,
    "data" => $db_name,
    "prefix" => DB_PREFIX,
  ];
}