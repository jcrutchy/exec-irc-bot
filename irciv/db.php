<?php

# gpl2
# by crutchy
# 20-april-2014

# db.php

#####################################################################################################

function db__connect()
{
  $user="irciv";
  $host="localhost";
  $password="irciv";
  $pdo=new PDO("mysql:host=".$host,$user,$password);
  if ($pdo===False)
  {
    irciv__err("db__connect: unable to connect to database");
  }
  $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,False);
  define("DB_SCHEMA","IRCiv");
  return $pdo;
}

#####################################################################################################

function db__get($table,$get_field,$unique_field,$unique_value)
{
  global $pdo;
  $sql="SELECT $get_field FROM ".DB_SCHEMA.".$table WHERE ($unique_field=:db_$unique_value)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    irciv__err("db__get: pdo prepare returned false: $sql");
  }
  $statement->bindParam(":db_$unique_field",$unique_value,PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    irciv__err("db__get: pdo execute returned false: $sql");
  }
  $result=$statement->fetch(PDO::FETCH_ASSOC);
  if ($result===False)
  {
    irciv__err("db__get: pdo fetch returned false: $sql");
  }
  return $result[$get_field];
}

#####################################################################################################

function db__delete($table,$unique_field,$unique_value)
{
  global $pdo;
  $sql="DELETE FROM ".DB_SCHEMA.".$table WHERE ($unique_field=:db_$unique_field)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    irciv__err("db__delete: pdo prepare returned false: $sql");
  }
  $statement->bindParam(":db_$unique_field",$unique_value,PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    irciv__err("db__delete: pdo execute returned false: $sql");
  }
}

#####################################################################################################

?>
