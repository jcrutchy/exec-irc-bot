<?php

#####################################################################################################

/*
exec:~antispam|30|0|0|1|||||php scripts/antispam.php %%trailing%%
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");
define("PREVIOUS_MSG_TRACK",7);
if (get_bucket("<<IRC_CONNECTION_ESTABLISHED>>")<>"1")
{
  return;
}
$items=unserialize($argv[1]);
$nick=$items["nick"];
if ($nick==NICK_EXEC)
{
  return;
}
$trailing=$items["trailing"];
$dest=strtolower($items["destination"]);
$exec=users_get_data(NICK_EXEC);
if (isset($exec["channels"][$dest])==True)
{
  if (strpos($exec["channels"][$dest],"@")!==False)
  {
    return;
  }
}
$timestamp=$items["time"];
$index="ANTISPAM_DATA_".$dest."_".$nick;
$bucket=get_array_bucket($index);
$data=array();
$data["timestamp"]=$timestamp;
$data["trailing"]=$trailing;
$bucket[]=$data;
$n=count($bucket);
if ($n>PREVIOUS_MSG_TRACK)
{
  array_shift($bucket);
}
set_array_bucket($bucket,$index);
if ($n<PREVIOUS_MSG_TRACK)
{
  return;
}
$delta=$timestamp-$bucket[0]["timestamp"];
if ($delta<5)
{
  rawmsg("KICK $dest $target :suspected flood");
}

#####################################################################################################

?>
