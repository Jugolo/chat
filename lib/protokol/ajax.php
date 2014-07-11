<?php
class Protokol implements ProtokolHead{
    public $use_session = true;
    public $protokol    = "Ajax";
    private $channel = array();
    private $my_channel = array();
    private static $mysql;
    public $user = array();
    private $ignore = array();
    private $main;

    function set_user_data($array,$ligegyldigt){
        $this->user = $array;
    }

    function add_ignore($uid){
        $this->ignore[] = $uid;
        mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_ignore`
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
            mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".(int)$this->user['user_id']."' AND `ignore`='".$uid."'");
        }
    }

    function get_ignore($only_cahce = true){
        if(!$only_cahce || empty($this->ignore)){
            $sql = mysqli_query(self::$mysql,"SELECT `ignore` FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".(int)$this->user['user_id']."'");
            while($row = mysqli_fetch_array($sql)){
                $this->ignore[] = $row['ignore'];
            }
        }

        return $this->ignore;
    }

    function Protokol($mysql,Server $server){
        self::$mysql = $mysql;
        $this->main = $server;
    }

    function get_channel_list($onlye_cache = true){
        if(!$onlye_cache){
            $sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."chat_name`");
            while($row = mysqli_fetch_array($sql)){
                $data = $row;
                unset($data['id']);
                $this->channel[$row['id']] = $data;
            }
        }

        return $this->channel;
    }

    function add_to_channel($cid){
        mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_member`
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

        $id = mysqli_insert_id(self::$mysql);
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

        $sql = mysqli_query(self::$mysql,"SELECT * FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$this->user['user_id']."'");
        while($row = mysqli_fetch_array($sql)){
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

        mysqli_query(self::$mysql,"INSERT INTO `".DB_PREFIX."chat_name`
         (
         `name`,
         `isPriv`,
         `uid`,
         `title`,
         `setTitle`
         ) VALUE (
         '".mysqli_escape_string(self::$mysql,$name)."',
         '".No."',
         '0',
         '".mysqli_escape_string(self::$mysql,$title)."',
         '".$this->user['user_id']."'
         )");

        $id = mysqli_insert_id(self::$mysql);
        $this->channel[$id] = array(
            'name'     => $name,
            'isPriv'   => No,
            'uid'      => 0,
            'title'    => $title,
            'setTitle' => $this->user['user_id']
        );

        $d = $this->channel[$id];
        $d['id'] = $id;

        return $d;
    }

    function remove_ban($uid,$cid,$id,$r = true){
        mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_member`
        WHERE `uid`='".$uid."'
        AND `cid`='".$cid."'");
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
        $sql = mysqli_query(self::$mysql,"SELECT `uid` FROM `".DB_PREFIX."chat_member` WHERE `ban`='".Yes."'");
        $bannet = array();
        while($row = mysqli_fetch_array($sql)){
            $bannet[] = $row['uid'];
        }

        return $bannet;
    }

    function banUser($cid,$uid,$banTo,$save = true){
        mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."` SET
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
        $sql = mysqli_query(self::$mysql,"SELECT user.*
        FROM `".DB_PREFIX."users` AS user
        LEFT JOIN `".DB_PREFIX."chat_member` AS cm ON cm.uid = user.user_id
        WHERE cm.cid='".(int)$cid."'
        AND user.nick='".mysqli_real_escape_string(self::$mysql,$nick)."'");

        $user = mysqli_fetch_array($sql);

        if(empty($user))
            return false;
        else
            return $user;
    }

    function getBanId($cid,$uid){
        $sql = mysqli_query(self::$mysql,"SELECT `id`
        FROM `".DB_PREFIX."chat_member`
        WHERE `cid`='".$cid."'
        AND `uid`='".$uid."'
        AND `ban`='".Yes."'");
        $data = mysqli_fetch_array($sql);

        if(empty($data))
            return null;
        else
            return $data;
    }

    function kick($cid,$uid){
        mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_member`
        WHERE `cid`='".$cid."'
        AND `uid`='".$uid."'");
    }

    function updateConfig($key,$value){
        mysqli_query(self::$mysql,"UPDATE `".DB_PREFIX."chat_userConfig` SET `value`='".mysqli_escape_string(self::$mysql,$value)."' WHERE `uid`='".(int)$this->user['user_id']."' AND `key`='".mysqli_escape_string(self::$mysql,$key)."'");
    }

    function getConfig($key){}//not in use. only for websocket (yey);
    function update(){}//not in use
}