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
  public function connect(array $data){
    $con = self::$connection = new mysqli($data["host"], $data["user"], $data["pass"], $data["data"]);
    if($con->connect_error)
      return $con->connect_error;
     
    self::$prefix = $data["prefix"];
    return false;
  }

  public static function query($request){
      $query = self::$connection->query($request);
      if(is_bool($query))
        return $query;

      return new DatabaseResult($query);
  }

  public static function insert($table, array $data){
     return self::query(self::createInsert($table, $data));
  }

  public static function qlean($item){
     return "'".self::$connection->real_escape_string($item)."'";
  }

  private static function createInsert($table, array $data){
     $row   = [];
     $value = [];

     foreach($data as $key => $values){
        $row[]   = $key;
        $value[] = self::qlean($values);
     }

     return "INSERT INTO `".table($table)."` (`".implode("`,`", $row).") VALUES (".implode(",", $value).")";
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
