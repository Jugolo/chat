<?php
session_start();
require_once '../../maincore.php';
require_once 'infusion_db.php';
require_once THEMES."templates/header.php";
require_once INCLUDES."infusions_include.php";
require_once 'lib/client.php';
require_once 'lib/db.php';
require_once 'lib/define.php';
require_once INFUSIONS."chat/locale/".str_replace("/",null,LOCALESET).".php";

$config = get_settings("chat");

if(!iGUEST){
	jugoloChatClientOnStart();
	include 'page/chat.php';
}else{
	//you may not bee here!!! :)
	opentable("Error!");
	echo "<div id='error' style='text-align:center;'>";
	 echo $locale['errorGeaust'];
	echo "</div>";
}

closetable();
require_once THEMES."templates/footer.php";