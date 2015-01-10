<?php

#####################################################################################################

/*
exec:~antispam|30|0|0|1|||||php scripts/antispam.php %%trailing%%
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

define("QUIET_TIME_SEC",10);
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
$host=$items["hostname"];
$timestamp=$items["time"];

$index="ANTISPAM_DATA_".$dest."_".$host;

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

if (($delta<5) and (get_bucket($index."_QUIET")==""))
{
  set_bucket($index."_QUIET","1");
  privmsg("*** quieting potential flooding nick \"$nick\" for ".QUIET_TIME_SEC." seconds");
  pm("ChanServ","QUIET $dest $nick!*@*");
  sleep(QUIET_TIME_SEC);
  pm("ChanServ","UNQUIET $dest $nick!*@*");
  unset_bucket($index."_QUIET");
}

#####################################################################################################

?>
