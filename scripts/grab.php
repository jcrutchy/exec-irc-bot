<?php

#####################################################################################################

/*

exec:add ~grab
exec:edit ~grab cmd php scripts/grab.php %%trailing%% %%dest%% %%nick%% %%cmd%% %%server%% %%alias%%
exec:enable ~grab

exec:add ~grab-internal
exec:edit ~grab-internal cmd php scripts/grab.php %%trailing%% %%dest%% %%nick%% %%cmd%% %%server%% %%alias%%
exec:enable ~grab-internal

init:~grab-internal register-events

*/

#####################################################################################################

require_once("lib.php");
require_once("switches.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$cmd=strtolower($argv[4]);
$server=$argv[5];
$alias=$argv[6];

if ($trailing=="")
{
  return;
}

if (($trailing=="register-events") and ($cmd=="internal") and ($alias=="~grab-internal"))
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~grab-internal PRIVMSG %%trailing%%");
  return;
}

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_GRAB_CHANNELS>>","~grab","~grab-internal",$msg);
switch ($flag)
{
  case 1:
    privmsg("grab enabled for ".chr(3)."10$dest");
    return;
  case 2:
    privmsg("grab already enabled for ".chr(3)."10$dest");
    return;
  case 3:
    privmsg("grab disabled for ".chr(3)."10$dest");
    return;
  case 4:
    privmsg("grab already disabled for ".chr(3)."10$dest");
    return;
  case 7:
  case 11:
    break;
  default:
    return;
}

if (($cmd<>"internal") or ($alias<>"~grab-internal"))
{
  return;
}

$parts=explode(" ",$trailing);
if (array_shift($parts)<>"PRIVMSG")
{
  return;
}

$trailing=implode(" ",$parts);

$fn=DATA_PATH."quotes_data_".base64_encode($server).".txt";

$data=array();
if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}

$save_data=False;

pm("crutchy",$trailing);

if ($save_data===True)
{
  if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
  {
    privmsg("error writing quotes data file");
  }
  return;
}

#####################################################################################################

?>
