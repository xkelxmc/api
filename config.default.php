<?php
//ob_start("ob_gzhandler");
 error_reporting(0);
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

session_start();

/* DATABASE CONFIGURATION */
define('DB_SERVER', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_DATABASE', '');
define("BASE_URL", '');
define("SITE_KEY", '');
define("FILE_URL", 'https://legendcity.ru/api/file/index.php?id=');
define("USER_PHOTO_URL", 'https://legendcity.ru/api/resize_crop.php?type=user&w=200&h=200&id=');


function getDB() 
{
	$dbhost=DB_SERVER;
	$dbuser=DB_USERNAME;
	$dbpass=DB_PASSWORD;
	$dbname=DB_DATABASE;
	$dbConnection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbConnection->exec("set names utf8");
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}
/* DATABASE CONFIGURATION END */

/* API key encryption */
function apiToken($session_uid)
{
	$key=md5(SITE_KEY.$session_uid);
	return hash('sha256', $key);
}



?>