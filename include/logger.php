<?php
class Logger{
	private $buffer = null;
	private $id = null;
	
	function __construct(ChannelData $data){
		//control if the logge exits
		$query = Database::query("SELECT * FROM `".table("logger")."` WHERE `channel`='".$data->name()."'");
		if($query->rows() == 1){
			$this->init($query->fetch());
		}else{
			$this->create($data);
		}
	}
	
	function appendLine($line){
		$line = $line."\r\n";
		fwrite($this->buffer, $line, strlen($line));
	}
	
	function close(){
		if($this->buffer != null){
			fclose($this->buffer);
		}
	}
	
	function end(){
		Database::query("UPDATE `".table("logger")."` SET `closed`=NOW() WHERE `id`='".$this->id."'");
		$this->close();
	}
	
	private function create(ChannelData $data, $prefix = null){
		if($prefix != null)
			$name = $prefix."_".$data->name();
		else 
			$name = $data->name();
		
		$name .= ".txt";
		
		//controle if the data is exits 
		$query = Database::query("SELECT * FROM `".table("logger")."` WHERE `file_name`='".$name."'");
		if($query->rows() != 0){
			$this->create($data, (is_numeric($prefix) ? $prefix++ : 1));
			return;
		}
		
		Database::insert("logger", [
				'channel'   => $data->name(),
				'file_name' => $name,
				'channel'   => $data->name(),
				'dir'       => realpath(dirname(__FILE__)."/log/"),
		]);
		
		$this->init(Database::query("SELECT * FROM `".table("logger")."` WHERE `channel`='".$data->name()."'")->fetch());
	}
	
	private function init(array $data){
		$buffer=$this;
		ShoutDown::add(function() use($buffer){
			$buffer->close();
		});
		$file = $data["dir"]."/".$data["file_name"];
		if(file_exists($file))
			$type = "a+";
		else 
			$type = "w+";
		
	    $this->buffer = fopen($file, $type);
	    $this->id = $data["id"];
	}
}