<?php 
function cleanMySQL($context){
	global $db_connect;
	return mysql_real_escape_string($context,$db_connect);
}

function isDatabaseError(){
	global $db_connect;
	if(@mysql_error($db_connect)){
		exit(mysql_error($db_connect));
	}
}