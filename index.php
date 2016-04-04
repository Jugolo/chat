<?php
include "include/system.php";

function session_init(){
  if(cookie("identify")){
    //control the cookie to detect errors
    $sql = dbquery("SELECT * FROM `".DB_PREFIX."chat_session_data` WHERE `token`='".cookie("identify")."'");
    if(dbrows($sql) == 1){
      $row = dbarray($sql);
      if($row["ip"] == $_SERVER['REMOTE_ADDR']){
          
      }
    }
  }
}

//<-page start here.
session_init();
