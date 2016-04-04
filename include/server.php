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

  WebSocketCache::$cache = $websocket = new WebSocket();
  $websocket->add_callback(function(WebSocket $websocket, $message){
     if(!empty($websocket->current_client->connectionData["token"]))
       Session::set_current($websocket->current_client->connectionData["token"]);
     else
       Session::set_current(null);//no token got so it not login
       

     handlePost($message);
  });
  $websocket->init($argv[0], $argv[1]);
}

function serverAjaxStart(){
 
}

function handlePost($message){
   //is this globel message or is it channel message. 
   //globel message "Command: data
   //channel "Command Channel: data
   $first = explode(" ", substr($message, 0, strpos($message, ": ")));
   $data  = substr($message, strpos($message, ": ")+1);

   //control if the user has a token. 
   if(Session::getCurrentToken() == null){
      if(count($first) == 1 && $first[0] == "LOGIN")
        globel_login("LOGIN", $data);
      else
        send("ERROR: You are not login yet", true);
      return;
   }

   if(count($first) == 1){
     handleGlobelPost($first[0], $data);
   }else{
     hansleChannelMessage($first[0], $first[1], $data);
   }
}

function handleGlobelPost($command, $data){
   switch($command){
      case "LOGIN":
        globel_login($command, $data);
      break;
   }
}

function handleChannelPost($command, $channel, $data){

}

function send($msg, $private = false){
  if(is_cli()){
    WebSocketSend($msg, $private);
  }else{
  
  }
}

function WebSocketSend($msg, $private){
    if($private){
      //wee send it right away now.
      WebSocketCache::$cache->write_line($msg);
      return;
    }
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
