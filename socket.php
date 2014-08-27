<?php

class socket_user_client{

    public  $socket      = null;
    public  $user        = array('id' => 0);
    public  $user_config = array();
    public  $channel     = array('1' => array(
        'id'        => 1,
        'uid'       => 0,
        'cid'       => 1,
        'lastActiv' => 0,
        'isInAktiv' => No,
        'ban'       => No,
        'banTo'     => 0,
    ));
    private $data        = array();
    public  $ip          = null;
    public  $leave_chan  = null;
    public  $isLogin     = false;
    private $cookie      = false;
    public  $aktiv       = array();
    public  $ignore      = array();
    private $mysql       = array();

    function kick($cid,$isBan = false){
        if(!empty($this->channel[$cid])){
            if(!$isBan)
                unset($this->channel[$cid]);
        }
    }

    function ban($cid){
        $this->kick($cid,true);
    }

    function socket_user_client($socket, DatabaseHandler $mysql){
        $this->socket = $socket;
        $this->mysql  = $mysql;
    }

    function update_my_nick($newNick){
        $this->user['nick'] = $newNick;
    }

    function my_turn(){

        if(!$this->isLogin){
            return true;
        }

        if($this->user['user_ip'] != $this->ip()){
            return false;
        }

        return true;
    }

    function login($cookie){
        $this->cookie = $this->parseCookie($cookie);

        if(!$this->cookie(COOKIE_PREFIX."user")){
            return false;
        }

        $user_ip     = $this->ip();
        $user_cookie = $this->cookie(COOKIE_PREFIX."user");
        //vi spliter den ad ;)
        $user_cookie_data = explode(".",$user_cookie);

        $sql = $this->mysql->query("SELECT * FROM `".DB_PREFIX."users` WHERE
         `user_id`='".(empty($user_cookie_data[0]) ? '0' : (int)$user_cookie_data[0])."'
         AND `user_status`='0'
         AND `user_actiontime`='0'");
        $row = $sql->get();

        if(empty($row)){
            echo "empty row";
            return false;
        }

        if($row['user_ip'] != $user_ip){
            echo "Fandt bruger men ip passer ikke\r\nIp var '".$user_ip."'\r\n";
            return false;
        }

        echo "User is now login: ".$row['nick']."\r\n";
        $this->isLogin = true;
        $this->user = $row;

        $this->channel[1]['uid'] = $this->user['user_id'];

        $this->get_user_config();

        return true;
    }

    function read($length = 1024){
        return socket_read($this->socket, $length);
    }

    function write($msg,$ishandshake = false,$mask = true){
        if(!$this->isLogin && !$ishandshake){
            echo "Prøvede at skrive til en som ikke er logget ind ";
            return false;
        }

        //vi kontrollere nu at brugeren ikke er bannet i denne channel :)
        if(empty($this->channel[$msg['message'][0]['cid']]) || $this->channel[$msg['message'][0]['cid']]['ban'] == Yes)
            return false;

        $msg['message'][0]['message'] = htmlentities($msg['message'][0]['message']);
        $msg = json_encode($msg);

        if($mask){
            $msg = $this->mask($msg);
        }

        socket_write($this->socket,$msg,strlen($msg));
        return true;
    }

    function ip(){
        if(!empty($this->data['ip'])){
            return $this->data['ip'];
        }

        socket_getpeername($this->socket,$ip);
        $this->data['ip'] = $ip;
        return $this->data['ip'];
    }

    function disconnect(){
        socket_close($this->socket);
    }

    function get(){
        $respons = socket_recv($this->socket, $buf, 1024, 0);

        if($respons >= 1){
            return false;
        }

        return $this->unmask($buf);
    }

    function message($data){
        Server::$message_id++;
        $this->write(array(
            'message' => array(
                array(
                    'id' => Server::$message_id,
                    'message' => $data['message'],
                    'isMsg'   => No,
                    'msgTo'   => 0,
                    'messageColor' => $data['color'],
                    'isBot'        => No,
                    'nick'         => $data['nick'],
                    'time'         => time(),
                    'cid'          => $data['cid'],
                    'channel'      => $data['channel'],
                    'cmid'         => 0,
                    'uid'          => $data['uid'],
                    'img'          => $data['img'],
                    'isPriv'       => No,
                    'privUid'      => 0
                ),
            ),
        ));
    }

    function msg($data){
        Server::$message_id++;
        if(!$this->isLogin){
            echo "User is not login !\r\n";
            return;
        }
        $this->write(array(
            'message' => array(
                array(
                    'id'           => Server::$message_id,
                    'message'      => $data['message'],
                    'isMsg'        => Yes,
                    'msgTo'        => $this->user['user_id'],
                    'messageColor' => 'yellow',
                    'isBot'        => No,
                    'nick'         => $data['nick'],
                    'time'         => time(),
                    'cid'          => $data['cid'],
                    'channel'      => $data['channel'],
                    'cmid'         => 0,
                    'uid'          => $data['uid'],
                    'img'          => $data['img'],
                    'isPriv'       => No,
                    'privUid'      => 0
                )
            )
        ));
        //message is now sendt ;)
    }

    function bot_message($data){
        Server::$message_id++;
        $this->write(array(
            'message' => array(
                array(
                    'id'           => Server::$message_id,
                    'message'      => $data['message'],
                    'isMsg'        => No,
                    'msgTo'        => 0,
                    'messageColor' => $data['color'],
                    'isBot'        => Yes,
                    'nick'         => $data['nick'],
                    'time'         => time(),
                    'cid'          => $data['cid'],
                    'channel'      => $data['channel'],
                    'cmid'         => 0,
                    'uid'          => $data['uid'],
                    'img'          => $data['img'],
                    'isPriv'       => No,
                    'privUid'      => 0,
                ),
            ),
        ));
    }

    function unmask($text) {
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

    function update_user_config($key,$value){
        $this->user_config[$key] = $value;
    }

    private function get_user_config(){
        $sql = $this->mysql->query("SELECT * FROM `".DB_PREFIX."chat_userConfig` WHERE `uid`='".$this->user['user_id']."'");
        while($row = $sql->get()){
            $this->user_config[$row['key']] = $row['value'];
        }

        return $this->user_config;
    }

    private function cookie($name){
        if(empty($this->cookie[$name]) || !trim($this->cookie[$name])){
            return null;
        }

        return $this->cookie[$name];
    }

    private function parseCookie($string){
        $block = explode(";",$string);
        $return = array();
        for($i=0;$i<count($block);$i++){
            if(preg_match("/^(.*?)=(.*?)$/",$block[$i],$c)){
                $return[trim($c[1])] = trim($c[2]);
            }else{
                exit("Unknown cookie string");
            }
        }
        return $return;
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

$socket = true;
include 'server.php';