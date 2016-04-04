<?php

function get_user(){
   return User::get(Session::getCurrentToken());
}

function getUserById($id){
  return User::run(function(UserData $user) use($id){
     if($user->id() == $id)
       return $user;
     return false;
  });
} 

class User{
  private static $user = [];

  public static function run($callback){
    $sql = Database::query("SElECT * FROM `".table("user")."`");
    while($row = $sql->fetch()){
      if(!empty(self::$user[$row["token"]]))
        $u = self::$user[$row["token"]];
      else
        $u = self::$user[$row["token"]] = new UserData($row);

      if($return = $callback($u))
        return $return;
    }
    retur null;
  }

  public static function get($token){
     if(self::exists($token)){
        return self::$user[$token]; 
     }

     return false;
  }

  public static function exists($token){
    if(!empty(self::$user))
      return true;

     $sql = Database::query("SELECT * FROM `".table("user")."` WHERE `token`=".Database::qlean($token));
     if($sql->rows()){
       self::$user[$token] = new UserData($sql->fetch());
       return true;
     }

     return false;
}

class UserData{
   private $data = [];

   public function __construct(array $data){
     $this->data = $data;
   }
  
   public function id(){
     return $this->data["id"];
   }

   public function join_channel($name){
     $channel = Channel::get($name);
     if(!$channel->is_member($this->id()){
        $channel->join($this);
     }
   }
}
