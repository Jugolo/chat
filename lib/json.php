<?php 
class Json{
    private static $json = array();
    
	static function location($url){
		self::$json['location'] = $url;
		self::show();
    }
	
	static function alert($msg){
		self::$json['alert'] = empty(self::$json['alert']) ? $msg : self::$json['alert']."\r\n".$msg;
	}
    
    static function addBlock($key,$value){
    	self::$json[$key] = $value;
    }
    
    static function show($exit = true){
        if(!headers_sent())
            header('Content-type: application/json');

        echo json_encode(self::$json);
		if($exit){
			exit;
		}
    }
	
	static function unset_json(){
		self::$json = array();
	}
}