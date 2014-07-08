<?php

$channelsCache = null;

/**
 * @return array contain all channels in database!
 */
function get_channel(){
    $channels = array();

    $sql = dbquery("SELECT `id`,`name` FROM `".DB_PREFIX."chat_name` WHERE `id`<>'1'");
    while($row = dbarray($sql)){
        $channels[] = array(
            'cid'  => $row['id'],
            'name' => $row['name']
        );
    }

    return $channels;
}


function bot_message($cid,$message,$messageColor = 'black'){
    global $channelsCache;

    if($channelsCache === null){
        $c = get_channel();
        $channelsCache = array();
        for($i=0;$i<count($c);$i++)
            $channelsCache[] = $c[$i]['cid'];
    }

    if(!in_array($cid,$channelsCache))
        return false;

    dbquery("INSERT INTO `".DB_PREFIX."chat_message` (
    `uid`,
    `cid`,
    `isBot`,
    `time`,
    `message`,
    `messageColor`,
    `isMsg`,
    `msgTo`
    ) VALUES (
    '0',
    '".(int)$cid."',
    '1',
    NOW(),
    '".mysql_real_escape_string($message)."',
    '".$messageColor."',
    '2',
    '0'
    )");

    return true;
}