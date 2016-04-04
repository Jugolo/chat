<?php

class Session{
   private static $sessions = [];
   private static $current = null;

   public static function add_token($token){
      if(!self::token_exists($token))
        return false;

      $session = self::$token[$token] = new SessionData($token);
      if(!$session->control()){
         self::remove($token);
         return false;
      }

      self::set_current($token);
      return true;
   }

   public static function remove($token){
      $query = Database::query("DELETE FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token));
      if($query->rows() != 0){
         //controle if wee got a cache of the session 
         if(!empty(self::sessions[$token])){
           unset(self::sessions[$token]);
         }
   
         return true;
      }
   
      return false;
   }

   public static function set_current($token){
     if($token != null && !self::token_exists($token))
        return false;

      self::$current = $token;
   }
  
   public function getCurrentToken(){
      return self::$current;
   } 

   private static token_exists($token){
      //if it not exists in the session array it not exists at all!
      if(!empty(self::$sessions[$token]))
        return true;

      $sql = Database::query("SELECT * FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token)." OR `ip`=".Database::qlean(ip()));
      if($sql->rows() == 1){
         $row = $sql->fetch();
         if($row["ip"] == ip() && $row["token"] == $token){
           self::$token[$token] = new SessionData($token, $row);
           return true;
         }

         Database::query("DELETE FROM `".table("session_data")."` WHERE `token`=".Database::qlean($token)." OR `ip`=".Database::qlean(ip()));
         Database
      }
      return false;
   }

   private static function new_token(){
      $use = "qwertyuioplkjhgfdsazxcvbnm.,?!'-/:;()&@[]{}#%^*+=£$€><~|\_1234567890";
      $return = "";
      for($i=0;$i<1001;$i++){
        $return .= $use[mt_rand(0, count($use)-1)];
      }

      return $return;
   }
}


class SessionData extends ArrayIterator{
    private $token, $data;

    public function __construct($token, $data){
       $this->token = $created;
       $this->data  = $data;
    }
}
