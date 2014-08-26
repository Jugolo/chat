<?php
class Protokol implements ProtokolHead{
    public $use_session = false;
    public $protokol    = "Socket";
    public $user        = array();
    private $client     = array();
    private $mysql      = null;

    private $channel    = array();
    private $flood      = array();
    private $main       = null;
    private $bannet     = array();

    public function Protokol($mysql,Server $server){
        $this->main = $server;
        $this->mysql = $mysql;
    }

    function get_channel_by_id($id){
        foreach($this->channel AS $cid => $data){
            if($id == $cid){
                return $data['name'];
            }
        }

        return "Bot";
    }

    function get_ignore($uid = null){
        if($uid === null)
            $uid = $this->user['user_id'];
        return $this->client[$uid]->ignore;
    }

    function add_ignore($uid){
        $this->client[$this->user['user_id']]->ignore[] = $uid;
    }

    function remove_ignore($uid){
        if(($key = array_search($uid,$this->get_ignore())) !== false){
            unset($this->client[$this->user['user_id']]->ignore[$key]);
        }
    }

    function set_user_data($data,$client){
        $this->user                     = $data;
        $this->client[$data['user_id']] = $client;
    }

    function get_channel_list($e=false){
        return $this->channel;
    }

    function get_my_channel_list(){
        if(empty($this->user['user_id'])){
            return array();
        }

        if(!empty($this->client[$this->user['user_id']])){
            return $this->client[$this->user['user_id']]->channel;
        }else{
            return array();
        }
    }

    function add_to_channel($cid){
        $id = count($this->client[$this->user['user_id']]->channel);
        $this->client[$this->user['user_id']]->channel[$cid] = array(
            'id'        => $id,
            'uid'       => $this->user['user_id'],
            'cid'       => $cid,
            'lastActiv' => time(),
            'isInAktiv' => No,
            'ban'       => No,
        );
    }

    function new_channel($name,$title = null,$isSystem = false){
        if($title === null)
            $title = $name;

        $id = count($this->channel);

        if($id === 0 && !$isSystem){
            $this->new_channel("null","null channel",true);
            $id = count($this->channel);

        }
        if($id === 1 && !$isSystem){
            $this->new_channel("Bot","Global bot system",true);
            $id = count($this->channel);
        }

        $this->channel[$id] = array(
            'id'       => $id,
            'name'     => $name,
            'isPriv'   => No,
            'uid'      => 0,
            'title'    => $title,
            'setTitle' => 0,
            'isPm'     => No,
        );

        $this->bannet[$id] = array();

        return $this->channel[$id];
    }

    function turn($uid){
        if(in_array($uid,array_keys($this->client))){
            $this->user = $this->client[$uid]->user;
        }
    }

    function get_flood($cid){
        if(empty($this->flood[$this->user['user_id']])){
            $this->flood[$this->user['user_id']] = array(
                $cid => array(),
            );
        }

        return $this->flood[$this->user['user_id']][$cid];
    }

    function update_flood($flood,$cid){
        $this->flood[$this->user['user_id']][$cid] = $flood;
    }

    function update_nick($newNick){
        $this->client[$this->user['user_id']]->user['nick'] = $newNick;
        $this->user['nick'] = $newNick;
    }

    function getUserInChannel($cid,$nick){
        foreach($this->client AS $object){
            if(strtolower($object->user['nick']) == strtolower($nick)){
                foreach($object->channel AS $channelID => $data){
                    if($channelID == $cid){
                        return $object->user;
                    }
                }
            }
        }

        return false;
    }

    function getBannetInChannel($cid){
        if(empty($this->bannet[$cid]))
            return array();
        return $this->bannet[$cid];
    }

    function banUser($cid,$uid,$banTo,$save =true){
        $this->bannet[$cid][] = $uid;
        $this->client[$uid]->channel[$cid]['ban']   = Yes;
        $this->client[$uid]->channel[$cid]['banTo'] = $banTo;
        if($save){
            mysqli_query($this->mysql,"INSERT INTO `".DB_PREFIX."chat_member` (
            `uid`,
            `cid`,
            `lastActiv`,
            `isInAktiv`,
            `ban`,
            `banTo`
            ) VALUES (
            '".(int)$uid."',
            '".(int)$cid."',
            NOW(),
            '".No."',
            '".Yes."',
            '".$banTo."'
            )");
        }
    }

    function remove_ban($uid,$cid,$id,$remove = true){
        unset($this->client[$uid]->channel[$cid]);
        $i = array_search($uid,$this->bannet[$cid]);
        unset($this->bannet[$cid][$i]);
        if($remove){
            mysqli_query($this->mysql,"DELETE FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".$uid."'
        AND `cid`='".$cid."'");
        }
    }

    function getBanId($c,$u){return null;}//this protokol dosent need this :)

    function kick($cid,$uid){
        if(empty($this->client[$uid]->channel[$cid])){
            return false;
        }

        unset($this->client[$uid]->channel[$cid]);
        unset($this->client[$uid]->aktiv[$cid]);
        return true;
    }

    function updateConfig($key,$value){
        $this->client[$this->user['user_id']]->update_user_config($key,$value);
    }

    function getConfig($key){
        $config = $this->client[$this->user['user_id']]->user_config;
        if(empty($config[$key]))
            return null;
        else
            return $config[$key];
    }

    function update(){
        foreach($this->bannet as $cid => $data){
            for($i=0;$i<count($data);$i++){
                $this->remove_ban($data[$i],$cid,0,false);
            }
        }

        $this->bannet = array();//protect against fail.
        $sql = mysqli_query($this->mysql,"SELECT * FROM `".DB_PREFIX."chat_member` WHERE `id`<>'1' AND `ban`='".Yes."'");
        while($row = mysqli_fetch_array($sql)){
            $this->banUser(
                $row['cid'],
                $row['uid'],
                $row['banTo'],
                false
            );
        }
    }
}