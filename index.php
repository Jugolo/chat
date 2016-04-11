<?php
include "include/system.php";
include "include/ajax.php";
include "../../maincore.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
function get_ip(){
	$ip = $_SERVER["REMOTE_ADDR"];
	
	if($ip=="::1"){
		return "127.0.0.1";
	}
	
	return $ip;
}
class User{
	private $data;
	
	public function hasToken(){
		return cookie("identify", false);
	}
	
	public function getToken(){
		return cookie("identify");
	}
	
	public function setToken($token){
		Ajax::createCookie("identify", $token);
	}
	public function login(){
		if(!iGUEST){
			global $userdata;
		}
		$query = dbquery("SELECT * FROM `".DB_PREFIX."chat_user` WHERE ".(iGUEST ? "`token`='".cookie("identify")."' AND `is_user`='0'" : "`uid`='".$userdata['user_id']."' AND `is_user`='1'"));
		if(dbrows($query)==0){
			if(iGUEST){
				$this->createUser(0, $this->createNick(), 0, "");
				$query = dbquery("SELECT * FROM `".DB_PREFIX."chat_user` WHERE `token`='".cookie("identify")."' AND `is_user`='0'");
			}else{
				$this->createUser($userdata["user_id"], $userdata["user_name"], 1, $userdata["user_avatar"]);
				$query = dbquery("SELECT * FROM `".DB_PREFIX."chat_user` WHERE `uid`='".$userdata['user_id']."' AND `is_user`='1'");
			}
		}
		
		$this->data = dbarray($query);
		
		//control for token and see if it is a match else update the table user tokens :)
		if($this->data["token"] != cookie("identify")){
			dbquery("UPDATE `".DB_PREFIX."chat_user` SET `token`='".cookie("identify")."' WHERE `id`='".$this->data["id"]."'");
			$this->data["token"] = cookie("identify");
		} 
	}
	private function createNick(){
		$use = "qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM1234567890";
		$return = "";
		for($i = 0; i<4; $i++){
			$return .= $use[mt_rand(0, strlen($use)-1)];
		}
		
		return $return;
	}
	private function createUser($uid, $nick, $isUser, $profileimage){
		dbquery("INSERT INTO `".DB_PREFIX."chat_user` (
				  `uid`,
				  `nick`,
				  `is_user`,
				  `profile`,
				  `token`,
				  `activ`
				) VALUES (
				  '".$uid."',
				  '".$nick."',
				  '".$isUser."',
				  '".($profileimage == "" ? IMAGES."avatars/noavatar100.png" : $profileimage)."',
				  '".$this->getToken()."',
				  '".time()."'
				)");
	}
}
class Session{
	private static $tokens = null;
	private static $data = null;
	private static $id = null;
	private static $user;
	
	public static function login(User $user){
		self::$user = $user;
		if(!$user->hasToken()){
			// delete all tokens there point to this ip addrresse :)
			dbquery("DELETE FROM `".DB_PREFIX."chat_session_data` WHERE `ip`='".get_ip()."'");
			// no token giving :)
			self::createToken($user);
		}else{
			//control if wee has a valid session :)
			$query = dbquery("SELECT * FROM `".DB_PREFIX."chat_session_data` WHERE `ip`='".get_ip()."' AND `token`='".$user->getToken()."'");
			if(dbrows($query) == 0){
				self::createToken($user);
			}
		}
		
		$user->login();
	}
	
	public static function has($key){
		return array_key_exists($key, self::getHandler());
	}
	
	public static function remove($key){
		if(self::has($key)){
			dbquery("DELETE FROM `".DB_PREFIX."chat_session` WHERE `sid`='".self::getID()."' AND `key`='".$key."'");
			unset(self::$data[$key]);
		}
	}
	
	public static function get($key){
		if(self::has($key)){
			self::updateActiv($key);
			$array = self::getHandler();
			return $array[$key];
		}
		
		return null;
	}
	
	public static function set($key, $value){
		if(self::has($key)){
			dbquery("UPDATE `".DB_PREFIX."chat_session` SET `active`='".time()."', `value`='".$value."' WHERE `sid`='".self::getID()."' AND `key`='".$key."'");
		}else{
			dbquery("INSERT INTO `".DB_PREFIX."chat_session` (
					 `sid`,
					 `key`,
					 `value`,
					 `active`
					) VALUES (
					 '".self::getID()."',
					 '".$key."',
					 '".$value."',
					 '".time()."'
					)");
		}
		
		self::getHandler();//to be sure it is interlize :)
		self::$data[$key] = $value;
	}
	
	public static function garbage_collect(){
		$remove = time()-60*60*24;
		dbquery("DELETE FROM `".DB_PREFIX."chat_session_data` WHERE `activ`<'".$remove."'");
		dbquery("DELETE FROM `".DB_PREFIX."chat_session` WHERE `active`<'".$remove."'");
		
	}
	
	private static function updateActiv($key){
		dbquery("UPDATE `".DB_PREFIX."chat_session` SET `active`='".time()."' WHERE `sid`='".self::getID()."' AND `key`='".$key."'");
	}
	
	private static function getHandler(){
		if(self::$data == null){
			self::$data = [];
			$query = dbquery("SELECT `key`, `value` FROM `".DB_PREFIX."chat_session` WHERE `sid`='".self::getID()."'");
			while($row = dbarray($query))
				self::$data[$row['key']] = $row['value'];
		}
		
		return self::$data;
	}
	
	private static function getID(){
		if(self::$id == null){
			$query = dbquery("SELECT `id` FROM `".DB_PREFIX."chat_session_data` WHERE `token`='".self::$user->getToken()."'");
			$row = dbarray($query);
			self::$id = $row['id'];
		}
		
		return self::$id;
	}
	
	private static function createToken(User $user){
		$str = "qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKJHGFDSAZXCVBNM1234567890";
		$use = "";
		for($i = 0; $i<1001; $i++)
			$use .= $str[mt_rand(0, strlen($str)-1)];
		
		$use = md5($use);
		
		if(in_array($use, self::getTokenList()))
			return self::createToken($user);
		
		$user->setToken($use);
		dbquery("INSERT INTO `".DB_PREFIX."chat_session_data` (
				  `ip`,
		          `token`,
				  `activ`
				)VALUES(
				  '".get_ip()."',
				  '".$use."',
				  '".time()."'
				)");
	}
	private static function getTokenList(){
		if(self::$tokens==null){
			$sql = dbquery("SELECT `token` FROM `".DB_PREFIX."chat_session_data`");
			while($row = dbarray($sql))
				self::$tokens[] = $row["token"];
		}
		
		return self::$tokens;
	}
}

$user = new User();

Session::garbage_collect();
Session::login($user);

//get the last id and set it to the session :)
$query = dbquery("SELECT `id` FROM `".DB_PREFIX."chat_message` ORDER BY id DESC LIMIT 1;");
$row = dbarray($query);
Session::set("last_id", $row["id"] == "" ? 0 : $row['id']);

//wee can start the page here ;)
require_once INCLUDES."infusions_include.php";
require_once THEMES."templates/header.php";
add_to_head("<script>".file_get_contents("include/style/js/main.js")."</script>");
opentable("Chat");
include "include/style/chat.php";
closetable();
require_once THEMES."templates/footer.php";

