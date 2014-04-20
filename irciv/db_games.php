<?php

# gpl2
# by crutchy
# 20-april-2014

# db_games.php

#####################################################################################################

function db_games__insert($name)
{
  global $pdo;
  $sql="INSERT INTO ".DB_SCHEMA.".games (name) VALUES (:db_name)";
  $statement=$pdo->prepare($sql);
  if ($statement===False)
  {
    irciv__err("db_games__insert: pdo prepare returned false: $sql");
  }
  $statement->bindParam(":db_name",$name,PDO::PARAM_STR);
  if ($statement->execute()===False)
  {
    irciv__err("db_games__insert: pdo execute returned false: $sql");
  }
}

#####################################################################################################

function db_games__delete($name)
{
  db__delete("games","game_id","name",$name);
}

#####################################################################################################

?>
