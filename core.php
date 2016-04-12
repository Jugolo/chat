<?php

define("IN_CHAT", true);

include "multi_include.php";//load multi include to get all file wee need

init_error();
if(($error = Database::connect(database_config()))){
  exit("[Database error]".$error);
}

Config::init();

//if this is NOT cli wee want to set ajax variabel!
if(!is_cli()){
 init_ajax();
}

function cli_title(){
	if(!is_cli())
		return;
	cli_set_process_title("Jugolo chat - Users: ".User::numberUser().". Channels: 0. Connections: ".WebSocketCache::$cache->connectionCount());
}

//finaly wee load and set up the diffrence libary so now wee can start the server and let it running until its goal is done.
server_start();

ShoutDown::onEnd();