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
include("include/shutdown.php");
include("include/error.php");
include("include/logger.php");
include("include/garbage_collect.php");
include("include/define.php");


//globel command
include("include/command/globel/login.php");
include("include/command/globel/join.php");
include("include/command/globel/title.php");
