<?php
include "include/system.php";

class Session{
   private static $session = [];
   private static $data;

   public static function session_start(){
     if(cookie("identify")){
        $sql = dbquery("SELECT * `".DB_PREFIX."chat_session_data` WHERE `token`='".cookie("identify")."'");
        if(dbrows($sql) == 1){
          $row = dbarray($sql);
          if($row["ip"] == $_SERVER['REMOTE_ADDR']){
             self::$data = $row;
             $sql = dbquery("SELECT `key`, `value` FROM `".DB_PREFIX."chat_session` WHERE `sid`='".$row["id"]."'");
             while($row = dbarray($sql)){
               self::$data[$row["key"]] = $row["value"];
             }
             //wee update the timer
             dbquery("UPDATE `".DB_PREFIX."chat_session_data` SET `time`='".time()."` WHERE `sid`='".self::$data["id"]."'");
             return;
          }
          dbquery("DELETE FROM `".DB_PREFIX."chat_session_data` WHERE `token`='".cookie("identify")."'");
        }
        remove_cookie("identify");
        return session_start();
     }
   }
}

Session::session_start();
