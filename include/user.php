<?php

function get_user(){
   return User::get(Session::getCurrentToken());
}

function getUserById($id){
  return User::run(function(UserData $user) use($id){
     if($user->id() == $id)
       return $user;
     return false;
  });
} 

class User{
  private static $user = [];
  
  public static function numberUser(){
  	return count(self::$user);
  }

  public static function run($callback){
    $sql = Database::query("SElECT * FROM `".table("user")."`");
    while($row = $sql->fetch()){
      if(!empty(self::$user[$row["token"]]))
        $u = self::$user[$row["token"]];
      else
        $u = self::$user[$row["token"]] = new UserData($row);

      if($return = $callback($u))
        return $return;
    }
    return null;
  }

  public static function get($token){
     if(self::exists($token)){
        return self::$user[$token]; 
     }

     return null;
  }

  public static function exists($token){
    if(!empty(self::$user))
      return true;

     $sql = Database::query("SELECT * FROM `".table("user")."` WHERE `token`=".Database::qlean($token));
     if($sql->rows() == 1){
       self::$user[$token] = new UserData($sql->fetch());
       cli_title();
       return true;
     }

     return false;
  }
}

class UserData{
   private $data = [];
   private $channels = [];

   public function __construct(array $data){
     $this->data = $data;
   }
  
   public function id(){
     return $this->data["id"];
   }
   
   public function nick(){
   	 return $this->data["nick"];
   }

   public function join_channel($name){
     $channel = Channel::get($name);
     if($channel == null){
     	Channel::create($name);
     	return;
     }
     
     if(!$channel->is_member($this->id())){
        if($channel->join($this)){
        	$this->channels[$channel->id()] = $chanenl->name();
        }
     }
   }
   
   public function leave_channel($name){
   	  $channel = Channel::get($name);
   	  if($channel != null){
   	  	//control if member of this channel
   	  	if($channel->is_member($this->id())){
   	  		$channel->leave($this);
   	  		unset($this->channels[$channel->id()]);
   	  		return true;
   	  	}
   	  }
   	  
   	  return false;
   }
   
   public function getChannelsId(){
   	  return array_values($this->channels);
   }
   
   public function getChannelNames(){
   	  return array_keys($this->channels);
   }

   public function ip(){
     return $this->data["ip"];
   }
}
