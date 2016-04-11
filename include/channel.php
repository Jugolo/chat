<?php
class Channel{
  private static $channels = [];

  public static function renderUsersInChannel($channel, $callback){
     return self::get($channel)->render_user($callback);
  }

  public static function exists($name){
    if(!empty(self::$channels[$name]))
      return true;

    //wee has not cache it yet so wee create it if it exists.
    $sql = Database::query("SELECT * FROM `".table("channels")."` WHERE `name`=".Database::qlean($name));
    if($sql->rows() == 1){
      self::$channels[$name] = new ChannelData($sql->fetch());
      return true;
    }

    return false;
  }

  public static function get($name){
    if(self::exists($name)){
      return self::$channels[$name];
    }

    return null;
  }

  public static function create($name, $title=null){
    if(self::exists($name)){
       return false;
    }

    Database::insert("channels", [
       "name"        => $name,
       "title"       => ($title == null ? $name : $title),
       "start_group" => 0,
    ]);

    self::get($name)->join(get_user(), self::get($name)->id());
  }
}

class ChannelData{
  private $data;
  private $users = [];

  public function render_user($callback){
  	$cache = $this;
  	User::run(function(UserData $user) use($callback, $cache){
  		if($cache->is_member($user->id())){
  			//okay wee has this as memeber :)
  			$callback($cache->users[$user->id()]);
  		}
  		
  		return null;
  	});
  }

  public function __construct($data){
    $this->data = $data;
  }

  public function id(){
    return $this->data["id"];
  }
  
  public function name(){
  	return $this->data["name"];
  }

  public function is_member($uid){
    //control if wee got the user cached
    if(!empty($this->users[$uid]))
      return true;
  
    //wee control if the user is member but not yet cached.
    $sql = Database::query("SELECT * FROM `".table("channel_member")."` WHERE `cid`='".$this->id()."' AND `uid`=".Database::qlean($uid));

    if($sql->rows() == 1){
       $this->users[$uid] = new ChannelMember($sql->fetch(), getUserById($uid));
       return true;
    }

    echo $sql->rows();
    
    return false;
  }

  public function join(UserData $user, $gid = null){
    if(!$this->is_member($user->id())){
      Database::insert("channel_member", [
         "uid" => $user->id(),
         "cid" => $this->id(),
         "gid" => $gid == null ? 0 : $gid,
         "activ" => time(),
      ]);
      
      return $this->is_member($user->id());
    }
    return false;
  }
  
  public function leave(UserData $user){
  	 $query = Database::query("DELETE FROM `".table("channel_member")."` WHERE `uid`='".$user->id()."' AND `cid`='".$this->id()."'");
  	 if($query->rows() != 0){
  	 	unset($this->users[$user->id()]);
  	 	return true;
  	 }
  	 
  	 return false;
  }
}

class ChannelMember{
	private $user;
	
	public function __construct($data, UserData $user){
		$this->user = $user;
	}
	
	/**
	 * Get the user object
	 * @return UserData
	 *    return the userdata object
	 */
	public function getUser(){
		return $this->user;
	}
}
