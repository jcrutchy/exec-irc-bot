<?php

# gpl2
# by crutchy
# 20-april-2014

# db_players.php

#####################################################################################################

function db_players__insert($nick,$pwd,$email)
{
  global $pdo;
  $sql="INSERT INTO ".DB_SCHEMA.".players (nick,password,email) VALUES (:db_nick,:db_pwd,:db_email)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    irciv__err("db_players__insert: pdo prepare returned false: $sql");
  }
  $statement->bindParam(":db_nick",$nick,PDO::PARAM_STR);
  $statement->bindParam(":db_pwd",$pwd,PDO::PARAM_STR);
  $statement->bindParam(":db_email",$email,PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    irciv__err("db_players__insert: pdo execute returned false: $sql");
  }
}

#####################################################################################################

function db_players__update($nick,$pwd,$email)
{
  global $pdo;
  $sql="UPDATE ".DB_SCHEMA.".players SET password=:db_pwd,email=:db_email WHERE (nick=:db_nick)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    irciv__err("db_players__update: pdo prepare returned false: $sql");
  }
  $statement->bindParam(":db_pwd",$pwd,PDO::PARAM_STR);
  $statement->bindParam(":db_email",$email,PDO::PARAM_STR);
  $statement->bindParam(":db_nick",$nick,PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    irciv__err("db_players__update: pdo execute returned false: $sql");
  }
}

#####################################################################################################

function db_players__delete($nick)
{
  db__delete("players","nick",$nick);
}

#####################################################################################################

?>
