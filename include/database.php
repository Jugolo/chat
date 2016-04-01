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
}

function table($name){
  return Database::$prefix.$name;
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
