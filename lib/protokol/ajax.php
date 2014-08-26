<?php
class Protokol implements ProtokolHead{
    public $use_session = true;
    public $protokol    = "Ajax";
    private $channel = array();
    private $my_channel = array();
    private $database;
    public $user = array();
    private $ignore = array();
    private $main;
    private $userconfig = array();

    function set_user_data($array,$ligegyldigt){
        $this->user = $array;
    }

    function add_ignore($uid){
        $this->ignore[] = $uid;
        $this->database->query("INSERT INTO `".DB_PREFIX."chat_ignore`
                 (
                 `uid`,
                 `ignore`
                 ) VALUES(
                 '".$this->user['user_id']."',
                 '".$uid."'
                 )");
    }

    function remove_ignore($uid){
        if(($key = array_search($uid,$this->get_ignore())) !== false){
            unset($this->ignore[$key]);
            $this->database->query("DELETE FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".(int)$this->user['user_id']."' AND `ignore`='".$uid."'");
        }
    }

    function get_ignore($only_cahce = true){
        if(!$only_cahce || empty($this->ignore)){
            $datas = $this->database->query("SELECT `ignore` FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".(int)$this->user['user_id']."'");
            while($row = $datas->get()){
                $this->ignore[] = $row['ignore'];
            }
        }

        return $this->ignore;
    }

    function Protokol(DatabaseHandler $mysql,Server $server){
        $this->database = $mysql;
        $this->main = $server;
    }

    function get_channel_list($onlye_cache = true){
        if(!$onlye_cache){
            $datas = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_name`");
            while($row = $datas->get()){
                $data = $row;
                unset($data['id']);
                $this->channel[$row['id']] = $data;
            }
        }

        return $this->channel;
    }

    function add_to_channel($cid){
        $this->database->query("INSERT INTO `".DB_PREFIX."chat_member`
         (`uid`,
         `cid`,
         `lastActiv`,
         `isInAktiv`,
         `ban`
         ) VALUE (
         '".(int)$this->user['user_id']."',
         '".(int)$cid."',
         NOW(),
         '".No."',
         '".No."'
         )");

        $id = $this->database->lastIndex();
        $this->my_channel[$id] = array(
            'uid'       => $this->user['user_id'],
            'cid'       => $cid,
            'lastActiv' => time(),
            'isInAktiv' => No,
            'ban'       => No
        );
    }

    function get_my_channel_list($only_cache = true){
        if($only_cache && !empty($this->my_channel)){
            return $this->my_channel;
        }

        $this->my_channel = array();//unset all data :)

        $datas = $this->database->query("SELECT cm.*,cn.name
        FROM `".DB_PREFIX."chat_member` AS cm
        LEFT JOIN `".DB_PREFIX."chat_name` AS cn ON cn.id = cm.cid
        WHERE cm.uid='".$this->user['user_id']."'");
        while($row = $datas->get()){
            $data = $row;
            unset($data['id']);
            $this->my_channel[$row['id']] = $data;
        }

        return $this->my_channel;
    }


    function new_channel($name,$title = null,$isSystem = false){
        if($title === null){
            $title = $name;
        }

        $data = $this->database->prepare("INSERT INTO `".DB_PREFIX."chat_name`
         (
         `name`,
         `isPriv`,
         `uid`,
         `title`,
         `setTitle`,
         `isPm`
         ) VALUE (
         {name},
         '".No."',
         '0',
         {title},
         '".$this->user['user_id']."',
         '".No."'
         )");

        $data->add("name",$name);
        $data->add("title",$title);
        $data->done();

        $id = $this->database->lastIndex();
        $this->channel[$id] = array(
            'name'     => $name,
            'isPriv'   => No,
            'uid'      => 0,
            'title'    => $title,
            'setTitle' => $this->user['user_id'],
            'isPm'     => No
        );

        $d = $this->channel[$id];
        $d['id'] = $id;

        return $d;
    }

    function remove_ban($uid,$cid,$id,$r = true){
        $this->database->query("DELETE FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".(int)$uid."'
        AND `cid`='".(int)$cid."'");
    }

    function get_flood($cid){
        if(!$this->main->session("flood")){
            $flood = array();
            $_SESSION['flood'] = array();
        }else
            $flood = $this->main->session("flood");

        if(empty($flood[$cid])){
            return array();
        }

        return $flood[$cid];
    }

    function update_flood($flood,$cid){
        $_SESSION["flood"][$cid] = $flood;
    }

    function update_nick($newNick){
        $this->user['nick'] = $newNick;
    }

    function getBannetInChannel($cid){
        $data = $this->database->query("SELECT `uid` FROM `".DB_PREFIX."chat_member` WHERE `ban`='".Yes."' AND `cid`='".(int)$cid."'");
        $bannet = array();
        while($row = $data->get()){
            $bannet[] = $row['uid'];
        }

        return $bannet;
    }

    function banUser($cid,$uid,$banTo,$save = true){
        $this->database->query("UPDATE `".DB_PREFIX."chat_member` SET
        `ban`='".Yes."',
        `banTo`='".$banTo."'
        WHERE `uid`='".$uid."' AND `cid`='".$cid."'");
    }

    function get_channel_by_id($id){
        if(!empty($this->channel[$id])){
            return $this->channel[$id]['name'];
        }

        return "Bot";
    }

    function getUserInChannel($cid,$nick){
        $sql = $this->database->prepare("SELECT user.*
        FROM `".DB_PREFIX."users` AS user
        LEFT JOIN `".DB_PREFIX."chat_member` AS cm ON cm.uid = user.user_id
        WHERE cm.cid='".(int)$cid."'
        AND user.nick={nick}");

        $sql->add("nick",$nick);
        $user = $sql->done();

        if($this->database->isError){
            exit($this->database->getError());
        }

        $user = $user->get();

        if(empty($user))
            return false;
        else
            return $user;
    }

    function getBanId($cid,$uid){
        $sql = $this->database->query("SELECT `id`
        FROM `".DB_PREFIX."chat_member`
        WHERE `cid`='".$cid."'
        AND `uid`='".$uid."'
        AND `ban`='".Yes."'");
        $data = $sql->get();

        if(empty($data))
            return null;
        else
            return $data;
    }

    function kick($cid,$uid){
        $this->database->query("DELETE FROM `".DB_PREFIX."chat_member`
        WHERE `cid`='".$cid."'
        AND `uid`='".$uid."'");
    }

    function updateConfig($key,$value){
        $sql = $this->database->prepare("UPDATE `".DB_PREFIX."chat_userConfig` SET `value`={value} WHERE `uid`={uid} AND `key`={key}");
        $sql->add("value",$value);
        $sql->add("key",$key);
        $sql->add("uid",$this->user['user_id']);
        $sql->done();
    }

    function userConfig($key,$id = null){
        if($id === null)
            $id = $this->user['user_id'];

        if(empty($this->userconfig[$id]))
            $this->userconfig[$id] = $this->getUserConfig($id);

        if(empty($this->userconfig[$id][$key]) || !trim($this->userconfig[$id][$key]))
            return null;
        return $this->userconfig[$id][$key];

    }

    private function getUserConfig($id){
        $sql = $this->database->query("SELECT * FROM `".DB_PREFIX."chat_userconfig` WHERE `uid`='".(int)$id."'");
        $return = array();
        while($row = $sql->get()){
            $return[$row['key']] = $row['value'];
        }

        return $return;
    }

    function getConfig($key){}//not in use. only for websocket (yey);
    function update(){}//not in use
}