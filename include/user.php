<?php

function get_user(){
   return User::get(Session::getCurrentToken());
}

class User{
  private $user = [];

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
}
