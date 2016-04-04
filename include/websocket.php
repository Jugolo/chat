<?php

class WebSocket{
   private $callback   = null;
   private $server     = null;
   private $connection = [];
   private $client     = [];
   private $run        = true;
   private $current_client;

   public function add_callback($callback){
     $this->callback = $callback;
   }

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
        foreach($read as $socket){
          if($this->server == $socket){
            $c = socket_accept($socket);
            if($c < 0){
              echo "[".$c."]failed to accept new connection\r\n";
            }else{
              $this->handshake($socket);
            }
            continue;
          }

          $client = $this->client[$socket];
          $r = @socket_recv($socket,$buffer,1024,0);
          if(!$r || $r == 0){
            $this->remove($socket);
            continue;
          }
          $this->current_client = $client;
          if($this->callback != null){
             $buf = $this->callback;
             $buf($this, $buffer);
          }
        }
      }
   }

   private function unmask($text){
      $length = ord($text[1]) & 127;
      if($length == 126) {
         $masks = substr($text, 4, 4);
         $data = substr($text, 8);
      }elseif($length == 127) {
         $masks = substr($text, 10, 4);
         $data = substr($text, 14);
      }else {
         $masks = substr($text, 2, 4);
         $data = substr($text, 6);
      }
      $text = "";
      for ($i = 0; $i < strlen($data); ++$i) {
          $text .= $data[$i] ^ $masks[$i%4];
      }
      return $text;
   }

   private function remove($socket){
     $this->client[$socket]->close();
     unset($this->client[$socket]);
     array_splice($this->connection, array_search($socket, $this->connection), 1);
   }

   private function handshake($connection){
       $user = $this->new_client($connection);

       $header = [];
       $read_line = function($stream){
         $read = socket_read($this->stream, 1024, PHP_NORMAL_READ);
         if($read == "")
           return null;
         return $read;
       }

       while(($line = $read_line($connection)) != null){
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
   public  $token = null;
   public $connectionData = [];

   public function __construct($stream){
     $this->stream = $stream;
   }

   public function write_line($str){
     if(!socket_write($this->stream, $str."\r\n", strlen($str."\r\n")))
      echo "[".$this->stream."]failed to write the line: ".$str."\r\n";
   }
}
