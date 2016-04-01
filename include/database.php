<?php
/**
Database handle the connection from server to mysql and back
*/
class Database{
  private static $connection;

  /**
   connect to the database.
  */
  public function connect(array $data){
    $con = self::$connection = new mysqli($data["host"], $data["user"], $data["pass"], $data["data"]);
    if($con->connect_error)
      return $con->connect_error;

    return false;
  }
}
