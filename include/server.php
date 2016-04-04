<?php
/**
Start the chat server. It will not send header or other task, it takes the request and parse it to actions.
*/
function server_start(){
  if(is_cli()){
    serverSocketStart();
  }else{
    serverAjaxStart();
  }
}

function serverSocketStart(){
  global $argv;

  if(count($argv) != 2){
    exit("[Error] the system need 2 agument. Host and port. The server could not start");
  }

  $websocket = new WebSocket();
  $websocket->add_callback(function(WebSocket $websocket, $message){
     if(!empty($websocket->current_client->connectionData["token"]))
       Session::set_current($websocket->current_client->connectionData["token"]);

     handlePost($message);
  });
  $websocket->init($argv[0], $argv[1]);
}

function serverAjaxStart(){
 
}

/**
Ajax server need to set varibels and header. This is happens here
*/
function init_ajax(){
 Ajax::header([
   ["Content-Type",  "application/json"],
   ["Cache-Control", "no-store, no-cache, must-revalidate, max-age=0"],
   ["Cache-Control", "post-check=0, pre-check=0", false],
   ["Pragma",        "no-cache"],
 ]);
 //start session handler :) 
 $sessionOk = false;
 if(cookie("identify") && Session::add_token(cookie("identify"))){
   $sessionOk = true;
 }

 if(!$sessionOk){
   Session::set_current(Session::create());
 }
}
