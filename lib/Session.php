<?php
namespace lib;


use PDO;

class Session
{
    public function __construct()
    {

    }
    public function set($key, $param, $value){
        $session_value = $this->get($key, $param);
        if($session_value){
            $this->update($key, $param, $value);
        }else{
            $this->insert($key, $param, $value);
        }
    }

    public function get($key, $param){
        global $DB;
        $sql = "SELECT value FROM b_mobile_session WHERE `key` = :key AND param=:param";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->bindParam("param", $param, PDO::PARAM_STR);
        $stmt->execute();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        return ($userData) ? $userData->value : false;
    }
    public function insert($key, $param, $value){
        global $DB;
        $date = time();
        $sql = <<<SQL
INSERT INTO b_mobile_session 
SET `key` = :key, `param` = :param, `value` = :value, `date` = :date
SQL;
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->bindParam("param", $param, PDO::PARAM_STR);
        $stmt->bindParam("value", $value, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->execute();
    }
    function delete($key, $param){
        global $DB;
        $sql = "DELETE FROM b_mobile_session WHERE `key` = :key AND `param` = :param";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->bindParam("param", $param, PDO::PARAM_STR);
        $stmt->execute();
    }
    public function update($key, $param, $value){
        global $DB;
        $date = time();
        $sql = "UPDATE b_mobile_session SET `value` = :value, `date` = :date WHERE `key` = :key AND param=:param";
        $stmt = $DB->prepare($sql);
        $stmt->bindParam("key", $key, PDO::PARAM_STR);
        $stmt->bindParam("param", $param, PDO::PARAM_STR);
        $stmt->bindParam("value", $value, PDO::PARAM_STR);
        $stmt->bindParam("date", $date, PDO::PARAM_STR);
        $stmt->execute();
    }
}