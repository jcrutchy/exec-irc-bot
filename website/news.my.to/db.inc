<?php

#####################################################################################################

define("DB_HOST","localhost");
define("DB_USER","www");
define("DB_PASSWORD",trim(file_get_contents(__DIR__."/../../../pwd/mysql_www")));
define("SQL_PATH",__DIR__."/mysql");

$pdo=new PDO("mysql:host=".DB_HOST,DB_USER,DB_PASSWORD);
if ($pdo===False)
{
  die("ERROR CONNECTING TO DATABASE");
}

#####################################################################################################

function db__get_all_stories()
{
  return db__fetch_query("db__get_all_stories");
}

#####################################################################################################

function db__get_story($sid)
{
  $params=array("sid"=>$sid);
  $stories=db__fetch_prepare("db__get_story",$params);
  if (isset($stories[0])==True)
  {
    return $stories[0];
  }
  else
  {
    die("STORY NOT FOUND");
  }
}

#####################################################################################################

function db__get_comments($sid)
{
  $params=array("sid"=>$sid);
  return db__fetch_prepare("db__get_comments",$params);
}

#####################################################################################################

function db__insert_story($story)
{
  $params=array("title"=>$story["title"],"content"=>$story["content"]);
  db__execute_prepare("db__insert_story",$params);
}

#####################################################################################################

function db__insert_comment($comment)
{
  $params=array("nick"=>$comment["nick"],"sid"=>$comment["sid"],"parent_cid"=>$comment["parent_cid"],"subject"=>$comment["subject"],"content"=>$comment["content"],"auth_hash"=>$comment["auth_hash"]);
  db__execute_prepare("db__insert_comment",$params);
}

#####################################################################################################

function db__fetch_query($filename)
{
  global $pdo;
  $sql=file_get_contents(SQL_PATH."/$filename.sql");
  $statement=$pdo->query($sql);
  if ($statement===False)
  {
    die("SQL QUERY ERROR\n\n$sql");
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

function db__execute_prepare($filename,$params)
{
  global $pdo;
  $sql=file_get_contents(SQL_PATH."/$filename.sql");
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    die("SQL PREPARE ERROR\n\n$sql");
  }
  foreach ($params as $key => $value)
  {
    if (ctype_digit(strval($value))==True)
    {
      $statement->bindParam(":$key",$value,PDO::PARAM_INT);
    }
    else
    {
      $statement->bindParam(":$key",$value,PDO::PARAM_STR);
    }
  }
  if ($statement->execute()===False)
  {
    die("SQL EXECUTE ERROR\n\n$sql");
  }
}

#####################################################################################################

function db__fetch_prepare($filename,$params)
{
  global $pdo;
  $sql=file_get_contents(SQL_PATH."/$filename.sql");
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    die("SQL PREPARE ERROR\n\n$sql");
  }
  foreach ($params as $key => $value)
  {
    if (ctype_digit(strval($value))==True)
    {
      $statement->bindParam(":$key",$value,PDO::PARAM_INT);
    }
    else
    {
      $statement->bindParam(":$key",$value,PDO::PARAM_STR);
    }
  }
  if ($statement->execute()===False)
  {
    die("SQL EXECUTE ERROR\n\n$sql");
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

?>
