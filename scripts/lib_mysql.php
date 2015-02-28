<?php

#####################################################################################################

$pdo=new PDO("mysql:host=localhost","www",trim(file_get_contents("../pwd/mysql_www")));
if ($pdo===False)
{
  die("ERROR CONNECTING TO DATABASE\n");
}

#####################################################################################################

function callback_quote($field)
{
  return "`$field`";
}

#####################################################################################################

function callback_prepare($field)
{
  return ":$field";
}

#####################################################################################################

function fetch_query($sql)
{
  global $pdo;
  $statement=$pdo->query($sql);
  if ($statement===False)
  {
    $err=$pdo->errorInfo();
    if ($err[0]<>Null)
    {
      echo $err[2]."\n";
    }
    die("SQL QUERY ERROR\n\n$sql\n");
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

function execute_prepare($sql,$params)
{
  global $pdo;
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    die("SQL PREPARE ERROR\n\n$sql\n");
  }
  foreach ($params as $key => $value)
  {
    if (ctype_digit(strval($value))==True)
    {
      $statement->bindParam(":$key",$params[$key],PDO::PARAM_INT);
    }
    else
    {
      $statement->bindParam(":$key",$params[$key],PDO::PARAM_STR);
    }
  }
  if ($statement->execute()===False)
  {
    $err=$statement->errorInfo();
    if ($err[0]<>Null)
    {
      echo $err[2]."\n";
    }
    die("SQL EXECUTE ERROR\n\n$sql\n");
  }
}

#####################################################################################################

function fetch_prepare($sql,$params)
{
  global $pdo;
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    die("SQL PREPARE ERROR\n\n$sql\n");
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
    $err=$statement->errorInfo();
    if ($err[0]<>Null)
    {
      echo $err[2]."\n";
    }
    die("SQL EXECUTE ERROR\n\n$sql\n");
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

?>
