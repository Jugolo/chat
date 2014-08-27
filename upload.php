<?php
require_once '../../maincore.php';

if(iGUEST){
    exit();
}

require_once INCLUDES."infusions_include.php";
require_once 'lib/tmp/allow_file.php';
require_once 'infusion_db.php';
require_once 'lib/db.php';

if(!is_dir(DOWNLOADS."chat")){
    mkdir(DOWNLOADS."chat", 755);
}

$json = array();
$name = uniqid();
$upload = upload_file(
    'fil',
    $name,
    DOWNLOADS."chat/",
    implode("|",$file),
    150000
);

switch($upload['error']){
    case 1:
     $json['error'] = "Max size error";
    break;
    case 2:
        $json['error'] = "File type not allow";
    break;
    case 3:
        $json['error'] = "Invalid qury string";
    break;
    case 4:
        $json['error'] = "File not uploadet!";
    break;
    case 0:
        dbquery("INSERT INTO `".DB_CHATFILE."` (
        `name`,
        `url`
        ) VALUES (
        '".cleanMySQL($upload['target_file'])."',
        '".cleanMySQL(DOWNLOADS."chat/".$upload['target_file'])."'
        )");
        $json['fileID'] = mysql_insert_id();
    break;
}

exit(json_encode($json));