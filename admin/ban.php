<?php
session_start();

require_once "../../../maincore.php";
require_once THEMES."templates/admin_header.php";
require_once INFUSIONS."chat/lib/client.php";
loadLang(str_replace("/",null,LOCALESET),"English",$locale);
require_once INFUSIONS."chat/lib/db.php";
require_once INFUSIONS."chat/infusion_db.php";
require_once INFUSIONS."chat/lib/define.php";
require_once INCLUDES."infusions_include.php";

if (!checkrights("CHAD") || !defined("iAUTH") || $_GET['aid'] != iAUTH){
    redirect("../chat.php");
}

$unban = isset($_SESSION['unban']) ? true : false;
if($unban){
    unset($_SESSION['unban']);
}

if(!empty($_GET['unban']) && $_GET['unban'] == "true" && isset($_GET['cmid']) && isnum($_GET['cmid'])){
    dbquery("DELETE FROM `".DB_CHATMEMBER."` WHERE `id`='".(int)$_GET['cmid']."'");
    $_SESSION['unban'] = true;
    redirect("?aid=".$_GET['aid']);
}

$config   = get_settings("chat");
if(empty($_GET['p']) || !isnum($_GET['p'])){
    $num = 0;
}else{
    $num = (int)$_GET['p'];
}

//before wee show bans wee remove all old :)
$sql = dbquery("SELECT cm.id AS id,cm.cid AS cid,cm.uid AS uid, user.nick AS nick
FROM ".DB_CHATMEMBER." AS cm
LEFT JOIN ".DB_USERS." AS user ON user.user_id = cm.uid
WHERE cm.cid <> '1'
AND cm.ban = '".Yes."'
AND cm.banTo < '".time()."'");
while($row = dbarray($sql)){
    dbquery("INSERT INTO `".DB_CHATMESSAGE."`
    (
     `uid`,
     `cid`,
     `isBot`,
     `time`,
     `message`,
     `messageColor`,
     `isMsg`,
     `msgTo`
     ) VALUES (
     '".(int)$row['uid']."',
     '".(int)$row['cid']."',
     '".Yes."',
     NOW(),
     '/unban ".cleanMySQL($row['nick'])."',
     'green',
     '".No."',
     '0'
     )");

    dbquery("DELETE FROM `".DB_CHATMEMBER."` WHERE `id`='".(int)$row['id']."'");
}

$rows = dbcount("(id)", DB_CHATMEMBER, "`ban`='".Yes."'");
$sql   = dbquery("SELECT count(cid) AS c FROM `".DB_CHATMEMBER."` WHERE `ban`='".Yes."' AND `cid`<>'1'");
$count = dbarray($sql);

opentable("Chat ban");
echo "<table class='center' style='width:600px'>";
if($unban){
    echo "<tr>";
    echo "<th class='tbl' style='background-color:green' colspan='4'>".$locale['unbanOkay']."</th>";
    echo "</tr>";
}

if($count['c'] == 0){
    echo "<tr>";
    echo "<th class='tbl' style='background-color:red'>".$locale['noBan']."</th>";
    echo "</tr>";
}else{
    $sql = dbquery("SELECT user.nick AS nick, cm.banTo AS banTo, cn.name AS name, cm.id AS cmid
    FROM ".DB_CHATMEMBER." AS cm
    LEFT JOIN ".DB_USERS." AS user ON user.user_id = cm.uid
    LEFT JOIN ".DB_CHATNAME." AS cn ON cn.id = cm.cid
    WHERE cm.ban='".Yes."'
    AND cm.cid<>'1'
    LIMIT ".$num.",10");
    while($row = dbarray($sql)){
        echo "<tr>";
        echo "<th class='tbl' style='width:25px;'>".$row['nick']."</th>";
        echo "<th class='tbl' style='width:25px;'>".showdate("forumdate", $row['banTo'])."</th>";
        echo "<th class='tbl' style='width:25px;'>".$row['name']."</th>";
        echo "<th class='tbl' style='width:25px;'><a href='?aid=".$_GET['aid']."&amp;cmid=".$row['cmid']."&amp;unban=true'>".$locale['unban']."</a></th>";
        echo "</tr>";
    }
}

echo "<tr>";
echo "<th class='tbl' colspan='4'>";
echo  makepagenav($num,10,$rows,3,FUSION_SELF."?aid=".$_GET['aid']."&amp;","p");
echo "</th>";
echo "</tr>";
echo "</table>";
closetable();

require_once THEMES."templates/footer.php";