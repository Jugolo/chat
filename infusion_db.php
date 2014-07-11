<?php
if (!defined("IN_FUSION")) { die("Access Denied"); }

if (!defined("DB_CHATMESSAGE")) {
    define("DB_CHATMESSAGE", DB_PREFIX."chat_message");
}

if(!defined("DB_CHATMEMBER")){
    define("DB_CHATMEMBER", DB_PREFIX."chat_member");
}

if(!defined("DB_IGNOERE")){
    define("DB_IGNOERE", DB_PREFIX."chat_ignore");
}

if(!defined("DB_CHATNAME")){
    define("DB_CHATNAME", DB_PREFIX."chat_name");
}

if(!defined("DB_CHATUCONFIG")){
    define("DB_CHATUCONFIG", DB_PREFIX."chat_userConfig");
}