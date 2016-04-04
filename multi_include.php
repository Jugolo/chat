<?php
if(!defined("IN_CHAT")){
  exit("hack detected");
}

include("include/system.php");
include("include/server.php");
include("include/database.php");
include("include/config.php");
include("include/session.php");
include("include/ajax.php");
include("include/channel.php");
include("include/user.php");
include("include/websocket.php");


//globel command
include("include/command/globel/login.php");
include("include/command/globel/join.php");
