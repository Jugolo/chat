<?php
class ShoutDown{
	private static $buffer = [];
	
	public static function add($callback){
		self::$buffer[] = $callback;
	}
	
	public static function onEnd(){
		foreach(self::$buffer as $callback){
			$callback();
		}
	}
}