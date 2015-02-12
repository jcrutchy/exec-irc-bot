<?php

#####################################################################################################

define("DB_SCHEMA","news_my_to");
define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASSWORD",trim(file_get_contents("../../../pwd/mysql_www")));

$pdo=new PDO("mysql:host=".DB_HOST,DB_USER,DB_PASSWORD);
if ($pdo===False)
{
  die("ERROR CONNECTING TO DATABASE");
}

#####################################################################################################

function db__get_all_stories()
{
  global $pdo;
  $sql="SELECT * FROM ".DB_SCHEMA."stories ORDER BY sid";
  $statement=$pdo->query($sql);
  if ($statement===False)
  {
    die("SQL ERROR\n\n$sql");
  }
}

#####################################################################################################

?>
