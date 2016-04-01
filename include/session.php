<?php

class Session{
   private static $sessions = [];
   private static $current = null;

   public static create(){
      //wee need to create a new tokens to the user
      $token = self::new_token();

      if(self::token_exists($token))
         return self::create();

      self::$sessions[$tokens] = new SessionData($token, time(), []);

      return $token;
   }

   public static function set_current($token){
     if(!self::token_exists($token))
        return false;

      self::$current = $token;
   }

   private static token_exists($token){
      //if it not exists in the session array it not exists at all!
      if(!empty(self::$sessions[$token]))
        return true;

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
    private $token;

    public function __construct($token){
       $this->token = $created;
    }

    public function control(){
        //wee got all data about this session.
        $sql = Database::query("SELECT * FROM ".table("session_data")." WHERE `token`=".Database::qlean($this->token));
        //here wee detect if the data should be created
        if($sql->rows() == 0){
           $data = [
             "token"   => $this->token,
             "created" => time(),
           ]; 
           if(!Database::insert("session_data", $data))
              return false;//failed to create the token.
        }else{
           $data = $sql->fetch();
        }

        $time = 60*60;

        if(time()-$time > $data["created"])
           return false;

        return true;
    }
}
