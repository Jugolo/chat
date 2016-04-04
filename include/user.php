<?php

function get_user(){
   return User::get(Session::getCurrentToken());
}

class User{
  private $user = [];

  public static function get($token){

  }
}
