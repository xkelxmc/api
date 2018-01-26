<?php
/**
 * Created by PhpStorm.
 * User: xkelx
 * Date: 10.11.2017
 * Time: 1:05
 */

namespace lib;


use PDO;

class DB
{
    private $dbhost = "";
    private $dbuser = "";
    private $dbpass = "";
    private $dbname = "";

    public function __construct($dbhost = DB_SERVER, $dbuser = DB_USERNAME, $dbpass = DB_PASSWORD, $dbname = DB_DATABASE)
    {
        $this->dbhost = $dbhost;
        $this->dbuser = $dbuser;
        $this->dbpass = $dbpass;
        $this->dbname = $dbname;
    }

    public function connect()
    {
        $dbConnection = new PDO("mysql:host=".$this->dbhost.";dbname=".$this->dbname, $this->dbuser, $this->dbpass);
        $dbConnection->exec("set names utf8");
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }
}