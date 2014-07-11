<?php
interface ProtokolHead{
    public function Protokol($mysqli,Server $server);
    public function get_channel_by_id($id);
    public function get_ignore($uid = null);
    public function add_ignore($uid);
    public function remove_ignore($uid);
    public function set_user_data($data,$client);
    public function get_channel_list();
    public function get_my_channel_list();
    public function add_to_channel($cid);
    public function new_channel($name,$title = null,$isSystem = false);
    public function get_flood($cid);
    public function update_flood($flood,$cid);
    public function update_nick($newNick);
    public function getUserInChannel($cid,$nick);
    public function getBannetInChannel($cid);
    public function banUser($cid,$uid,$banTo,$save = true);
    public function remove_ban($uid,$cid,$id,$remove = true);
    public function getBanId($cid,$uid);
    public function kick($cid,$uid);
    public function updateConfig($key,$value);
    public function getConfig($key);
    public function update();//happens evay time admin write /update
}