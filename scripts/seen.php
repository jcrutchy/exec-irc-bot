<?php

#####################################################################################################

/*
exec:~seen|10|0|0|1|||||php scripts/seen.php %%items%% %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
require_once("lib_mysql.php");

$items=unserialize(base64_decode($argv[1]));
$trailing=trim($argv[2]);

$params=array("nick"=>$trailing,"dest"=>$items["destination"],"serv"=>$items["server"]);

$sql="SELECT * FROM ".BOT_SCHEMA.".".LOG_TABLE." WHERE ((`cmd`=\"PRIVMSG\") AND (`nick`=:nick) AND (`destination`=:dest) AND (`server`=:serv)) ORDER BY id DESC LIMIT 1";

$records=fetch_prepare($sql,$params);

if (count($records)==0)
{
  privmsg(chr(3).$items["nick"].", $trailing not seen in ".$items["destination"]);
}
else
{
  privmsg(chr(3).$items["nick"].", $trailing was last seen in ".$items["destination"]." @ ".date("H:i:s, Y-m-d",$records[0]["microtime"])." with message: ".$records[0]["trailing"]);
}

#####################################################################################################

?>
