<?php

class Channel{
  private static $channles = [];

  public static function exists($name){
    if(!empty(self::$channels[$name]))
      return true;

    //wee has not cache it yet so wee create it if it exists.
    $sql = Database::query("SELECT * FROM `".table("channels")." WHERE `name`=".Database::qlean($name));
    if($sql->rows() == 1){
      self::$channels[$name] = $sql->fetch();
      return true;
    }

    return false;
  }
}
