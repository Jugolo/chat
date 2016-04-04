<?php

function globel_login($data){
  if(!is_cli()){
     send("ERROR: invald token");
     return false;
  }
  
  send("LOGIN: ".(Session::add_token($data) ? "true" : "false"), true);
}
