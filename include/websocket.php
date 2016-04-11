<?php
class WebSocketCache{
	public static $cache = null;
}
class WebSocket{
	private $events = [];
	private $host, $port;
	private $socket = null;
	private $run = true;
	private $connections = [];
	private $clients = [];
	private $currentClient = null;
	public function __construct($host, $port){
		if(!is_numeric($port))
			return null;
		
		$this->host = $host;
		$this->port = $port;
	}
	public function render_clients($callback){
		foreach ($this->clients as $client){
			$callback($client);
		}
	}
	public function getCurrent(){
		return $this->currentClient;
	}
	public function init(){
		if(empty($this->host)||empty($this->port))
			return false;
		
		if(!function_exists("socket_create"))
			return false;
		
		if(($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))===false)
			return false;
		
		if(socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)===false)
			return false;
		
		if(!filter_var($this->host, FILTER_VALIDATE_IP)){
			$this->host = gethostbyname($this->host);
		}
		
		if(@socket_bind($this->socket, $this->host, $this->port)===false){
			return false;
		}
		
		if(socket_listen($this->socket, 20)===false){
			return false;
		}
		
		$this->newClient($this->socket, true);
		return true;
	}
	public function appendEvents($event, $callback){
		if(empty($this->events[$event])){
			$this->events[$event] = $callback;
			return true;
		}
		
		return false;
	}
	public function listen(){
		$this->run = true; // if the websocket is stopped befor start it here
		while($this->run){
			$read = $this->connections;
			$write = $ex = null;
			@socket_select($read, $write, $ex, null);
			if(in_array($this->socket, $read)){
				$this->newClient(socket_accept($this->socket));
			}else{
				foreach($read as $socket){
					//wee set the current client :)
					$this->currentClient = $this->getClient($socket);
					while (@socket_recv($socket, $buf, 1024, 0) >= 1){
						$this->triggerEvents("onmessage", $this->unmask($buf));
						break 2;
					}
					
					//look up after disconnected user :)
					$buffer = @socket_read($socket, 1024, PHP_NORMAL_READ);
					if($buffer == null){
						$this->removeClient($socket);
					}
				}
			}
		}
	}
	private function newClient($stream, $onStart = false){
		if($stream < 0){
			echo "Fail to accept socket: ".$stream."\r\n";
			return;
		}
		$this->connections[] = $stream;
		$this->clients[] = $obj = new WebSocketClient($stream);
		if(!$onStart)
			$this->handshake($stream);
		return $obj;
	}
	private function removeClient($stream){
		echo "Remove client: ".$stream."\r\n";
		//trigger event onclose
		$this->triggerEvents("onclose", null);
		unset($this->connections[array_search($stream, $this->connections)]);
		for($i = 0; $i<count($this->clients); $i++)
			if($this->clients[$i]->isStream($stream)){
				array_slice($this->clients, $i, 1);
				return;
			}
		
		@socket_close($stream);
	}
	private function getClient($stream){
		for($i=0;$i<count($this->clients);$i++){
			if($this->clients[$i]->isStream($stream)){
				return $this->clients[$i];
			}
		}
		
		return null;
	}
	private function handshake($stream){
		$head = array();
		// handshake :)
		$lines = explode("\r\n", socket_read($stream, 1024));
		for($i = 0; $i<count($lines); $i++){
			$line = trim($lines[$i]);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
				$head[$matches[1]] = $matches[2];
			}
		}
		if(empty($head['Sec-WebSocket-Key'])){
			$this->removeClient($stream);
			echo "Missing Sec-WebSocket-Key\r\n";
			return false;
		}
		$key = $head['Sec-WebSocket-Key'];
		$hkey = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		
		$uhead = array();
		
		$uhead[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
		$uhead[] = "Upgrade: websocket";
		$uhead[] = "Connection: Upgrade";
		$uhead[] = "Sec-WebSocket-Accept: ".$hkey;
		$handshake = implode("\r\n", $uhead)."\r\n\r\n";
		// exit($handshake);
		if(socket_write($stream, $handshake, strlen($handshake))===false){
			exit("Handshake fail");
		}
		echo "New client connected to server\r\n";
		return null;
	}
	
	private function unmask($text){
			$length = ord($text[1]) & 127;
	if ($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	} elseif ($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	} else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text.= $data[$i] ^ $masks[$i % 4];
	}
	return $text;
	}
	
	private function triggerEvents($name, $data){
		if(!empty($this->events[$name])){
			$buffer = $this->events[$name];
			$buffer($this->currentClient, $data);
		}
	}
}
class WebSocketClient{
	private $stream;
	public $connectionData = [];
	private $myIp = null;
	public function __construct($stream){
		$this->stream = $stream;
	}
	public function isStream($stream){
		return $this->stream==$stream;
	}
	public function write_line($str){
		$string = $this->mask($str);
		if(!$s = @socket_write($this->stream, $string."\r\n", strlen($string."\r\n")))
			echo "[".$this->stream."]failed to write the line: ".$str."\r\n";
		
		echo "[S]".$str."\r\n";
	}
	public function close(){
		@socket_close($this->stream);
	}
	public function ip(){
		if($this->myIp==null){
			socket_getpeername($this->stream, $this->myIp);
		}
		return $this->myIp;
	}
	private function mask($data){
		$frame = array();
        $encoded = "";
        $frame[0] = 0x81;
        $data_length = strlen($data);

        if($data_length <= 125){
            $frame[1] = $data_length;
        }else{
            $frame[1] = 126;
            $frame[2] = $data_length >> 8;
            $frame[3] = $data_length & 0xFF;
        }

        for($i=0;$i<sizeof($frame);$i++){
            $encoded .= chr($frame[$i]);
        }

        $encoded .= $data;
        return $encoded;
	}
}
