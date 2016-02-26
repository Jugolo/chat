<?php
define("CHAT_CORE_VERSION", "V0.1");

function getCoreModulDir($name){
  return "core/module/core_module_".$name.".php";
}

class CoreModule{
  private static $dataCache = array();//here wee cash the data about the module
  private static $moduleCache = array();//here wee cache the modules

  public static function loadModule($name, $data){
     //first wee include the file :)

     include getCoreModulDir($name);// wee know it exists.

     $cName = self::getClassName($name);

     //control if the class exists
     if(!class_exists($cName)){
       exit("Missing the core module class ".$cName);
     }

     $obj = new $cName();

     if(!($obj instanceof CoreModule)){
       exit("the core module class for ".$name." Is not instance of CoreModule");
     }

     self::$dataCache[$name] = $data;
     self::$moduleCache[$name] = $obj;
  }

  public function trigger($func){
       foreach(self::$moduleCache as $name => $obj){
           $func(self::$dataCache[$name], $obj);
       }
  }

  private static function getClassName($name){
      return "CoreModule_".$name;
  }
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

   CoreModule::loadModule($name, $data);// handle the module soo wee can init the module :)
}

CoreModule::trigger(function($data, $obj){
    $obj->init();
});
