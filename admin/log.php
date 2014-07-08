<?php
require_once "../../../maincore.php";
require_once INFUSIONS."chat/locale/".str_replace("/",null,LOCALESET).".php";
require_once INFUSIONS."chat/lib/db.php";
require_once INFUSIONS."chat/infusion_db.php";
require_once INFUSIONS."chat/lib/define.php";
require_once INCLUDES."infusions_include.php";

if (!checkrights("CHADLOG") || !defined("iAUTH") || $_GET['aid'] != iAUTH) { redirect("../chat.php"); }

$config = get_settings("chat");

function getCommandType($message){
	$sp = explode(" ",$message);
	$sp = $sp[0];
	return str_replace("/",null,$sp);
}

if(!empty($_GET['getLog']) && trim($_GET['getLog']) && preg_check("/^#/", $_GET['getLog'])){
	//start log :)
	$log = array();
	$log[] = "Log for ".$_GET['getLog'];
	$log[] = "Createt ".showdate("forumdate", time());
	$log[] = "<-- Start log -->";
	$log[] = null;//make empty line ;)
	
	$r = dbquery("SELECT ms.message AS message, ms.time AS mtime, user.nick AS nick,ms.isBot AS isBot
	FROM `".DB_CHATMESSAGE."` AS ms
	LEFT JOIN `".DB_CHATMEMBER."` AS cm ON cm.cid=ms.cid
	LEFT JOIN `".DB_USERS."` AS user ON user.user_id=ms.uid
	LEFT JOIN `".DB_CHATNAME."` AS cn ON ms.cid=cn.id
	WHERE cn.name='".cleanMySQL($_GET['getLog'])."'
	AND ms.isMsg='".No."'
	AND cn.id<>'1'");
	while($row = dbarray($r)){
		$time = showdate("forumdate",strtotime($row['mtime']));
		if(preg_check("/^\//", $row['message'])){
			switch (getCommandType($row['message'])){
				case 'join':
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onJoin'],$row['nick']);
				break;
				case 'notInaktiv':
					preg_match("/^\/notInaktiv\s(.*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onNotInaktiv'],$reg[1]);
				break;
				case 'inaktiv':
					preg_match("/^\/inaktiv\s(.*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onInaktiv'],$reg[1]);
				break;
				case 'leave':
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onLeave'],$row['nick']);
				break;
				case 'exit':
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onExit'],$row['nick']);
				break;
				case 'nick':
					preg_match("/\/nick\s([a-zA-Z0-9]*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onNick'],$reg[1],$row['nick']);
				break;
				case 'title':
					preg_match("/^\/title\s(.*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onTitle'],$config['botUserName'],$reg[1]);
				break;
				case 'kick':
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onKick'],$row['nick']);
				break;
				case 'ban':
					preg_match("/^\/ban\s(.*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onBan'],$row['nick'],$reg[1]);
				break;
				case 'unban':
					preg_match("/^\/unban\s(.*?)$/",$row['message'],$reg);
					$log[] = "[".$time."]".$config['botUserName'].": ".sprintf($locale['onUnBan'],$reg[1]);
				break;
			}
		}else{
			if($row['isBot'] == Yes){
				$log[] = "[".$time."]".$config['botUserName'].": ".$row['message'];
			}else{
				$log[] = "[".$time."]".$row['nick'].": ".$row['message'];
			}
		}
	}
	
	$log[] = null;
	$log[] = "<-- end log -->";
	$log[] = "Note nick may differ from the unit at the time punk shown in this log";
	
	
	$string = "#".implode("\r\n#",$log);
	require_once INCLUDES."class.httpdownload.php";
	$obj = new httpdownload();
	if(!$obj->set_bydata($string)){
		exit("Log error");
	}
	
	$obj->set_filename(str_replace("#",null,$_GET['getLog']).".log");
	$obj->download();
	exit;
}
require_once THEMES."templates/admin_header.php";

opentable($locale['dawnlordLog']);
echo "<table class='center' style='width:600px;'>";
 $sql = dbquery("SELECT `name`,`id` FROM `".DB_CHATNAME."` WHERE `id`<>'1'");
 while($row = dbarray($sql)){
 	echo "<tr>";
 	 echo "<th class='tbl' style='width:30px;'>".$row['name']."</th>";
 	 echo "<td class='tbl' style='width:70px;'><a href='?aid=".$_GET['aid']."&amp;getLog=".urlencode($row['name'])."'>".$locale['dawnlordLog']."</a></td>";
 	echo "</tr>";
 }
echo "</table>";
closetable();

require_once THEMES."templates/footer.php";