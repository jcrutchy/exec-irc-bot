<?php

#####################################################################################################

define("DB_SCHEMA","news_my_to");
define("DB_HOST","localhost");
define("DB_USER","www");
define("DB_PASSWORD",trim(file_get_contents(__DIR__."/../../../pwd/mysql_www")));

$pdo=new PDO("mysql:host=".DB_HOST,DB_USER,DB_PASSWORD);
if ($pdo===False)
{
  die("ERROR CONNECTING TO DATABASE");
}

#####################################################################################################

function db__get_all_stories()
{
  global $pdo;
  $sql="SELECT * FROM ".DB_SCHEMA.".stories ORDER BY sid";
  $statement=$pdo->query($sql);
  if ($statement===False)
  {
    die("SQL QUERY ERROR\n\n$sql");
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

function db__insert_story($story)
{
  global $pdo;
  $sql="INSERT INTO ".DB_SCHEMA.".stories (title,content) VALUES (:title,:content)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    die("SQL PREPARE ERROR\n\n$sql");
  }
  $statement->bindParam(":title",$story["title"],PDO::PARAM_STR);
  $statement->bindParam(":content",$story["content"],PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    die("SQL EXECUTE ERROR\n\n$sql");
  }
}

#####################################################################################################

?>
