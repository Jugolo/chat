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
       self::$user[$token] = $sql->fetch();
       return true;
     }

     return false;
}
