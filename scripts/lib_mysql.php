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

function sql_insert($items,$table,$schema=BOT_SCHEMA)
{
  $fieldnames=array_keys($items);
  $placeholders=array_map("callback_prepare",$fieldnames);
  $fieldnames=array_map("callback_quote",$fieldnames);
  execute_prepare("INSERT INTO `$schema`.`$table` (".implode(",",$fieldnames).") VALUES (".implode(",",$placeholders).")",$items);
}

#####################################################################################################

function sql_delete($items,$table,$schema=BOT_SCHEMA)
{
  $fieldnames=array_keys($items);
  $placeholders=array_map("callback_prepare",$fieldnames);
  $fieldnames=array_map("callback_quote",$fieldnames);
  execute_prepare("DELETE FROM `$schema`.`$table` WHERE (".build_prepared_where($items).")",$items);
}

#####################################################################################################

function sql_update($value_items,$where_items,$table,$schema=BOT_SCHEMA)
{
  $value_fieldnames=array_keys($value_items);
  $value_placeholders=array_map("callback_prepare",$value_fieldnames);
  $value_fieldnames=array_map("callback_quote",$value_fieldnames);
  $values_array=array();
  for ($i=0;$i<count($value_items);$i++)
  {
    $result[]=$value_fieldnames[$i]."=".$value_placeholders[$i];
  }
  $values_string=implode(",",$values_array);
  $items=array_merge($value_items,$where_items);
  execute_prepare("UPDATE `$schema`.`$table` SET $values_string WHERE (".build_prepared_where($where_items).")",$items);
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

function build_prepared_where($items)
{
  $fieldnames=array_keys($items);
  $placeholders=array_map("callback_prepare",$fieldnames);
  $fieldnames=array_map("callback_quote",$fieldnames);
  $result=array();
  for ($i=0;$i<count($items);$i++)
  {
    $result[]="(".$fieldnames[$i]."=".$placeholders[$i].")";
  }
  return implode(" ",$result);
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
