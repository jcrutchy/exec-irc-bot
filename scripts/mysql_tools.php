<?php

#####################################################################################################

/*
exec:~mysql|30|0|0|1|crutchy,chromas||#,#Soylent||php scripts/mysql_tools.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("lib_mysql.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

switch ($trailing)
{
  case "count":
    $records=fetch_query("SELECT COUNT(*) FROM ".BOT_SCHEMA.".".LOG_TABLE);
    privmsg($records[0]["COUNT(*)"]);
    break;
  case "last":
    $params=array("destination"=>$dest);
    $records=fetch_prepare("SELECT * FROM ".BOT_SCHEMA.".".LOG_TABLE." WHERE ((cmd='PRIVMSG') and (destination=:destination)) ORDER BY id DESC LIMIT 1",$params);
    privmsg($records[0]["data"]);
    break;
}

#####################################################################################################

?>
