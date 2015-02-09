<?php

define("DB_SCHEMA","news_my_to");
define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASSWORD",file_get_contents("../../../../pwd/mysql"));

$pdo=new PDO("mysql:host=".DB_HOST,DB_USER,DB_PASSWORD);
if ($pdo===False)
{
  die("ERROR CONNECTING TO MYSQL SERVER");
}
$pdo->exec("DROP DATABASE IF EXISTS ".DB_SCHEMA);
$result=$pdo->exec("CREATE DATABASE ".DB_SCHEMA);
if ($result===False)
{
  die("ERROR CREATING DATABASE");
}

DROP TABLE IF EXISTS `EDR_Sections`.`EDR_UA`;

?>
