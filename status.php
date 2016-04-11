<?php
define("IN_CHAT", true);
include 'multi_include.php';

$json = array(
		'isWebSocket' => false,
		'host'        => null,
		'port'        => null,
		'config'      => [
				'startChannel' => '#start'
		]
);

if(file_exists("include/websocket.txt")){
	$data = unserialize(file_get_contents("include/websocket.txt"));
	$json = [
			'isWebSocket' => true,
			'host'        => $data['host'],
			'port'        => $data['port'],
			'config'      => $json["config"]
	];
}

init_ajax();
exit(json_encode($json));