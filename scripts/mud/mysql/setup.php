<?php

define("DB_HOST","localhost");
define("DB_USER","root");
define("DB_PASSWORD",trim(file_get_contents("../../../../pwd/mysql_root")));

$pdo=new PDO("mysql:host=".DB_HOST,DB_USER,DB_PASSWORD);
if ($pdo===False)
{
  die("ERROR CONNECTING TO MYSQL SERVER\n");
}
else
{
  echo "CONNECTED\n";
}
$sql=file_get_contents("schema.sql");
$result=$pdo->exec($sql);
if ($result===False)
{
  die("ERROR CREATING DATABASE\n");
}
else
{
  echo "DATABASE CREATED\n";
}
$result=$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON exec_mud.* TO www@'%';");
if ($result===False)
{
  die("ERROR GRANTING PRIVILEGES\n");
}
else
{
  die("PRIVILEGES GRANTED\n");
}

?>
