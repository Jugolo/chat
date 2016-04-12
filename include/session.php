<?php

class Session{
   private static $sessions = [];
   private static $current = null;
   private static $token;

   public static function add_token($token){
      if(!self::token_exists($token))
        return false;

      self::$sessions[$token] = new SessionData($token);
      return self::set_current($token);
   }

   public static function remove($token){
      $query = Database::query("DELETE FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token));
      if($query->rows() != 0){
         //controle if wee got a cache of the session 
         if(!empty(self::$sessions[$token])){
           unset(self::$sessions[$token]);
         }
   
         return true;
      }
   
      return false;
   }

   public static function set_current($token){
     if($token != null && !self::token_exists($token))
        return false;

      self::$current = $token;
      return true;
   }
  
   public static function getCurrentToken(){
      return self::$current;
   } 

   private static function token_exists($token){
   	  //if it not exists in the session array it not exists at all!
      if(!empty(self::$sessions[$token]))
        return true;

      $sql = Database::query("SELECT * FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token)." OR `ip`=".Database::qlean(ip()));
      if(!$sql){
      	$error = Database::error();
      	trigger_error("SQL FAIL\r\n[Number]".$error[0]."\r\n[Message]".$error[1]);
      }
      if($sql->rows() == 1){
         $row = $sql->fetch();
         if($row["ip"] == ip() && $row["token"] == $token){
           self::$token[$token] = new SessionData($token, $row);
           return true;
         }
         Database::query("DELETE FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token)." OR `ip`=".Database::qlean(ip()));
         Database::query("DELETE FROM `".table("session")."` WHERE `sid`='".$row["id"]."'");
      }
      return false;
   }
}


class SessionData extends ArrayIterator{
    private $token;

    public function __construct($token){
       $this->token = $token;
    }
    
    public function control(){
    	
    }
}
