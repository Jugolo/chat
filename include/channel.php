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
    if(self::exists($name)){
       return false;
    }

    Database::insert("channels", [
       "name"        => $name,
       "title"       => ($title == null ? $name : $title),
       "start_group" => 0,
    ]);

    $channel = self::get($name);
    ChannelGroup::append_user($channel->id(), get_user()->id(), Config::get("start_group"));
  }
}

class ChannelData{
  private $data;

  public function __construct($data){
    $this->data = $data;
  }
}

class ChannelGroup{
   public static function append_user($cid, $uid, $gid){

   }
}
