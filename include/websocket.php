<?php

class WebSocket{
   private $callback   = null;
   private $server     = null;
   private $connection = [];
   private $client     = [];
   private $run        = true;

   public function init($host, $port){
      $this->server = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      socket_set_option($this->server,SOL_SOCKET,SO_REUSEADDR,1);
      if(!filter_var($host, FILTER_VALIDATE_IP)){
          $host = gethostbyname($host);
      }
      socket_bind($this->server, $host, $port);
      socket_listen($this->server, 20);
      $this->new_client($this->server);
      while($this->run){
        $read = $this->connection;
        $write = $ex = null;
        @socket_select($read, $write, $ex, null);
        
      }
   }

   private function handshake($connection){
       $user = $this->new_client($connection);

       $header = [];
       while(($line = $user->read_line()) != null){
         if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
           $header[$matches[1]] = $matches[2];
         }
       }

       $key = base64_encode(sha1($header["Sec-WebSocket-Key"].'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
       $user->write_line("HTTP/1.1 101 Web Socket Protocol Handshake");
       $user->write_line("Upgrade: websocket");
       $user->write_line("Connection: Upgrade");
       $user->write_line("Sec-WebSocket-Accept: ".$key);
       $user->write_line("");
       echo "[".$connection."] connected to the server\r\n";
   }

   private function new_client($socket){
     $this->connection[] = $socket;
     return $this->client[$socket] = new WebSocketClient($socket);
   }
}

class WebSocketClient{
   private $stream;
   public $connectionData = [];

   public function __construct($stream){
     $this->stream = $stream;
   }

   public function read_line(){
     $read = socket_read($this->stream, PHP_NORMAL_READ);
     if($read == "")
       return null;
     return $read;
   }
   
   public function write_line($str){
     if(!socket_write($this->stream, $str."\r\n", strlen($str."\r\n")))
      echo "[".$this->stream."]failed to write the line: ".$str."\r\n";
   }
}
