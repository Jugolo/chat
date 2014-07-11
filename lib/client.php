<?php
function jugoloChatClientReturnUserStat(){
    global $userdata;

    if(empty($userdata['nick'])){
        dbquery("UPDATE `".DB_USERS."` SET `nick`='".cleanMySQL($userdata['user_name'])."' WHERE `user_id`='".cleanMySQL($userdata['user_id'])."'");
        dbquery("INSERT INTO `".DB_CHATUCONFIG."` (`uid`,`key`,`value`) VALUES ('".cleanMySQL($userdata['user_id'])."','sound','true')");
        dbquery("INSERT INTO `".DB_CHATUCONFIG."` (`uid`,`key`,`value`) VALUES ('".cleanMySQL($userdata['user_id'])."','textColor','#000000')");
        dbquery("INSERT INTO `".DB_CHATUCONFIG."` (`uid`,`key`,`value`) VALUES ('".cleanMySQL($userdata['user_id'])."','lang','".cleanMySQL(str_replace("/",null,LOCALESET))."')");
        dbquery("INSERT INTO `".DB_CHATUCONFIG."` (`uid`,`key`,`value`) VALUES ('".cleanMySQL($userdata['user_id'])."','time','H:i')");
        dbquery("INSERT INTO `".DB_CHATMEMBER."`  (`uid`,`cid`,`lastActiv`,`isInAktiv`,`ban`) VALUES ('".cleanMySQL($userdata['user_id'])."','1','0','0','".No."')");
        $userdata['nick'] = $userdata['user_name'];
    }

    function jugoloChatClientReturnCookie(){
        $c = $_COOKIE;
        $return  = array();
        foreach($c AS $key => $value){
            $return[] = urlencode($key)."=".urlencode($value);
        }
        return implode(";",$return);
    }

    return "
    'nick'  : '".$userdata['nick']."',
    'id'    : '".$userdata['user_id']."',
    'sort'  : '".(int)$userdata['user_level']."',
    'mobil' : false,
    'lang'  : '".str_replace("/",null,LOCALESET)."'
    ";
}

function jugoloChatClientReturnBotStat(){
    global $config;

    return "
    'name'  : '".$config['botUserName']."',
    'color' : '".$config['botUserColor']."',
    'text'  : '".$config['botTextColor']."'
    ";
}

function jugoloChatClientReturnBoxColorArray(){
    global $config;

    return "'".$config['bColorOne']."','".$config['bColorToo']."'";
}

function jugoloChatClientOnStart(){
    global $userdata;

    $sql = dbquery("SELECT `cid` FROM `".DB_CHATMEMBER."` WHERE `uid`='".(int)$userdata['user_id']."' AND `cid` <> '1'");
    while($row = dbarray($sql)){
        dbquery("INSERT INTO `".DB_CHATMESSAGE."` (
        `uid`,
        `cid`,
        `isBot`,
        `time`,
        `message`,
        `messageColor`,
        `isMsg`,
        `msgTo`
        ) VALUE (
        '".(int)$userdata['user_id']."',
        '".(int)$row['cid']."',
        '".No."',
        NOW(),
        '/exit',
        'red',
        '".No."',
        '0'
        )
        ");
    }

    dbquery("DELETE FROM `".DB_CHATMEMBER."` WHERE `uid`='".(int)$userdata['user_id']."' AND `cid`<>'1' AND `ban`='".No."'");
}

function jugoloChatCLientAddSmylie(){
    global $smiley_cache;

    $use = null;
    for($i=0;$i<count($smiley_cache);$i++){
        $use .= "system.setSmylie('".addslash($smiley_cache[$i]['smiley_text'])."','".addslash($smiley_cache[$i]['smiley_code'])."','".addslash(IMAGES."smiley/".$smiley_cache[$i]['smiley_image'])."','".addslash(preg_quote($smiley_cache[$i]['smiley_code']))."')\r\n";
    }

    return $use;
}

function jugoloChatClientGetLastId(){
    $li = dbarray(dbquery("SELECT id FROM `".DB_CHATMESSAGE."` ORDER BY id DESC LIMIT 1;"));
    return $li['id'];
}

function getStartChannel(){
    if(isset($_GET['sChannel']) && trim($_GET['sChannel'])){
        return $_GET['sChannel'];
    }

    global $config;
    return $config['startChannel'];
}

function loadLang($main,$isNotFound,&$locale){
    $dir = INFUSIONS."chat/locale/".$main.".php";
    if(!file_exists($dir)){
        loadLang($isNotFound,"English",$locale);
    }else{
        include $dir;
    }
}