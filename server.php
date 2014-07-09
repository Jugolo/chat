<?php
 class Server{
     
     protected $config;
     private $variabel         = array();
     private $user             = array();
     private $userConfig       = array();
     private $cid              = null;
	 private $lang             = array();
	 private $websocket        = false;
	 private $client           = array();
	 private $clientObj        = array();
	 private $postData         = array();
	 public static $message_id = 0;
     private $basepart         = null;
     public static $mysql      = null;
     private $sConfig          = array();
     private $protokol         = null;
     private $channel          = array();

     const text_max = 1;
     const text_min = 2;
     
    function Server($websocket = false){
    	
    	header("Expires: Mon, 26 Jul 12012 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
    	
		$this->websocket = $websocket;

        $this->loadPages();
        $this->init_db();
        $this->init_system_setting();
        $this->init_lang();
		$this->loadVariabel();
        $this->loadDatabaseConfig();
        $this->init_protokol();
        if($this->protokol->use_session){
            $this->sessionInit();
        }

        $this->channel = $this->protokol->get_channel_list(false);

        if(!$this->websocket){
            $this->userInit();
        }
        
		if($this->websocket){
			$this->init_websocket();
		}else{
			if($this->getConfig("protokol") == "socket"){
				exit("This is not ajax webserver but WebSocket server!");
			}
		}
		
		//ajax only ;) if it is not ajax it will not work!
		if($this->getVariabel("isPost")){
        	$this->handlePost();
        }else{
        	$this->showMessage();
        }
        
        Json::show();
    }

	 private function init_websocket(){

         if(!function_exists("socket_create")){
             exit("Missing socket create!");
         }

         if(($master = socket_create(AF_INET,SOCK_STREAM,SOL_TCP)) === false){
             exit("Denaid to create socket!");
         }

         if(socket_set_option($master,SOL_SOCKET,SO_REUSEADDR,1) === false){
             exit("Deinad to create socket");
         }

         if (!filter_var($this->getConfig("socketServer"), FILTER_VALIDATE_IP)) {
             $this->config['socketServer'] = gethostbyname($this->getConfig("socketServer"));
         }

         if(@socket_bind(
             $master,
             $this->getConfig("socketServer"),
             $this->getConfig("socketPort")
         ) === false){
             exit("Failt to bind socket");
         }

		 if(socket_listen($master,20) === false){
             exit("Fail to listen socket");
         }

         $this->add_socket_client($master);

         while($this->websocket){
             //vi beder om client :)
             foreach($this->client AS $socket){
                 $konto = $this->get_client($socket);

                 try{
                     $konto->handle_inaktiv_or_leave_status(
                         $this->getConfig("inaktiv"),
                         $this->getConfig("leave")
                     );
                 }catch (Exception $e){
                     switch($e->getMessage()){
                         case 'inaktiv':
                             $this->sendBotMessage(
                                 $e->getCode(),
                                 "/inaktiv ".$konto->user['nick'],
                                 false,
                                 $konto->user['user_id']
                             );
                         break;
                         case 'leave':
                             $this->sendBotMessage(
                                 $e->getCode(),
                                 '/leave',
                                 false,
                                 $konto->user['user_id']
                             );
                             $this->sendBotPrivMessage(
                                 1,
                                 "/leave ".$this->protokol->get_channel_by_id($e->getCode()),
                                 "red",
                                 $konto->user['user_id']
                             );
                             $this->kick(
                                 $e->getCode(),
                                 null,
                                 $konto->user['user_id'],
                                 false
                             );

                          break;
                     }
                 }
             }

             $read = $this->client;
             $write = $ex = null;

             @socket_select($read,$write,$ex,null);

             foreach($read AS $socket){
                 if($socket == $master){
                     $client = socket_accept($socket);
                     if($client < 0){
                         echo "Error to accept socket!";
                         continue;
                     }else{
                         $this->handle_new_connect($client);
                     }
                     continue;
                 }

                 $konto = $this->get_client($socket);

                 if(!$konto->my_turn()){
                     $this->remove_client($socket);
                 }

                 $recv = @socket_recv($socket,$buf,1024,0);
                 if($recv === false || $recv == 0){
                     $this->remove_client($socket);
                     continue;
                 }

                 $message = $konto->unmask($buf);
                 if(!$message || $message == "undefined"){
                     continue;
                 }

                 $this->postData = @json_decode($message,true);
                 if(@json_last_error() != JSON_ERROR_NONE){
                     $this->remove_client($socket);
                     continue;
                 }


                 $this->variabel['client'] = $konto;
                 if($konto->isLogin)
                     $this->protokol->turn($konto->user['user_id']);
                 $this->re_cache_channel_id($this->postData['channel']);
                 $this->handlePost();

             }
         }
     }
	 
	 private function handle_new_connect($new){
		 $user =  $this->add_socket_client($new);
		 
		 $head = array();
		 //handshake :)
		 $lines = explode("\r\n",$user->read());
		 for($i=0;$i<count($lines);$i++){
			 $line = trim($lines[$i]);
			 if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
				 $head[$matches[1]] = $matches[2];
			 }
		 }

         if(empty($head['Sec-WebSocket-Key'])){
             $this->remove_client($new);
             echo "Missing Sec-WebSocket-Key\r\n";
             print_r($head);
             return false;
         }

		 $key  = $head['Sec-WebSocket-Key'];
		 $hkey = base64_encode(sha1($key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		 
		 $uhead = array();
		 
		 $uhead[] = "HTTP/1.1 101 Web Socket Protocol Handshake";
		 $uhead[] = "Upgrade: websocket";
		 $uhead[] = "Connection: Upgrade";
		 $uhead[] = "Sec-WebSocket-Accept: ".$hkey;

         $handshake = implode("\r\n",$uhead)."\r\n\r\n";
         //exit($handshake);

         if(socket_write($new,$handshake,strlen($handshake))===false){
             exit("Handshake fail");
         }
         echo "New client connected to server\r\n";
	 }
	 
	 private function remove_client($socket){
		 $i = array_search($socket,$this->client);
		 if(empty($i)){
			 return false;
		 }
		 $this->clientObj[$i]->disconnect();
         $this->clientObj = $this->reset_array_sort($this->clientObj,$i);
         $this->client    = $this->reset_array_sort($this->client,$i);
         echo "Client disconetet\r\n";
		 
		 return true;
	 }

     private function reset_array_sort($array,$removeId = null){
         $cache = $array;
         $array = array();
         for($i=0;$i<count($cache);$i++){
             if($removeId !== null && $i == $removeId){
                 continue;
             }
             $array[] = $cache[$i];
         }

         return $array;
     }
	 
	 private function add_socket_client($client){
		 $this->client[]    = $client;
		 $this->clientObj[] = $obj = new socket_user_client($client);
		 return $obj;
	 }

     private function get_client($socket){
         foreach($this->clientObj as $c){
             if($c->socket == $socket){
                 return $c;
             }
         }

         return false;
     }
	 
	 private function init_lang(){
         $locale = array();
         if($this->websocket)
             $langUse = $this->get_base_part()."locale\\".str_replace("/",null,$this->get_system_config("locale"))."_server.php";
         else
             $langUse = "locale/".str_replace("/",null,$this->get_system_config("locale")."_server.php");
		 if(file_exists($langUse)){
			 include $langUse;
			 $this->lang = $locale;
		 }else{
             if($this->websocket){
                 exit("Include lang fail!. control you server settings!");
             }
			 Json::location("../../index.php");
		 }
	 }


     private function getLangList(){
         $return = array();
         if($this->websocket)
             $dir = $this->get_base_part()."locale\\";
         else
             $dir = "locale/";

         $dirObj = opendir($dir);
         while($file = readdir($dirObj)){
             if($file != "." || $file != ".."){
                 if(!preg_match("/^[a-zA-Z]*?_server\.php$/",$file) && !preg_match("/^index\.php$/",$file) && is_file($dir.$file)){
                     $return[] = str_replace(".php",null,$file);
                 }
             }
         }

         return $return;
     }
    
    //inaktiv sektion
    private function handle_inaktiv($row){
        if($row['isInAktiv'] == Yes){
            $this->do_leave($row);
        }else{
         $this->do_inaktiv($row);
        }
    }
    
    private function do_leave($row){
        $this->sendBotMessage(
            $row['cid'],
            '/leave',
            false,
            $row['uid']
        );
    	mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_member` WHERE `id`='".(int)$row['id']."'");
        $this->sendBotPrivMessage(
            1,
            "/leave ".$row['name'],
            "red",
            $row['uid']
        );
    }
    
    private function do_inaktiv($row){
		mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_member` SET `isInAktiv`='".Yes."' WHERE `id`='".$row['id']."'");
    	$this->sendBotMessage(
            $row['cid'],
            "/inaktiv ".$row['nick'],false,$row['uid']
        );
    }
    
    //message sektion
    private function showMessage(){
        //vi sletter nu pong beskeder som er mere end 1 min gammel :)
        mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_message` WHERE `cid`='1' AND `message`='/pong' AND DATE_SUB(time, INTERVAL 1 MINUTE) > NOW()");
        if(self::$mysql->error){
            exit(self::$mysql->error);
        }

        //big sql :D
		$sql = mysqli_query(self::$mysql,"SELECT tm.id AS id, tm.message AS message, tm.isMsg AS isMsg, tm.msgTo AS msgTo, tm.messageColor AS messageColor, tm.isBot AS isBot, user.nick AS nick, tm.time AS time, tn.id AS cid, tn.name AS channel, cm.id AS cmid, user.user_id AS uid, user.user_avatar AS img, tn.isPriv AS isPriv, tn.uid AS privUid
		FROM ".DB_PREFIX."chat_message AS tm
		LEFT JOIN ".DB_PREFIX."chat_member AS cm ON tm.cid = cm.cid
		LEFT JOIN ".DB_PREFIX."users AS user ON user.user_id = tm.uid
		LEFT JOIN ".DB_PREFIX."chat_name AS tn ON tn.id = cm.cid
		WHERE cm.uid = '".(int)$this->protokol->user['user_id']."'
		AND tm.id > '".(int)$this->getVariabel("last_id")."'
		AND cm.ban <> '".Yes."'
		ORDER BY tm.id ASC ");

        if(self::$mysql->error){
            exit(self::$mysql->error);
        }

    	$message = array();
    	while($row = mysqli_fetch_array($sql)){
    		if($row['message'] == null){
    			continue;
    		}

            if($this->may_show_may_show_message($row)){
                $row['time'] = date("H:i",strtotime($row['time']));
                $message[] = $this->messageMakeSafe($row);
            }
    	}
    	
    	Json::addBlock("message", $message);

        $sql = mysqli_query(self::$mysql,"SELECT cm.isInAktiv, cm.id, cm.uid , us.nick, cm.cid, cn.name
        FROM `".DB_PREFIX."chat_member` AS cm
        LEFT JOIN `".DB_PREFIX."users` AS us ON us.user_id = cm.uid
        LEFT JOIN `".DB_PREFIX."chat_name` AS cn ON cn.id=cm.cid
        WHERE cm.cid != '1'
         AND cm.ban = '".No."'
          AND cm.lastActiv < DATE_SUB( now( ) , INTERVAL ".(int)$this->config['inaktiv']." MINUTE )
           AND cm.isInAktiv = '".No."'
        OR cm.cid != '1'
         AND cm.ban = '".No."'
          AND cm.lastActiv < DATE_SUB( now( ) , INTERVAL ".(int)$this->config['leave']." MINUTE )
           AND cm.isInAktiv = '".Yes."'"
        );

        while($row = mysqli_fetch_array($sql))
            $this->handle_inaktiv($row);
    }

    private function may_show_may_show_message($data){
        if(in_array($data['uid'],$this->protokol->get_ignore()) && $data['isBot'] == No){
            return false;
        }

        if($data['isMsg'] == Yes){
            if(
                $data['uid'] != $this->protokol->user['user_id']
                && $data['msgTo'] != $this->protokol->user['user_id']
            ){
                return false;
            }
        }

        return true;
    }
    
    private function messageMakeSafe($row){
    	$row['message'] = $this->remove_bad_words(htmlentities($row['message']));
    	$row['time'] = htmlentities($row['time']);
    	$row['nick'] = htmlentities($row['nick']);
    	if(empty($row['img'])){
    		$row['img'] = "noavatar150.png";
    	}
    	$row['img'] = "../../images/avatars/".$row['img'];
    	return $row;
    }

    private function remove_bad_words($word){
        if($this->sConfig['bad_words_enabled'] != '1'){
            return $word;
        }

        $block = explode("\r\n",$this->sConfig["bad_words"]);
        for($i=0;$i<count($block);$i++){
            if($block[$i] == ""){
                continue;
            }

            $word = preg_replace("/".$block[$i]."/si", $this->sConfig['bad_word_replace'], $word);
        }

        return $word;
    }
    
    private function getUserIdFromNick($nick){
    	$sql = mysqli_query(self::$mysql,"SELECT `user_id` FROM `".DB_PREFIX."users` WHERE BINARY `nick`='".mysqli_escape_string(self::$mysql,$nick)."'");
    	$row = mysqli_fetch_array($sql);
    	return (empty($row['user_id']) ? 0 : $row['user_id']);
    }
    
    //channel sektion

     protected function re_cache_channel_id($name){
         $myChannels = $this->protokol->get_my_channel_list();
         $channel    = $this->protokol->get_channel_list();

         //og vi får resultatet hurtigt da MySQL ikke er indblandet :D

         foreach($channel as $cid => $data){
             if($data['name'] == $name){
                 //vi har fundet den. nu er spøgsmålet bare om brugeren er medlem i den :)
                 $this->variabel['cid'] = $this->cid = empty($myChannels[$cid]) ? 1 : $cid;
                 return $this->cid;
             }
         }

         $this->variabel['cid'] = $this->cid = 1;

         return $this->cid;
     }

    protected function getCidFromChannel($name,$cache=true){
    	
    	if($this->cid != null && $cache){
    		return $this->cid;
    	}

        $use = 1;//1 is bot channels :)

        //vi skal nu finde ud af id fra name ;)
        foreach($this->channel as $cid => $array){
            if(trim($array['name']) == trim($name)){
                $use = $cid;
                break;//stop denne løkke :)
            }
        }

        //vi har nu cid nu skal vi bare have en alle mine channels
        $my_channel = $this->protokol->get_my_channel_list();

        $found = false;
        foreach($my_channel as $id => $data){
            if($use != 1 && $use == $data['cid']){
                $found = true;
                break;
            }
        }

        if(!$found)
            $use = 1;

        if($cache){
            $this->variabel['cid'] = $this->cid = $use;
        }

        return $use;
    }
    
    //post sektion
	 
	 private function init_get_data(){
		 if($this->websocket){
             if(!is_array($this->postData)){
                 return $this->remove_client($this->getVariabel("client")->socket);
             }
			 return array_merge($this->postData,array(
               'li' => 0,
             ));
		 }else{
			 return array(
				 'message' => $this->post("message"),
				 'channel' => $this->post("channel"),
				 'li'      => $this->get("li"),
			 );
		 }
	 }
	 
    private function handlePost(){
		
		$input = $this->init_get_data();

        //WebSocket has a problem. bannet user can write in the channel soo wee control it now :)
        if($this->getVariabel("cid") != 1 && in_array($this->protokol->user['user_id'],$this->protokol->getBannetInChannel($this->getVariabel("cid")))){
            return;
        }

    	if(preg_match("/^\//", $input['message'])){
    		$this->handleCommand();
            if($this->getVariabel("last_id")){
                $this->showMessage();
            }
    	}else{
            if($this->is_flood($this->getVariabel("cid"))){
                if($input['message']){
                    $this->handleMessage($input);
                }

                if($input['channel']){
                    $this->updateActivInChannel($input['channel']);
                }
            }else{
                $this->sendBotPrivMessage(
                   $this->getVariabel("cid"),
                   "/maxFlood",
                    'red'
                );
            }

            if($this->getVariabel("last_id")){
                $this->showMessage();
            }else{
                Json::addBlock("message",array());
            }
        }
    }

    private function is_flood($cid){
        $flood = $this->protokol->get_flood($cid);


        $count = 0;
        $new_flood = array();
        for($i=0;$i<count($flood);$i++){
            $time = $flood[$i];
            if($time < strtotime("-1 minutes")){

            }else{
                $count++;
                $new_flood[] = $flood[$i];
            }
        }

        //vi indsætter en for denne :)
        $count++;

        if($count <= (int)$this->sConfig["flood_interval"]){
            $new_flood[] = time();

            $this->protokol->update_flood($new_flood,$cid);
            return true;
        }else{
            return false;
        }
    }
    
    private function updateActivInChannel(){
        if($this->websocket){
            //det er websocket ;)
            /**
             * @todo Update post for websocket ;)
             */
            return;
        }

        $sql = mysqli_query(self::$mysql,"SELECT *
        FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".(int)$this->protokol->user['user_id']."'
        AND `cid`='".(int)$this->getVariabel("cid")."'");

    	$row = mysqli_fetch_array($sql);
    	
    	if($row['isInAktiv'] == Yes){
			$this->sendBotMessage($row['cid'], "/notInaktiv ".$this->protokol->user['nick'], 'green');
    	}
    	
    	mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_member` SET `lastActiv`= NOW(), `isInAktiv`='".No."' WHERE `cid`='".(int)$row['cid']."' AND `uid`='".$this->protokol->user['user_id']."'");
    }
    
    private function handleMessage($data){
		if($this->getVariabel("cid") == 1){
            //exit("Can not send to server 1!"); Fail from my side :( user can not send join command if user is not allow to write to bot channel :)
			return;
		}

        if($this->websocket){

            $this->send_message_to_users($data['message']);
            return;//websocket sender direkte til brugerenerne så vi har ikke brug for denne del :)
        }
    	mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `messageColor`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".(int)$this->protokol->user['user_id']."',
                '".(int)$this->getVariabel("cid")."',
                '".No."',
                NOW(),
                '".mysqli_escape_string(self::$mysql,$this->post("message"))."',
                '".mysqli_escape_string(self::$mysql,$this->userConfig['textColor'])."',
                '".No."',
                '0'
                )");
    }

    private function get_user_id_from_nick($nick){
        $sql = mysqli_query(self::$mysql,"SELECT `user_id` FROM `".DB_PREFIX."users` WHERE `nick`='".mysqli_escape_string(self::$mysql,$nick)."'");
        if(self::$mysql->error){
            exit(self::$mysql->error);
        }

        $row = mysqli_fetch_array($sql);

        if(!empty($row['user_id']))
            return $row['user_id'];
        else
            return false;
    }
    
    private function returnCommandName(){
		$data = $this->init_get_data();
        $ex = explode(" ",$data['message']);
        return preg_replace("/^\//",null,$ex[0]);
    }
    
    private function handleCommand(){
    	switch($this->returnCommandName()){
            case 'cookie':
                if(!$this->websocket){
                    exit("ERROR");
                }
                $input = $this->init_get_data();

                if(!$this->getVariabel("client")->login(str_replace("/cookie ",null,$input['message']))){
                    $this->remove_client($this->getVariabel("client")->socket);
                }else{
                    $this->getVariabel("client")->user['user_avatar'] = $this->convert_image(
                        $this->getVariabel("client")->user['user_avatar']
                    );
                    $this->sendBotPrivMessage(1,"/cookieOkay","green");
                    $this->protokol->set_user_data($this->getVariabel("client")->user,$this->getVariabel("client"));
                }
            break;
    		case "getStatus":
    			return $this->answer_getStatus();
    		break;
            case "join":
                return $this->answer_join();
    		break;
            case "nick":
            	return $this->answer_nick();
            break;
            case 'msg':
            	return $this->answer_msg();
            break;
            case "config":
            	return $this->answer_config();
            break;
            case 'getOnline':
            	return $this->do_getOnline();
            break;
            case 'title':
            	return $this->doTitle();
            break;
            case 'exit':
            	return $this->doExit();
            break;
			case 'getLang':
			    return $this->answer_getLang();
			break;
			case 'leave':
			    return $this->answer_leave();
			break;
			case 'kick':
				return $this->answer_kick();
			break;
			case 'bot':
			     return $this->answer_bot();
			break;
			case 'ban':
			     return $this->answer_ban();
			break;
			case 'unban':
			     return $this->answer_unban();
			break;
            case 'ignore':
                  return $this->answer_ignore();
            break;
            case 'unIgnore':
                  return $this->answer_uningore();
            break;
            case 'ping':
                $this->sendBotPrivMessage(1,"/pong","green");
            break;
            case 'update':
                return $this->answer_update();
            break;
            default:
            	$this->sendBotPrivMessage($this->getCidFromChannel($this->post("channel")), "/commandDenaid");
            break;
    	}
    }

     private function answer_update(){
         $input = $this->init_get_data();
         if(!$this->iADMIN() && !$this->iSUPERADMIN()){
             //has nothing to do here idiot :()
             $this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/update"));
             return;
         }

         //system config :)
         $this->sConfig = array();
         $this->init_system_setting();

         //database config :)
         $this->config = array();
         $this->loadDatabaseConfig();

         if($this->getConfig("protokol") != "socket" && $this->websocket){
             exit("Server gooinh down!");
         }

         //okay now wee can tell user this system is now updatet :)
         $this->sendBotPrivMessage(
             $this->getVariabel("cid"),
             $this->lang['systemUpdatet'],
             "green"
         );
     }

     private function answer_uningore(){
         $input = $this->init_get_data();
         if(preg_match("/^\/unIgnore\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             if(($uid = $this->get_user_id_from_nick($reg[1])) !== false){
                 if(!in_array($uid,$this->protokol->get_ignore())){
                     return $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['isNotIgnore']);
                 }
                 $this->protokol->remove_ignore($uid);
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/unIgnore ".$reg[1],"green");
             }else{
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['userNotFound']);
             }
         }else{
             $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['invalidCommand'],"unIgnore"));
         }
     }

     private function answer_ignore(){
         $input = $this->init_get_data();
         if(preg_match("/^\/ignore\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             if(($uid = $this->get_user_id_from_nick($reg[1])) !== false){
                 if(in_array($uid,$this->protokol->get_ignore())){
                   return $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['isIgnore'],"red");
                 }
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/ignore ".$reg[1]);
                 $this->protokol->add_ignore($uid);
             }else{
                 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['userNotFound']);
             }
         }else{
             $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['invalidCommand'],"ignore"));
         }
     }
	 
	 private function answer_unban(){
		 
		 $input = $this->init_get_data();
		 
		if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getCidFromChannel($input['channel']), "/error ".sprintf($this->lang['accessDenaidKommando'],"/unban"));
			return;
		} 
		 
		 if(preg_match("/^\/unban\s([a-zA-Z]*?)$/",$input['message'],$reg)){
             $userData = $this->protokol->getUserInChannel($this->getVariabel("cid"),$reg[1]);
             if($userData === false){
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".$this->lang['userNotFound']
                 );
                 return;
             }

             if(in_array($userData['user_id'],$this->protokol->getBannetInChannel($this->getVariabel("cid")))){
                 $this->protokol->remove_ban(
                     $userData['user_id'],
                     $this->getVariabel("cid"),
                     $this->protokol->getBanId($userData['user_id'],$this->getVariabel("cid"))
                 );
                 $this->sendBotMessage(
                     $this->getVariabel("cid"),
                     $input['message'],
                     "green"
                 );
             }else{
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".sprintf($this->lang['notBan'],$reg[1])
                 );
             }
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['unbanBroken']);
		 }
	 }
	 
	 private function answer_ban(){
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/ban"));
			return;
		}		 
		 
		 $input = $this->init_get_data();
		 
		 if(preg_match("/^\/ban\s([a-zA-Z]*?)\s([0-9]*?)$/",$input['message'],$reg)){
			 //vi sætter nu tiden frem til den tidspunkt brugeren ikke længere er bannet ;)
			 $to = strtotime("+".$reg[2]." minutes",time());

             $userData = $this->protokol->getUserInChannel(
                 $this->getVariabel("cid"),
                 trim($reg[1])
             );

             if($userData !== false && is_array($userData)){
                 $this->ban(
                     $input['channel'],
                     $userData['user_id'],
                     $to,
                     trim($reg[1])
                 );
             }else{
                 $this->sendBotPrivMessage(
                     $this->getVariabel("cid"),
                     "/error ".sprintf($this->lang['nickNotFound'],$reg[1])
                 );
             }
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['banBroken']);
		 }
	 }
	 
	 private function answer_bot(){
		 
		 $input = $this->init_get_data();
		 
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getCidFromChannel($input['channel']), "/error ".sprintf($this->lang['accessDenaidKommando'],"/bot"));
			return;
		}

		 if(preg_match("/^\/bot\s(.*?)$/",$input["message"],$reg)){
			 $this->sendBotMessage($this->getVariabel("cid"),$reg[1],($this->websocket ? $this->getVariabel("client")->user_config['textColor'] : $this->userConfig['textColor']));
		 }else{
			 $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['botBroken']);
		 }		 
	 }
	 
	 private function answer_leave(){
		 if($this->getVariabel("cid") == 1){
			 return;
		 }
		 
		 mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_member` WHERE `cid`='".(int)$this->getVariabel("cid")."' AND `uid`='".($this->websocket ? $this->getVariabel("client")->user['user_id'] : $this->user['user_id'])."'");
         if(self::$mysql->error){
             exit(self::$mysql->error);
         }
		 //vi skriver til channel at brugeren har forladt channel ;)
         $input = $this->init_get_data();

         if($this->websocket){
             unset($this->getVariabel("client")->channel[$this->getVariabel("cid")]);
             unset($this->getVariabel("client")->aktiv[$this->getVariabel("cid")]);
         }

         $this->sendBotMessage($this->getVariabel("cid"), "/leave ".$input['channel'],"red",($this->websocket ? $this->getVariabel("client")->user['user_id'] : $this->user['user_id']));
	 }
	 
    private function answer_kick(){
    	if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/kick"));
			return;
		}

        $input = $this->init_get_data();
		
		if(preg_match("/^\/kick\s([a-zA-Z]*?)\s(.*?)$/",trim($input['message']),$reg)){
			//vi har nu en kick med message
			if(($uid = $this->getUserIdFromNick($reg[1])) != 0){
			   $this->kick($input['channel'],$reg[2],$uid);
			}else{
			   $this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['kickBroken']);	
			}
		}elseif(preg_match("/^\/kick\s([a-zA-Z\s]*?)$/", trim($input['message']),$reg)){
			if(($uid = $this->getUserIdFromNick($reg[1])) != 0){
				$this->kick($input['channel'],null,$uid);
			}else{
				$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['nickNotFound'],$reg[1]));
			}
		}else{
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['kickBroken']);
		}
    }
    
	private function answer_getLang(){
		$this->sendBotPrivMessage(1,"/langList ".implode(",",$this->getLangList()));
	}
    
    private function doExit(){

        foreach($this->protokol->get_my_channel_list() AS $cid => $data){
            $this->sendBotMessage(
                $this->getVariabel("cid"),
                "/exit"
            );
        }

        if($this->websocket){
            $this->remove_client($this->getVariabel("client")->socket);
        }
    }
    
    private function doTitle(){
    	
		if(!$this->iADMIN() && !$this->iSUPERADMIN()){
			//has nothing to do here idiot :()
			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".sprintf($this->lang['accessDenaidKommando'],"/title"));
			return;
		}
		
		$input = $this->init_get_data();
		
    	if(preg_match("/^\/title\s(.*?)$/",$input['message'],$reg)){
			mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_name` SET `title`='".mysqli_escape_string(self::$mysql,$reg[1])."', `setTitle`='".($this->websocket ? (int)$this->getVariabel("client")->user['user_id'] : (int)$this->user['user_id'])."' WHERE `id`='".(int)$this->getVariabel("cid")."'");
    		$this->sendBotMessage($this->getVariabel("cid"), $reg[0],"green");
    	}else{
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['titleBroken']);
    	}
    }
    
    private function do_getOnline(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/getOnline\s(.*?)$/", $input["message"],$reg)){
            $this->sendBotPrivMessage(
                $this->getVariabel("cid"),
                "/online ".implode(" ", $this->getOnline($this->getCidFromChannel($reg[1],true),true))
            );
    	}else{
            exit($input['message']);
        }
    }
    
    //config
    private function answer_config(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/config\s\[(.*?)\]\[(.*?)\]$/", $input['message'],$reg)){
            $isConfig = false;
    		switch(trim($reg[1])){
                case 'time':
    			case 'sound':
                case 'textColor':
                case 'lang':
			     	mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_userConfig` SET `value`='".mysqli_escape_string(self::$mysql,$reg[2])."' WHERE `uid`='".(int)$this->protokol->user['user_id']."' AND `key`='".mysqli_escape_string(self::$mysql,$reg[1])."'");
                    if(self::$mysql->error){
                        exit(self::$mysql->error);
                    }
                    if($this->websocket){
                        $this->getVariabel("client")->update_user_config($reg[1],$reg[2]);
                    }
    				$this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        $this->lang['configUpdatet'],
                        "green"
                    );

                    $this->sendBotPrivMessage(
                        1,
                        "/updateConfig ".$reg[1]." ".$reg[2],
                        'black'
                    );
                break;
    			default:
    				$this->sendBotPrivMessage($this->getVariabel("cid"), "/error Wrong key: ".$reg[1],"red");
    			break;
    		}
    	}else{
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['brokenConfigCommand'], "red");
    	}
    }
    
    //msg
    private function answer_msg(){
    	$cid = $this->getVariabel("cid");
		$input = $this->init_get_data();
    	//vi deler stringen op ;)
    	if(preg_match("/^\/msg\s(.*?)\s(.*?)$/", $input['message'],$reg)){
    		//vi ser om vi kan finde en bruger med det nick ;)
			$sql = mysqli_query(self::$mysql,"SELECT user_id AS id,`nick` FROM `".DB_PREFIX."users` WHERE `nick`='".mysqli_escape_string(self::$mysql,$reg[1])."'");
    		$row = mysqli_fetch_array($sql);
    		if(!empty($row['id'])){
				
				if($this->websocket){
					for($i=0;$i<count($this->clientObj);$i++){
						if($this->clientObj[$i]->isLogin && $this->clientObj[$i]->user['user_id'] == $row['id']){
							$this->clientObj[$i]->msg(array(
								'cid'     => $cid,
								'message' => "/msg ".$this->getVariabel("client")->user['nick']." -> ".$this->clientObj[$i]->user['nick'].": ".$reg[2],
								'nick'    => $this->getVariabel("client")->user['nick'],
								'channel' => $input['channel'],
								'uid'     => $this->getVariabel("client")->user['user_id'],
								'img'     => $this->getVariabel("client")->user['user_avatar']
							));
							//vi skal også sende det til afsenderen :)
							$this->getVariabel("client")->msg(array(
								'cid'     => $cid,
								'message' => "/msg ".$this->getVariabel("client")->user['nick']." -> ".$this->clientObj[$i]->user['nick'].": ".$reg[2],
								'nick'    => $this->getVariabel("client")->user['nick'],
								'channel' => $input['channel'],
								'uid'     => $this->getVariabel("client")->user['user_id'],
								'img'     => $this->getVariabel("client")->user['user_avatar']
							));
							return;
						}
					}
					
					$this->sendBotPrivMessage($cid, "/error ".sprintf($this->lang['noNick'], $reg[1]),"red");
					return;
				}
				
				mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `messageColor`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".(int)$this->user['user_id']."',
                '".(int)$cid."',
                '".No."',
                NOW(),
                '".mysqli_escape_string(self::$mysql,"/msg ".$this->user['nick']." -> ".$row['nick'].": ".$reg[2])."',
                'yellow',
                '".Yes."',
                '".(int)$row['id']."'
                )");
    		}else{
    			$this->sendBotPrivMessage($cid, "/error ".sprintf($this->lang['noNick'], $reg[1]),"red");
    		}
    	}else{
    		$this->sendBotPrivMessage($cid, "/error broken /msg","red");
    	}
    }
    
    //nick
    private function answer_nick(){
		$input = $this->init_get_data();
    	if(preg_match("/\/nick\s([a-zA-Z0-9]*?)$/", $input['message'],$reg)){
    		if(!$this->nickKontrol($reg[1])){
    			$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['nickTaken'], "red");
    			return;
    		}

            if(($code = $this->is_length_okay($reg[1],1,$this->getConfig("maxNickLengt"))) !== true){
                if($code == self::text_min){

                }elseif($code == self::text_max){
                    $this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        '/error '.$this->lang['maxNick'],
                        'red'
                    );
                }
            }else{
                //wee control if user try to change nick to his nick o.O
                if(strtolower($this->protokol->user['nick']) == strtolower($reg[1])){
                    $this->sendBotPrivMessage(
                        $this->getVariabel("cid"),
                        '/error '.sprintf($this->lang['nickIsYour'],$this->protokol->user['nick']),
                        'red'
                    );
                    return;
                }
                $oldNick = $this->protokol->user['nick'];
                mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."users` SET `nick`='".mysqli_escape_string(self::$mysql,$reg[1])."' WHERE `user_id`='".$this->protokol->user['user_id']."'");
                $this->protokol->update_nick($reg[1]);
                if($this->websocket){
                    foreach($this->getVariabel("client")->channel as $id => $name){
                        $this->sendBotMessage(
                            $id,
                            '/nick '.$oldNick,
                            'green'
                        );
                    }
                }else{
                    $sql = mysqli_query(self::$mysql,"SELECT `cid` FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$this->protokol->user['user_id']."' AND `cid`<>'1'");
                    while($row = mysqli_fetch_array($sql)){
                        $this->sendBotMessage($row['cid'], "/nick ".$oldNick,"green");
                    }
                }
            }
        }else{
    		Json::addBlock("isOkay", "false");
    		$this->sendBotPrivMessage($this->getVariabel("cid"), "/error ".$this->lang['nickBroken'], "red");
    	}
    }
    
    //join
    private function answer_join(){
		$input = $this->init_get_data();
    	if(preg_match("/^\/join #([a-zA-Z0-9]*?)$/", $input['message'], $reg)){
			if(!$this->parseJoinChannelName($reg[1])){
				$this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['invalidJoin']);
				return;
			}
    		$channelName = "#".$reg[1];

            //okay vi finder nu channel :)
            $channel_data = null;

            foreach($this->channel AS $cid => $data){
                if($data['name'] == $channelName){
                    $data['id'] = $cid;
                    $channel_data = $data;
                    break;
                }
            }

            //vi skal nu se om vi kan finde brugeren i channel :)
            $is_found = false;
            $my_chan  = $this->protokol->get_my_channel_list();
            $data = null;

            foreach($my_chan AS $id => $cData){
                if($channel_data['id'] == $cData['cid'] && $cData['cid'] != 1){
                    $is_found = true;
                    $data = $this->joinUser($channelName,$cData);
                    break;
                }
            }

            if($is_found && $data === false){
                return;//hmmm
            }

            if(!$is_found && $channel_data === null){
                $data = $this->joinUserInNewChannel($channelName);
                $channel_data = $data;
            }elseif($channel_data !== null && $channel_data['id'] != 1 && empty($data)){
                //vi har channel men brugeren er ikke inde i den ;) derfor skal vi nu smide brugeren der ind :D
                $data = $this->join_user_in_channel($channel_data);
            }else{
                $this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".sprintf($this->lang['isMember'],$channelName));

                return;
            }

            if($data === false){
                return;
            }

    		$this->sendBotPrivMessage((int)$channel_data['id'], "/title ".$data['title'],"green",null,0);
    		
    	}else{
			$this->sendBotPrivMessage($this->getVariabel("cid"),"/error ".$this->lang['invalidJoin']);
    	}
    }

     private function join_user_in_channel($data){
         $this->protokol->add_to_channel($data['id']);
         $this->sendBotMessage($data['id'],"/join","green");
         $this->sendBotPrivMessage(1,"/join ".$data['name']);
         return $data;
     }
	 
	 private function parseJoinChannelName($name){

         if($this->sConfig['bad_words_enabled'] != '1'){
             return true;
         }

		 //is ther any bad word??
         $sp = explode("\r\n",$this->sConfig['bad_words']);
		 for($i=0;$i<count($sp);$i++){
			 
			 if($sp[$i] == ""){
				 continue;
			 }
			 
			 if(preg_match("/".$sp[$i]."/si",$name)){
				 return false;
			 }
		 }
		 
		 return true;
	}
	 
    private function joinUserInNewChannel($channel){
		$data = $this->protokol->new_channel($channel);
        $this->channel = $this->protokol->get_channel_list();
        $this->protokol->add_to_channel($data['id']);

    	$this->sendBotMessage($data['id'],"/join","green");
        $this->sendBotPrivMessage(1,"/join ".$channel);
    	
    	//join user ;)
    	return $data;
    }
    
    private function joinUser($channel,$data){
        if($data['ban'] == Yes){
            if(time() > $data['banTo']){
                $data['ban'] = No;
                $this->protokol->remove_ban(
                    $data['cid'],
                    $data['uid'],
                    $data['id']
                );
                $this->answer_join();
                return false;//just for stop last run :)
            }else{
                $this->sendBotPrivMessage(1,"/bannet ".$channel);
                return false;
            }
        }

        $this->sendBotMessage($data['cid'],"/join","green");
        $this->sendBotPrivMessage(1,"/join ".$channel);

        return $data;
    }
    
    private function getOnline($id,$isCommand = false){
		$name = array();
		if($this->websocket){
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if($client->isLogin && !empty($client->channel[$id])){
					if($isCommand){
                        $name[] = $client->user['user_id']."|".$client->user['nick']."|".$client->user['user_avatar']."|".No;
					}else{
						$name[] = array(
							$client->user['user_id'],
							$client->user['nick'],
							$this->convert_image($client->user['user_avatar']),
							No
						);
					}
				}
			}
		}else{
		$sql = mysqli_query(self::$mysql,"SELECT user.nick AS nick, user.user_id AS id, user.user_avatar AS img, cm.isInAktiv AS isInAktiv
		FROM ".DB_PREFIX."users AS user
		LEFT JOIN ".DB_PREFIX."chat_member AS cm ON user.user_id = cm.uid
		WHERE cm.cid='".(int)$id."' AND cm.ban <> '".Yes."'");
    	
    	while($row = mysqli_fetch_array($sql)){
    		if($isCommand){
    			$name[] = $row['id']."|".$row['nick']."|".$this->convert_image($row['img'])."|".$row['isInAktiv'];
    		}else{
    			$name[] = array($row['id'],$row['nick'],$this->convert_image($row['img']),$row['isInAktiv']);
    		}
    	}
		}
    	return $name;
    }
    
    //getStatus
    private function answer_getStatus(){
        $this->sendBotPrivMessage(1,"/getStatus you are user");
		$this->sendBotPrivMessage(1,"/config ".$this->getUserConfig());
		$this->sendBotPrivMessage($this->getVariabel("cid"),"/profilImage ".$this->convert_image(
                $this->protokol->user['user_avatar']
            ));
    }
    
    //nick control
    
    private function nickKontrol($nick){
		$sql = mysqli_query(self::$mysql,"SELECT `user_id` FROM `".DB_PREFIX."users` WHERE `user_name`='".mysqli_escape_string(self::$mysql,$nick)."' AND `user_id`!='".$this->protokol->user['user_id']."' OR `nick`='".mysqli_escape_string(self::$mysql,$nick)."' AND `user_id`!='".$this->protokol->user['user_id']."'");
    	$row = mysqli_fetch_array($sql);
    	return (empty($row['user_id']) ? true : false);
    }
    
    //user config
    private function getUserConfig(){
    	$return = array();
		
		$sql = mysqli_query(self::$mysql,"SELECT *
		FROM `".DB_PREFIX."chat_userConfig`
		WHERE `uid`='".$this->protokol->user['user_id']."'");

    	while($row = mysqli_fetch_array($sql)){
    		$return[] = $row['key']."=".$row['value'];
    	}
    	
    	return implode(";",$return);
    }
    
    //load sektion

     private function get_base_part($numBack = 0){
         if($this->basepart !== null){
             return $this->basepart;
         }

         $file = __FILE__;
         //remove server.php ;)
         $file = preg_replace("/server\.php$/",null,$file);

         if($numBack !== 0){
            $block = explode("\\",$file);

             for($i=0;$i<$numBack;$i++){
                 unset($block[count($block)-1]);
             }

             $file = implode("\\",$block)."\\";
         }

         return $file;
     }

     private function init_protokol(){
         if($this->getConfig("protokol") == "socket"){
             include $this->get_base_part()."lib\\protokol\\websocket.php";
         }else{
             include ("lib/protokol/ajax.php");
         }
         $this->protokol = new Protokol(self::$mysql,$this);
     }

     private function init_system_setting(){
         $sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."settings"."`");
         while($row = mysqli_fetch_array($sql)){
             $this->sConfig[$row['settings_name']] = $row['settings_value'];
         }
     }

     private function get_system_config($name){
         if(empty($this->sConfig[$name])){
             return null;
         }

         return $this->sConfig[$name];
     }

     private function init_db(){
         $mysqli = new mysqli(
             $this->getVariabel("db_host"),
             $this->getVariabel("db_user"),
             $this->getVariabel("db_pass"),
             $this->getVariabel("db_data")
         );

         if($mysqli->connect_errno){
             exit($mysqli->connect_error);
         }

         self::$mysql = $mysqli;
     }
    
    private function loadPages(){
        if($this->websocket){
            $load = array(
                $this->get_base_part()."lib\\define.php",
                $this->get_base_part()."lib\\db.php",
                $this->get_base_part()."lib\\json.php",
                $this->get_base_part()."lib\\protokol\\Protokol.php",
            );
        }else{
            $load = array(
                'lib/define.php',
                'lib/db.php',
                'lib/json.php',
                'lib/protokol/Protokol.php',
            );
        }

        for($i=0;$i<count($load);$i++){
            include $load[$i];
        }

        //vi skal nu have fat i php-fusions config ;)
        $db_host = $db_user = $db_pass = $db_name = null;//set all to null ;)
        if($this->websocket)
            include($this->get_base_part(3)."config.php");
        else
            include("../../config.php");
        $this->variabel['db_host'] = $db_host;
        $this->variabel['db_user'] = $db_user;
        $this->variabel['db_pass'] = $db_pass;
        $this->variabel['db_data'] = $db_name;
    }
    
    //user sektion
    private function userInit(){
		$this->initUser();
		$this->loadUserConfig();
		$this->variabel["cid"] = $this->post("channel") ? $this->getCidFromChannel($this->post("channel")) : 1;
    }
    
	 private function convert_image($img){
		if(empty($img) || !trim($img)){
			$img = "../../images/avatars/noavatar100.png";
		}else{
			$img = "../../images/avatars/".$img;
		}
		 
		 return $img;
	 }
	 
    private function initUser(){
		if($this->getVariabel("userBlock") == $this->getSessionId()){
			if(!$this->login()){
                exit("Unknown user");
            }
		}else{
			Json::location("../../index.php?error=session");
        }
	}

     private function get_nick_from_user_id($uid){
         if($this->websocket){
             foreach($this->clientObj AS $clientID => $clientOBJ){
                 if($clientOBJ->isLogin && $clientOBJ->user['user_id'] == $uid){
                     return $clientOBJ->user['nick'];
                 }
             }

             return null;
         }
     }
    
    private function loadUserConfig(){
		$sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."chat_userConfig` WHERE `uid`='".($this->websocket ? $this->getVariabel("client")->user['id'] : $this->user['user_id'])."'");
        while($row = mysqli_fetch_array($sql)){
            $this->userConfig[$row['key']] = $row['value'];
        }
    }
    
    //variabel sektion
    
    private function getVariabel($key){
        if(empty($this->variabel[$key])){
            return null;
        }else{
            if(is_object($this->variabel[$key])){
                return $this->variabel[$key];
            }
            if(!is_array($this->variabel[$key]) && !trim($this->variabel[$key])){
                return null;
            }

        }
        
        return $this->variabel[$key];
    }
    
    private function loadVariabel(){
        $this->variabel['roomId']    = $this->get("roomId")    ? $this->get("roomId")    : null;
        $this->variabel['userBlock'] = $this->get("userBlock") ? $this->get("userBlock") : null;
        $this->variabel['isPost']    = $this->get("isPost")    ? true : false;
        $this->variabel['userType']  = $this->get('userType')  ? $this->getUserType($this->get("userType")) : 0;
        $this->variabel['last_id']   = $this->get('li')        ? (int)$this->get('li') : 0;
    }
    
    //user sektion
    
    private function getUserType($typeGet){
        if($typeGet == "user"){
            $this->user['type'] = 1;
        }else{
             $this->user['type'] = 0;
        }
    }
    
    //Config sektion
    
    private function loadDatabaseConfig(){
        $sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."settings_inf` WHERE `settings_inf`='chat'");
        while($row = mysqli_fetch_array($sql)){
            $this->config[$row['settings_name']] = $row['settings_value'];
        }
    }
    
    private function getConfig($key){
        if(empty($this->config[$key])){
            return null;
        }
        
        return $this->config[$key];
    }
    
    //session sektion
    private function sessionInit(){

        //wee kontrol if header is sendt :)
        if(headers_sent()){
            exit("Header is allray sendt!");
        }

        if(!$this->isSessionStarted()){
            $this->startSession();
        }
    }

    private function ip(){
        return $_SERVER['REMOTE_ADDR'];
    }

    private function login(){

        if(empty($_COOKIE[COOKIE_PREFIX."user"])){
           return false;
        }
        $ip = $this->ip();
        $ucd = explode(".",$_COOKIE[COOKIE_PREFIX."user"]);




        $sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."users` WHERE
        `user_id`='".(empty($ucd[0]) ? '0' : (int)$ucd[0])."'
        AND `user_status`='0'
        AND `user_actiontime`='0'");
        if(self::$mysql->error){
            exit(self::$mysql->error);
        }
        $row = mysqli_fetch_array($sql);

        if(empty($row)){
            return false;
        }

        if($row['user_ip'] != $ip){
            return false;
        }

        $this->protokol->set_user_data($row);
        $this->user = $row;
        return true;
    }
    
    private function startSession(){
        session_start();
    }
    
    private function isSessionStarted(){
        return (session_id() != '');
    }
    
    private function getSessionId(){
        return session_id();
    }
    
    //header sektion (post get session)
    
    private function get($key){
        if(empty($_GET[$key]) || !trim($_GET[$key])){
            return null;
        }
        
        return $_GET[$key];
    }
    
    private  function post($key){
    	if(empty($_POST[$key]) || !trim($_POST[$key])){
    		return null;
    	}
    	
    	return $_POST[$key];
    }

     public function session($name){
         if(empty($_SESSION[$name]) || !is_array($_SESSION[$name]) && !trim($_SESSION[$name])){
             return null;
         }

         return $_SESSION[$name];
     }
    
    //send message

     private function send_message_to_users($message){
         if($this->websocket){

             $input = $this->init_get_data();

             $this->display(
                 $input['channel'],
                 $this->getVariabel('client')->user['nick'],
                 $message
             );

             for($i=0;$i<count($this->clientObj);$i++){
                 $client = $this->clientObj[$i];
                 if(!$client->isLogin){
                     continue;
                 }

                 if(!empty($client->channel[$this->getVariabel("cid")]) && !in_array($this->protokol->user['user_id'],$this->protokol->get_ignore($client->user['user_id']))){
                     $client->message(array(
                         'cid'      => $this->getVariabel("cid"),
                         'message'  => $this->remove_bad_words($message),
                         'color'    => $this->getVariabel("client")->user_config['textColor'],
                         'uid'      => $this->getVariabel("client")->user['user_id'],
                         'nick'     => $this->getVariabel("client")->user['nick'],
                         'channel'  => $this->protokol->get_channel_by_id($this->getVariabel("cid")),
                         'img'      => $this->getVariabel("client")->user['user_avatar']
                     ));
                 }
             }
         }
     }

    private function sendBotMessage($cid,$message,$color = false, $uid = false){
		if($this->websocket){
			$input = $this->init_get_data();
            $this->display(
                $input['channel'],
                'Bot',
                $message
            );
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if(!$client->isLogin){
                    continue;
                }
                if(!empty($client->channel[$cid])){
					$client->bot_message(array(
						'cid'     => $cid,
						'message' => $message,
						'color'   => ($color === false ? $this->getConfig("botTextColor") : $color),
						'uid'     => $uid !== false ? $uid : $this->getVariabel("client")->user['user_id'],
						'nick'    => $uid !== false ? $this->get_nick_from_user_id($uid) : $this->getVariabel("client")->user['nick'],
						'channel' => $this->protokol->get_channel_by_id($cid),
						'img'     => $this->getVariabel("client")->user['user_avatar']
					));
				}
			}
			return;
		}
		
    	    if(!$uid){
    	    	$uid = $this->user['user_id'];
    	    }
    	
            if($color === false){
                $color = $this->getConfig("botTextColor");
            }

		    mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `messageColor`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".(int)$uid."',
                '".(int)$cid."',
                '".Yes."',
                NOW(),
                '".mysqli_escape_string(self::$mysql,$message)."',
                '".mysqli_escape_string(self::$mysql,$color)."',
                '".No."',
                '0'
                )");
    }
    
    private function sendBotPrivMessage($cid,$message,$color = false,$uid=null,$my=null){
		
		if($this->websocket){
            $this->display(
                ($cid === 1 ? 'Globel' : $this->protokol->get_channel_by_id($cid)),
                'Bot',
                $message
            );
			for($i=0;$i<count($this->clientObj);$i++){
				$client = $this->clientObj[$i];
                if(!$client->isLogin){
                    continue;
                }
				$m = ($uid === null ? $this->getVariabel("client")->user['user_id'] : $uid);
                if(!empty($client->channel[$cid]) || $cid == 1){
                    if($m==$client->user['user_id']){
					$client->bot_message(array(
						'cid'     => $cid,
						'message' => $message,
						'color'   => ($color ? $color : 'yellow'),
						'uid'     => ($my === null ? $this->getVariabel("client")->user['user_id'] : $my),
						'nick'    => $this->getVariabel("client")->user['nick'],
						'channel' => $this->protokol->get_channel_by_id($cid),
						'img'     => $this->getVariabel("client")->user['user_avatar'],
					));
                    }
				}
			}
			return;
		}
		
    	    mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_message`
            (
            `uid`,
            `cid`,
            `isBot`,
            `time`,
            `message`,
            `messageColor`,
            `isMsg`,
            `msgTo`
            ) VALUE (
                '".(int)($my === null ? $this->user['user_id'] : $my)."',
                '".(int)$cid."',
                '".Yes."',
                NOW(),
                '".mysqli_escape_string(self::$mysql,$message)."',
                '".mysqli_escape_string(self::$mysql,($color ? $color : 'yellow'))."',
                '".Yes."',
                '".(int)($uid === null ? $this->user['user_id'] : $uid)."'
                )");

        if(self::$mysql->error){
            exit(self::$mysql->error);
        }
    }
	 
	 private function ban($channel,$uid,$to,$nick){
		 $cid = $this->getCidFromChannel($channel,false);
		 $this->sendBotPrivMessage(1,"/ban ".$channel,"red",$uid,0);
		 if($this->websocket){
             foreach($this->clientObj AS $clientID => $clientOBJ){
                 if($clientOBJ->isLogin && $clientOBJ->user['user_id'] == $uid){
                     $clientOBJ->ban($cid);
                 }
             }
         }else{
             mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_member` SET `ban`='".Yes."', `banTo`='".(int)$to."' WHERE `uid`='".(int)$uid."' AND `cid`='".(int)$cid."'");
         }

         $this->protokol->banUser($cid,$uid,$to);
		 $this->sendBotMessage($cid,"/ban ".$nick,"red");
	 }
    
	 //Yes my dear :D 
	 private function kick($channel,$message = null,$uid = 0, $sendMessage = true){
		 if($uid === 0){
			 $uid = $this->protokol->user['user_id'];
		 }

         if(!is_numeric($channel)){
             $cid = $this->getCidFromChannel($channel,false);
         }else{
             $cid = (int)$channel;
         }
		 
		 if($cid == 1){
			 return;
		 }

         if($sendMessage){
             $this->sendBotPrivMessage(
                 1,
                 "/kick ".$channel.($message !== null ? ' '.$message : null),
                 "red",
                 $uid,
                 $uid//kun for denne bruger :D
             );
         }

         $this->protokol->kick(
             $cid,
             $uid
         );

         if($sendMessage){
             $this->sendBotMessage($cid, "/kick".($message !== null ? " ".$message : null),"red",$uid);
         }
	 }
	 
	 private function iADMIN(){
         if($this->protokol->user['user_level'] >= 102)
             return true;
         else
             return false;
	 }
	 
	 private function iSUPERADMIN(){
         if($this->protokol->user['user_level'] == 103)
             return true;
         else
             return false;
	 }

     private function display($channel,$nick,$msg){
         echo "[".$channel."] ".date("H:i")." ".$nick." ".$msg;

         echo "\r\n";
     }

     private function is_length_okay($text, $min=0,$max=1000){
         if(strlen($text) < $min){
             return self::text_min;
         }

         if(strlen($text) > $max){
             return self::text_max;
         }

         return true;
     }
 }

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

     function update_post($cid){
         $is_inaktiv = $this->aktiv[$cid]['isInaktiv'];
         $this->aktiv[$cid] = array(
             'time'      => time(),
             'isInaktiv' => No
         );

         return ($is_inaktiv == Yes);
     }

     function handle_inaktiv_or_leave_status($inaktiv,$leave){
         foreach($this->channel as $cid => $data){
             if($cid == 1){
                 continue;
             }

             if($leave != 0 && $data['lastActiv'] < strtotime("-".$leave." minutes")){
                 unset($this->channel[$cid]);
                 throw new Exception("leave",$cid);
             }elseif($data['lastActiv'] < strtotime("-".$inaktiv." minutes") && $data['isInAktiv'] == No){
                 $this->channel[$cid]['isInAktiv'] = Yes;
                 throw new Exception("inaktiv",$cid);
             }
         }
     }

     function kick($cid,$isBan = false){
         if(!empty($this->channel[$cid])){
             unset($this->aktiv[$cid]);
             if(!$isBan)
                 unset($this->channel[$cid]);
         }
     }

     function ban($cid){
         $this->kick($cid,true);
     }

     function socket_user_client($socket){
		 $this->socket = $socket;
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

         $sql = mysqli_query(Server::$mysql,"SELECT * FROM `".DB_PREFIX."users` WHERE
         `user_id`='".(empty($user_cookie_data[0]) ? '0' : (int)$user_cookie_data[0])."'
         AND `user_status`='0'
         AND `user_actiontime`='0'");
         $row = mysqli_fetch_array($sql);

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
             return;

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
         $sql = mysqli_query(Server::$mysql,"SELECT * FROM `".DB_PREFIX."chat_userConfig` WHERE `uid`='".$this->user['user_id']."'");
         while($row = mysqli_fetch_array($sql)){
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
 
$server = new Server((empty($socket) ? false : true));