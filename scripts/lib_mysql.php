<?php

#####################################################################################################

define("BOT_SCHEMA","exec_irc_bot");
define("LOG_TABLE","irc_log");

$pdo=new PDO("mysql:host=localhost","www",trim(file_get_contents("../pwd/mysql_www")));
if ($pdo===False)
{
  term_echo("ERROR CONNECTING TO DATABASE\n");
}

#####################################################################################################

function get_last_error()
{
  global $pdo;
  $err=$pdo->errorInfo();
  if ($err[0]<>Null)
  {
    return $err[2];
  }
  else
  {
    return "";
  }
}

#####################################################################################################

function sql_insert($items,$table)
{
  $fieldnames=array_keys($items);
  $placeholders=array_map("callback_prepare",$fieldnames);
  $fieldnames=array_map("callback_quote",$fieldnames);
  execute_prepare("INSERT INTO ".BOT_SCHEMA.".$table (".implode(",",$fieldnames).") VALUES (".implode(",",$placeholders).")",$items);
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
    term_echo("SQL QUERY ERROR\n\n$sql\n");
    return False;
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
    term_echo("SQL PREPARE ERROR\n\n$sql\n");
    return;
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
    term_echo("SQL EXECUTE ERROR\n\n$sql\n");
  }
}

#####################################################################################################

function fetch_prepare($sql,$params)
{
  global $pdo;
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    term_echo("SQL PREPARE ERROR\n\n$sql\n");
    return False;
  }
  foreach ($params as $key => $value)
  {
    if (ctype_digit(strval($value))==True)
    {
      $err=$statement->bindParam(":$key",$params[$key],PDO::PARAM_INT);
    }
    else
    {
      $err=$statement->bindParam(":$key",$params[$key],PDO::PARAM_STR);
    }
    if ($err==False)
    {
      $err=$statement->errorInfo();
      if ($err[0]<>Null)
      {
        echo $err[2]."\n";
      }
      term_echo("SQL BINDVALUE ERROR\n\n$sql\n");
      return False;
    }
  }
  if ($statement->execute()===False)
  {
    $err=$statement->errorInfo();
    if ($err[0]<>Null)
    {
      echo $err[2]."\n";
    }
    term_echo("SQL EXECUTE ERROR\n\n$sql\n");
    return False;
  }
  return $statement->fetchAll(PDO::FETCH_ASSOC);
}

#####################################################################################################

?>
