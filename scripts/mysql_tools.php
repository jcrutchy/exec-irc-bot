<?php

#####################################################################################################

/*
exec:~mysql|30|0|0|1|crutchy,chromas||#,#Soylent,#test,#journals||php scripts/mysql_tools.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~sql|30|0|0|1|crutchy,chromas||#,#Soylent,#test,#journals||php scripts/mysql_tools.php %%trailing%% %%dest%% %%nick%% %%alias%%
help:~mysql|syntax: ~mysql query <sql>
*/

#####################################################################################################

require_once("lib.php");
require_once("lib_mysql.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
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
  case "query":
    # ~sql query select comment_body from exec_irc_bot.sn_comments where (comment_body like '%fart%') order by rand() limit 1
    $records=fetch_query($trailing);
    $error=get_last_error();
    if ($error<>"")
    {
      privmsg($error);
      return;
    }
    for ($i=0;$i<min(3,count($records));$i++)
    {
      if (is_array($records[$i])==True)
      {
        privmsg(implode("|",$records[$i]));
      }
      else
      {
        privmsg($records[$i]);
      }
    }
    break;
}

#####################################################################################################

?>
