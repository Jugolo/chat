<?php

function globel_login($data){
  if(!is_cli() && cookie("identify") != $data){
     send("ERROR: invald token");
     return false;
  }
  
  $isOkay = Session::add_token($data);
  if(is_cli() && $isOkay){
  	WebSocketCache::$cache->getCurrent()->connectionData["token"] = $data;
  }
  send("LOGIN: ".($isOkay ? "true" : "false"), true);
}
