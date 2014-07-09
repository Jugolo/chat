<?php
if (!defined("IN_FUSION")) { die("Access Denied"); }

if (file_exists(INFUSIONS."chat/locale/".$settings['locale'].".php")) {
	// Load the locale file matching the current site locale setting.
	include INFUSIONS."chat/locale/".$settings['locale'].".php";
} else {
	// Load the infusion's default locale file.
	include INFUSIONS."chat/locale/English.php";
}

include INFUSIONS."chat/infusion_db.php";
if(!defined("No")){
	include INFUSIONS."chat/lib/define.php";
}

//vi har nu henet sprog filerne 
// Infusion general information
$inf_title = $locale['chatTitle'];
$inf_description = $locale['chatDec'];
$inf_version = "2.00";
$inf_developer = "Jugolo.dk";
$inf_email = "";
$inf_weburl = "http://jugolo.dk";

$inf_folder = "chat";

$inf_newtable[1] = DB_CHATMESSAGE." (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `isBot` tinyint(1) NOT NULL,
  `time` datetime NOT NULL,
  `message` text NOT NULL,
  `messageColor` varchar(255) NOT NULL,
  `isMsg` int(1) NOT NULL,
  `msgTo` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$inf_newtable[2] = DB_CHATMEMBER."(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `lastActiv` datetime NOT NULL,
  `isInAktiv` int(1) NOT NULL,
  `ban` int(11) NULL,
  `banTo` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$inf_newtable[3] = DB_CHATNAME."(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `isPriv` tinyint(1) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `setTitle` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$inf_newtable[4] = DB_CHATUCONFIG."(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$inf_newtable[5] = DB_IGNOERE."(
`id` int(11) NOT NULL AUTO_INCREMENT,
`uid` int(11) NOT NULL,
`ignore` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM;";

$inf_insertdbrow[1]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('startChannel', '#Jugolo', '".$inf_folder."')";
$inf_insertdbrow[2]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('timer', '2500', '".$inf_folder."')";
$inf_insertdbrow[3]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('botUserName','Bot','".$inf_folder."')";
$inf_insertdbrow[4]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('botUserColor', 'red','".$inf_folder."')";
$inf_insertdbrow[5]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('botTextColor','red','".$inf_folder."')";
$inf_insertdbrow[6]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('bColorOne', '#C6C6C6', '".$inf_folder."')";
$inf_insertdbrow[7]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('bColorToo', '#828282', '".$inf_folder."')";
$inf_insertdbrow[8]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('textColor', '[#009900][Green],[#FF0000][Red],[#000000][Black]','".$inf_folder."')";
$inf_insertdbrow[9]  = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('leave','10','".$inf_folder."')";
$inf_insertdbrow[10] = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('inaktiv','5','".$inf_folder."')";
$inf_insertdbrow[11] = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('protokol','ajax','".$inf_folder."')";
$inf_insertdbrow[12] = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('socketServer','','".$inf_folder."')";
$inf_insertdbrow[13] = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('socketPort','','".$inf_folder."')";
$inf_insertdbrow[15] = DB_SETTINGS_INF." (settings_name, settings_value, settings_inf) VALUES('maxNickLengt','3','".$inf_folder."')";

$inf_insertdbrow[16] = DB_CHATNAME." (`id`,`name`,`isPriv`,`uid`,`title`) VALUES('1','Bot','".No."','0','Channel to connect system and user togeter')";


$inf_droptable[1] = DB_CHATMESSAGE;
$inf_droptable[2] = DB_CHATMEMBER;
$inf_droptable[3] = DB_CHATNAME;
$inf_droptable[4] = DB_CHATUCONFIG;

$inf_deldbrow[1] = DB_SETTINGS_INF." WHERE settings_inf='".$inf_folder."'";
$inf_deldbrow[2] = DB_ADMIN." WHERE admin_rights='CHAD' OR admin_rights='CHADBAN' OR admin_rights='CHADLOG' OR admin_rights='CHADSOCKET'";

$inf_adminpanel[1] = array(
  'title'  => 'Chat setting',
  'image'  => '',
  'panel'  => 'admin/setting.php',
  'rights' => 'CHAD'
);

$inf_adminpanel[2] = array(
  'title'  => 'Chat bans',
  'image'  => '',
  'panel'  => 'admin/ban.php',
  'rights' => 'CHADBAN'
);

$inf_adminpanel[3] = array(
  'title'  => 'Chat log',
  'image'  => '',
  'panel'  => 'admin/log.php',
  'rights' => 'CHADLOG'
);

$inf_adminpanel[4] = array(
	'title'  => 'Chat WebSocket',
	'image'  => '',
	'panel'  => 'admin/websocket.php',
	'rights' => 'CHADSOCKET',
);

//i can not find other way to do this. if you have one so sey it :)
if(isset($_POST['infusion'])){
	dbquery("ALTER TABLE `".DB_USERS."` ADD `nick` VARCHAR(255) NULL DEFAULT NULL AFTER `user_name`");
}

if (isset($_GET['defuse'])) {
    dbquery("ALTER TABLE `".DB_USERS."` DROP `nick`");
}

