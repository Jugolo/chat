<?php
/**
Database handle the connection from server to mysql and back
*/
class Database{
  private static $connection;
  public static $prefix;

  /**
   connect to the database.
  */
  public static function connect(array $data){
  	if(!class_exists("mysqli")){
  		exit("This server uses mysqli class. it is not supportet in this serve.\r\n please install it on the server and try agin");
  	}
  	
    $con = self::$connection = new mysqli($data["host"], $data["user"], $data["pass"], $data["data"]);
    if($con->connect_error)
      return $con->connect_error;
    
    $con->set_charset("UTF-8");
     
    self::$prefix = $data["prefix"];
    ShoutDown::add(function(){
    	Database::close();//close the connection when wee are done
    });
    
    return false;
  }

  public static function query($request){
      $query = self::$connection->query($request);
      if(is_bool($query) || $query == null)
        return $query;

      return new DatabaseResult($query);
  }
  
  public static function error(){
  	return [self::$connection->error, self::$connection->errno];
  }

  public static function insert($table, array $data){
     $result = self::query($sql = self::createInsert($table, $data));
     if(!$result){
     	echo "[SQL Insert error]\r\n";
     	echo "[Message]".self::$connection->error."\r\n";
     	echo "[Number] ".self::$connection->errno."\r\n";
     	echo "[SQL]    ".$sql."\r\n";
     	exit;
     }
  }

  public static function qlean($item){
     return "'".self::$connection->real_escape_string($item)."'";
  }
  
  public static function close(){
  	self::$connection->close();
  }

  private static function createInsert($table, array $data){
     $row   = [];
     $value = [];

     foreach($data as $key => $values){
        $row[]   = $key;
        if(is_array($values))
        	$value = $values[0];
        else
        	$value[] = self::qlean($values);
     }

     return "INSERT INTO `".table($table)."` (`".implode("`,`", $row)."`) VALUES (".implode(",", $value).")";
  }
}

function table($name){
  return Database::$prefix."chat_".$name;
}

class DatabaseResult{
   private $result;

   public function __construct(mysqli_result $result){
      $this->result = $result;
   }

   public function rows(){
      return $this->result->num_rows;
   }

   public function fetch(){
      return $this->result->fetch_assoc();
   }
}
