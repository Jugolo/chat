<?php

define("IN_CHAT", true);

include "multi_include.php";//load multi include to get all file wee need

if(($error = Database::connect(database_config()))){
  exit("[Database error]".$error);
}

Config::init();

//if this is NOT cli wee want to set ajax variabel!
if(!is_cli()){
 init_ajax();
}

//finaly wee load and set up the diffrence libary so now wee can start the server and let it running until its goal is done.
server_start();
