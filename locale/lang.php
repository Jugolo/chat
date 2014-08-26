<?php

header('Content-Type: text/javascript; charset=UTF-8');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

function getLang($dir){
    if(file_exists($dir."/javascript.php")){
        return include($dir."/javascript.php");
    }else{
        return array();
    }
}

function PHPArrayToJavaScriptObject($data){
    $return = "{";

    $i=0;
    foreach($data as $key => $value){
        $return .= "'".$key."' : ";
        if(is_array($value)){
            $return .= PHPArrayToJavaScriptObject($value,false).((count($data)-1) == $i ? null : ',');
        }else{
            $return .= "'".addslashes($value)."'".((count($data)-1) == $i ? null : ',');
        }
        $i++;
    }

    return $return."}";
}

$dir = opendir("./");
$l   = array();

while($file = readdir($dir)){
    if(is_dir($file) && ($file != "." && $file != "..")){
        $l[$file] = getLang($file);
    }
}

echo "var LibLang = ".PHPArrayToJavaScriptObject($l).";";