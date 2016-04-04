<?php

class Channel{
  private static $channles = [];

  public static function exists($name){
    if(!empty(self::$channels[$name]))
      return true;

    //wee has not cache it yet so wee create it if it exists.
    $sql = Database::query("SELECT * FROM `".table("channels")." WHERE `name`=".Database::qlean($name));
    if($sql->rows() == 1){
      self::$channels[$name] = new ChannelData($sql->fetch());
      return true;
    }

    return false;
  }

  public static function get($name){
    if(self::exists($name)){
      return self::$channels[$name];
    }

    return null;
  }

  public function create($name, $title=null){

  }
}

class ChannelData{
  private $data;

  public function __construct($data){
    $this->data = $data;
  }
}
