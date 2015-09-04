<?php

#####################################################################################################

/*
exec:.karma|10|0|0|1|*||||php scripts/karma.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:.karma-internal|10|0|0|1|*|INTERNAL||<<EXEC_KARMA>>|php scripts/karma.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
init:.karma-internal register-events
*/

#####################################################################################################

return;

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$server=$argv[5];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :.karma-internal %%trailing%%");
  return;
}

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_KARMA_CHANNELS>>",".karma",".karma-internal",$msg);
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
  case 9:
    do_karma($trailing,$alias,$server,$dest);
    return;
}

#####################################################################################################

function do_karma($trailing,$alias,$server,$dest)
{
  $data=get_array_bucket("<<EXEC_KARMA>>");
  if ($alias<>".karma")
  {
    $operator=substr($trailing,strlen($trailing)-2);
    $trailing=substr($trailing,0,strlen($trailing)-2);
    if (isset($data[$server][$dest][$trailing])==False)
    {
      $data[$server][$dest][$trailing]=0;
    }
    switch ($operator)
    {
      case "++":
        $data[$server][$dest][$trailing]=$data[$server][$dest][$trailing]+1;
        break;
      case "--":
        $data[$server][$dest][$trailing]=$data[$server][$dest][$trailing]-1;
        break;
      default:
        return;
    }
    set_array_bucket($data,"<<EXEC_KARMA>>");
  }
  if (isset($data[$server][$dest][$trailing])==False)
  {
    privmsg(chr(3)."$trailing doesn't have karma yet");
  }
  else
  {
    privmsg(chr(2)."karma".chr(2)." - $trailing: ".$data[$server][$dest][$trailing]);
  }
}

#####################################################################################################

?>
