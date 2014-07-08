<?php
class Protokol{
    public $use_session = true;
    public $protokol    = "Ajax";
    private $channel = array();
    private $my_channel = array();
    private $mysql;
    public $user = array();
    private $ignore = array();
    private $main;

    function set_user_data($array){
        $this->user = $array;
    }

    function add_ignore($uid){
        $this->ignore[] = $uid;
    }

    function remove_ignore($uid){
        if(($key = array_search($uid,$this->get_ignore())) !== false){
            unset($this->ignore[$key]);
        }
    }

    function get_ignore($only_cahce = true){
        if(!$only_cahce || empty($this->ignore)){
            $sql = mysqli_query($this->mysql,"SELECT `ignore` FROM `".DB_PREFIX."chat_ignore` WHERE `uid`='".(int)$this->user['user_id']."'");
            while($row = mysqli_fetch_array($sql)){
                $this->ignore[] = $row['ignore'];
            }
        }

        return $this->ignore;
    }

    function Protokol($mysql,Server $server){
        $this->mysql = $mysql;
        $this->main = $server;
    }

    function get_channel_list($onlye_cache = true){
        if(!$onlye_cache){
            $sql = mysqli_query($this->mysql,"SELECT * FROM `".DB_PREFIX."chat_name`");
            while($row = mysqli_fetch_array($sql)){
                $data = $row;
                unset($data['id']);
                $this->channel[$row['id']] = $data;
            }
        }

        return $this->channel;
    }

    function add_to_channel($cid){
        mysqli_query($this->mysql,"INSERT INTO `".DB_PREFIX."chat_member`
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

        $id = mysqli_insert_id($this->mysql);
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

        $sql = mysqli_query($this->mysql,"SELECT * FROM `".DB_PREFIX."chat_member` WHERE `uid`='".$this->user['user_id']."'");
        while($row = mysqli_fetch_array($sql)){
            $data = $row;
            unset($data['id']);
            $this->my_channel[$row['id']] = $data;
        }

        return $this->my_channel;
    }


    function new_channel($name,$title = null){
        if($title === null){
            $title = $name;
        }

        mysqli_query($this->mysql,"INSERT INTO `".DB_PREFIX."chat_name`
         (
         `name`,
         `isPriv`,
         `uid`,
         `title`,
         `setTitle`
         ) VALUE (
         '".mysqli_escape_string($this->mysql,$name)."',
         '".No."',
         '0',
         '".mysqli_escape_string($this->mysql,$title)."',
         '".$this->user['user_id']."'
         )");

        $id = mysqli_insert_id($this->mysql);
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

    function remove_ban($id){
        mysqli_query(self::$mysql,"DELETE FROM `".DB_PREFIX."chat_member` WHERE `id`='".$id."'");
        unset($this->my_channel[$id]);
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
}