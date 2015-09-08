<?php

#####################################################################################################

/*
exec:~karma|10|0|0|1|*||||php scripts/karma.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~karma-internal|10|0|0|1|*|INTERNAL||<<EXEC_KARMA>>|php scripts/karma.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
init:~karma-internal register-events
*/

#####################################################################################################

require_once("lib.php");
require_once("switches.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$server=$argv[5];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~karma-internal PRIVMSG %%trailing%%");
  return;
}

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_KARMA_CHANNELS>>","~karma","~karma-internal",$msg);
switch ($flag)
{
  case 1:
    privmsg("karma enabled for ".chr(3)."10$dest");
    return;
  case 2:
    privmsg("karma already enabled for ".chr(3)."10$dest");
    return;
  case 3:
    privmsg("karma disabled for ".chr(3)."10$dest");
    return;
  case 4:
    privmsg("karma already disabled for ".chr(3)."10$dest");
    return;
  case 7:
  case 11:
    break;
  default:
    return;
}

if ($alias==".karma")
{
  $msg=$trailing;
}

if ($msg=="")
{
  return;
}

$data=get_array_bucket("EXEC_KARMA");
if ($alias<>"~karma")
{
  $operator=substr($msg,strlen($msg)-2);
  $msg=substr($msg,0,strlen($msg)-2);
  if (isset($data[$server][$dest][$msg])==False)
  {
    $data[$server][$dest][$msg]=0;
  }
  switch ($operator)
  {
    case "++":
      $data[$server][$dest][$msg]=$data[$server][$dest][$msg]+1;
      break;
    case "--":
      $data[$server][$dest][$msg]=$data[$server][$dest][$msg]-1;
      break;
    default:
      return;
  }
  set_array_bucket($data,"EXEC_KARMA");
}
if (isset($data[$server][$dest][$msg])==False)
{
  privmsg(chr(3)."$msg doesn't have karma yet");
}
else
{
  privmsg(chr(2)."karma".chr(2)." - $msg: ".$data[$server][$dest][$msg]);
}

#####################################################################################################

?>
