<?php
class Channel{
  private static $channels = [];

  public static function renderUsersInChannel($channel, $callback){
     return self::get($channel)->render_user($callback);
  }
  
  public static function renderChannels($callback){
  	foreach(self::$channels as $name => $data){
  		$callback($name, $data);
  	}
  }

  public static function exists($name){
    if(!empty(self::$channels[$name]))
      return true;

    //wee has not cache it yet so wee create it if it exists.
    $sql = Database::query("SELECT * FROM `".table("channels")."` WHERE `name`=".Database::qlean($name));
    if($sql->rows() == 1){
      self::$channels[$name] = new ChannelData($sql->fetch());
      cli_title();
      return true;
    }

    return false;
  }

  /**
   * Get the channel data handler
   * @param string $name channel name
   * @return ChannelData ChannelData if exists or null if not
   */
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

    $data = self::get($name);
    $data->join(get_user(), 0);
  }
  
  public static function delete($name){
  	if(self::exists($name)){
  		//the channel exists
  		$chanenl = self::get($name);
  		//wee tel the channel data handler this channel is now on delete
  		$channel->onDelete();
  		//remove the channel (up to data handler to delete members)
  		Database::query("DELETE FROM `".table("channels")."` WHERE `id`=".Database::qlean($channel->id()));
  	}
  }
}

class ChannelData{
  private $data;
  private $users = [];
  private $logger = null;
  
  public function controlMemberStatus(){
  	echo "here\r\n";
  	$name = $this->name();
  	$data = $this;
  	$this->render_user(function(ChannelMember $member) use($name, $data){
  		if(time() >= strtotime("-5 MINUTES", $member->time())){
  			send_channel($name, "GHOST ".$name.": ".$member->getUser()->nick());
  			$data->leave($member->getUser());
  		}elseif(time() >= strtotime("+2 MINUTES", $member->time())){
  			//contole the user is mark as inaktiv 
  			if(!$member->isInaktiv()){
  				$member->markInaktiv();
  			}
  		}
  	});
  }

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
  
  public function log($message){
  	$this->logger->appendLine($message);
  }

  public function __construct($data){
    $this->data = $data;
    $this->logger = new Logger($this);
  }

  public function id(){
    return $this->data["id"];
  }
  
  public function name(){
  	return $this->data["name"];
  }
  
  public function title(){
  	return $this->data["title"];
  }

  public function is_member($uid){
    //control if wee got the user cached
    if(!empty($this->users[$uid]))
      return true;
  
    //wee control if the user is member but not yet cached.
    $sql = Database::query("SELECT * FROM `".table("channel_member")."` WHERE `cid`='".$this->id()."' AND `uid`=".Database::qlean($uid));

    if($sql->rows() == 1){
       $this->users[$uid] = new ChannelMember($sql->fetch(), getUserById($uid), $this);
       return true;
    }
    
    return false;
  }

  public function join(UserData $user, $gid = null){
    if(!$this->is_member($user->id())){
      Database::insert("channel_member", [
         "uid"     => $user->id(),
         "cid"     => $this->id(),
         "gid"     => $gid == null ? 0 : $gid,
         "activ"   => time(),
      	 "inaktiv" => NO,
      ]);
      
      if($this->is_member($user->id())){
      	send_channel($this->name(), "JOIN ".$this->name().": ".$user->nick());
      	return true;
      }
    }
    return false;
  }
  
  public function leave(UserData $user){
  	 $query = Database::query("DELETE FROM `".table("channel_member")."` WHERE `uid`='".$user->id()."' AND `cid`='".$this->id()."'");
  	 if($query->rows() != 0){
  	 	unset($this->users[$user->id()]);
  	 	return true;
  	 }
  	 
  	 //let us control member count if 0 remove the channel
  	 if(count($this->users) == 0){
  	 	//okay remove the channel
  	 	Channel::delete($this->name());
  	 }
  	 
  	 return false;
  }
  
  public function onDelete(){
  	Database::query("DELETE FROM `".table("channel_member")."` WHERE `cid`='".$this->id()."'");
  	$this->logger->end();
  }
}

class ChannelMember{
	private $user;
	private $data;
	private $channel;
	
	public function __construct($data, UserData $user, ChannelData $channel){
		$this->user    = $user;
		$this->data    = $data;
		$this->channel = $channel;
	}
	
	/**
	 * Get the user object
	 * @return UserData
	 *    return the userdata object
	 */
	public function getUser(){
		return $this->user;
	}
	
	/**
	 * Get the last time the user has sent a message
	 * @return int time
	 */
	public function time(){
		return $this->data['activ'];
	}
	
	public function isInaktiv(){
		return $this->data["inaktiv"] == YES;
	}
	
	public function markInaktiv(){
		$query = Database::query("UPDATE `".table("channel_member")."` SET `inaktiv`='".YES."' WHERE `id`='".$this->data["id"]."'");
		send_channel($this->channel->name(), "INAKTIV ".$this->channel->name().": ".$this->getUser()->nick());
		return $query->rows() != 0;
	}
}
