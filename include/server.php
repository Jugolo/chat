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
