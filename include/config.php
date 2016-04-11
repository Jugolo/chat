<?php
function database_config(){
  static $c = null;

  if($c != null)
   return $c;
  include(realpath(dirname(__FILE__)."../../../../")."\\config.php");
  return $c = [
    "host" => $db_host,
    "user" => $db_user,
    "pass" => $db_pass,
    "data" => $db_name,
    "prefix" => DB_PREFIX,
  ];
}

class Config{
  private static $data = [];

  public static function init(){
    $sql = Database::query("SELECT `key`, `value` FROM `".table("config")."`");
    while($row = $sql->fetch())
      self::$data[$row["key"]] = $row["value"];
  }

  public static function get($key){
    return self::$data[$key];
  }
}
