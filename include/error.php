<?php
//this is handling the error 
function init_error($callback = null){
	set_error_handler(function($number, $message, $file, $line) use($callback){
		if($callback != null){
			$callback($number, $message, $file, $line);
		}
		echo "System error:\r\n";
		echo $message."\r\n";
		echo "[line]".$line."\r\n";
		echo "[file]".$file;
		ShoutDown::onEnd();
		exit;
	});
}