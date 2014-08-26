<?php
class DatabaseHandler{

    private $head      = null;
    public $isError   = false;
    private $errorData = array();

    function DatabaseHandler($host,$user,$pass,$data){
        $this->head = new mysqli($host,$user,$pass,$data);
        if($this->head->connect_error){
            $this->saveError(
                $this->head->connect_error,
                $this->head->connect_errno
            );
            return;
        }
    }

    function query($sql){
        $result = $this->head->query($sql);
        if($this->strstwidth($sql,"SELECT")){

        }else{
            if(!$result){
                $this->saveError(
                    $this->head->error,
                    $this->head->errno,
                    $sql
                );
            }
        }

        return new DatabaseResult($sql,$this->head,$result);
    }

    function prepare($sql){
        return new DatabasePrepare($sql,$this);
    }

    function clean($context){
        return $this->head->escape_string($context);
    }

    function saveError($errStr,$errNo,$sql = 'null'){
        $this->isError = true;
        $this->errorData[] = array(
            'string' => $errStr,
            'number' => $errNo,
            'sql'    => $sql
        );
    }

    function getError(){
        $return =  "Error in Database:<br>";
        for($i=0;$i<count($this->errorData);$i++){
            $return .= "Error String: ".$this->errorData[$i]['string']."<br>
            Error Number: ".$this->errorData[$i]['number']."<br>
            Sql: ".$this->errorData[$i]['sql'];
        }

        return $return;
    }

    function strstwidth($tekst,$exp){
        return (strpos($tekst,$exp) === 0);
    }

    function lastIndex(){
        return $this->head->insert_id;
    }
}

class DatabaseResult{
    private $sql = null;
    private $main = null;
    private $result = null;

    function DatabaseResult($sql,$main,$result){
        $this->main = $main;
        $this->sql = $sql;
        $this->result = $result;
    }

    function get(){
        return $this->result->fetch_assoc();
    }
}

class DatabasePrepare{
    private $sql = null;
    private $main = null;

    function DatabasePrepare($sql,DatabaseHandler $main){
        $this->sql = $sql;
        $this->main = $main;
    }

    function add($name,$context){
        $this->sql = str_replace(
            "{".$name."}",
            "'".$this->main->clean($context)."'",
            $this->sql
        );
    }

    function done(){
        return $this->main->query($this->sql);
    }
}