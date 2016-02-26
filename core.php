<?php
function getCoreModulDir($name){
  return "core/module/core_module_".$name.".php";
}

//Control if wee got core config file
if(!file_exsist("core_config.json")){
  exit("Missing the chat core config file. The file tell this system how to work. Install the chat to let this page work");
}

$coreConfig = json_decode(file_get_contents("core_config.json"));

//wee need to get list of core modules
if(empty($coreConfig["core"]) || empty($coreConfig["core"]["module"])){
   exit("missing core module list in core config file!!");
}

//wee need to get every node in the list where wee are handling the module init it soo wee got plugin an so on.
foreach($coreConfig["core"]["module"] as $name => $data){
   $dir = getCoreModulDir($name);

   //wee control if the core file exsist.
   if(!file_exsists($dir)){
      //noo this module is not exists. Wee move to new page to install this module if there is install url in this module else it will show a error message
      header("location: ".$coreConfig["core"]["modulInstall"]);
      exit;
   }
}
