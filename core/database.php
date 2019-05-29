<?php
require_once(__DIR__ . DIRECTORY_SEPARATOR . '../config.php');

function getDBConnection() {
	$dbparam = getDBParam();
	$db = new PDO(sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", $dbparam["dbinstance"], $dbparam["dbname"]), $dbparam["dbusername"], $dbparam["dbpassword"]);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	return $db;
}
?>