<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) 2002 - 2011 Nick Jones
| http://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: chat.php
| Author: Ronnie Stengade Christen (Jugolo/Rix)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
session_start();
require_once '../../maincore.php';
require_once 'infusion_db.php';
require_once THEMES."templates/header.php";
require_once INCLUDES."infusions_include.php";
require_once 'lib/client.php';
require_once 'lib/db.php';
require_once 'lib/define.php';
loadLang(str_replace("/",null,LOCALESET)."php","English",$locale);

$config = get_settings("chat");

if(!iGUEST){
    jugoloChatClientOnStart();
    include 'page/chat.php';
}else{
    opentable("Error!");
    echo "<div id='error' style='text-align:center;'>";
    echo $locale['errorGeaust'];
    echo "</div>";
}

closetable();
require_once THEMES."templates/footer.php";