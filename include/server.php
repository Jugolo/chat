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
	
	if(count($argv)!=3){
		exit("[Error] the system need 2 agument. Host and port. The server could not start");
	}
	
	define("WEBSOCKET_CONFIG_PATH", realpath(dirname(__FILE__)."../")."\\websocket.txt");
	
	$fopen = fopen(WEBSOCKET_CONFIG_PATH, "w");
	fwrite($fopen, serialize([
			"host" => $argv[1],
			"port" => $argv[2]
	]));
	fclose($fopen);
	
	ShoutDown::add(function (){
		if(is_cli()&&file_exists(WEBSOCKET_CONFIG_PATH))
			@unlink(WEBSOCKET_CONFIG_PATH);
	});
	
	WebSocketCache::$cache = $websocket = new WebSocket($argv[1], $argv[2]);
	
	if(!$websocket->init()){
		echo "WebSocket failed to initlize\r\n";
		return;
	}
	
	cli_title();
	
	$websocket->appendEvents("onmessage", function (WebSocketClient $client, $data){
		echo "[C]".$data."\r\n";
		Session::set_current(empty($client->connectionData["token"]) ? null : $client->connectionData["token"]);
		handlePost($data);
	});
	
	$websocket->appendEvents("onclose", function(WebSocketClient $client){
		if(empty($client->connectionData["token"]))
			return;//this user is never login :)
		
		$user = User::get($client->connectionData["token"]);
		foreach($user->getChannelNames() as $name){
			$user->leave_channel($name);
		}
		
		cli_title(User::numberUser(), 0);
	});
	
	$websocket->appendEvents("onconnect", function(){
		cli_title();
	});
	
	$websocket->appendEvents("newcyclus", function(){
		echo "hehe\r\n";
		garbage_collect();
	});
	
	$websocket->listen();
	
	return false;
}
function serverAjaxStart(){}
function handlePost($message){
	// is this globel message or is it channel message.
	// globel message "Command: data
	// channel "Command Channel: data
	$first = explode(" ", substr($message, 0, strpos($message, ": ")));
	$data = substr($message, strpos($message, ": ")+2);
	
	// control if the user has a token.
	if(Session::getCurrentToken()==null){
		if(count($first)==1&&$first[0]=="LOGIN"){
			if(!is_cli()&&cookie("identify")!=$data){
				send("ERROR: Token is broken");
				return;
			}
			globel_login($data);
		}else{
			send("ERROR: You are not login yet", true);
		}
		return;
	}
	
	if(count($first)==1){
		handleGlobelPost($first[0], $data);
		return;
	}else{
		handleChannelPost($first[0], $first[1], $data);
	}
	
	//save the post (both in ajax and websocket)
	Database::insert("message", [
			'uid'     => get_user()->id(),
			'message' => $message,
			'channel' => Channel::get($first[1])->id(),
			'sent'    => time(),
	]);
}
function handleGlobelPost($command, $data){
	switch($command){
		case "JOIN":
			globel_join($data);
		break;
		case "TITLE":
			get_title($data);
		break;
	}
}
function handleChannelPost($command, $channel, $data){
	switch(strtolower($command)){
		case "title":
			
		break;
	}
}
function send($msg, $private = false){
	if(is_cli()){
		WebSocketSend($msg, $private);
	}else{}
}
/**
 * Send a message to users in a given channel. 
 * @param string $channel name of channel
 * @param string $message message to send to the channel
 */
function send_channel($channel, $message){
	Channel::get($channel)->log($message);
	Channel::renderUsersInChannel($channel, function (ChannelMember $member) use($channel, $message){
		if(is_cli()){
			WebSocketSend($message, false, $member->getUser(), $channel);
		}
	});
}
function WebSocketSend($msg, $private, $user = null, $channel = null){
	if($private){
		WebSocketCache::$cache->getCurrent()->write_line($msg);
		return;
	}
	
	WebSocketCache::$cache->render_clients(function (WebSocketClient $client) use($msg){
		if(array_key_exists("token", $client->connectionData)){
			$client->write_line($msg);
		}
	});
}
function ip(){
	if(is_cli()){
		return WebSocketCache::$cache->getCurrent()->ip();
	}
	
	return $_SERVER['REMOTE_ADDR'];
}

/**
 * Ajax server need to set varibels and header.
 * This is happens here
 */
function init_ajax(){
	Ajax::header([
			[
					"Content-Type",
					"application/json"
			],
			[
					"Cache-Control",
					"no-store, no-cache, must-revalidate, max-age=0"
			],
			[
					"Cache-Control",
					"post-check=0, pre-check=0",
					false
			],
			[
					"Pragma",
					"no-cache"
			]
	]);
}
