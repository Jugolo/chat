<?php
/**
Start the chat server. It will not send header or other task, it takes the request and parse it to actions.
*/
function server_start(){

}

/**
Ajax server need to set varibels and header. This is happens here
*/
function init_ajax(){
 Ajax::header([
   "Content-Type"  => "application/json",
   "Cache-Control" => "no-store, no-cache, must-revalidate, max-age=0",
   "Cache-Control" => ["post-check=0, pre-check=0", false],
   "Pragma"        => "no-cache",
 ]);
}
