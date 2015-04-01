<?php

#####################################################################################################

/*
exec:~activity|60|0|0|1|||||php scripts/activity.php %%nick%% %%trailing%% %%dest%% %%start%% %%alias%% %%cmd%%
init:~activity register-events
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

date_default_timezone_set("UTC");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

$channel_data=get_array_bucket("channel_data");

if ($trailing=="")
{
  return;
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "register-events":
    register_all_events("~activity",True);
    return;
  case "event-join":
    # trailing = <nick> <channel>
    break;
  case "event-kick":
    # trailing = <channel> <nick>
    break;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    break;
  case "event-part":
    # trailing = <nick> <channel>
    break;
  case "event-quit":
    # trailing = <nick>
    break;
  case "event-privmsg":
    # trailing = <nick> <channel> <trailing>
    handle_privmsg($parts,$channel_data);
    break;
}

set_array_bucket($channel_data,"channel_data");

#####################################################################################################

function handle_privmsg($parts,&$channel_data)
{
  if (count($parts)<3)
  {
    return;
  }
  # trailing = <nick> <channel> <trailing>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  array_shift($parts);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  term_echo("*** activity: nick=$nick, channel=$channel, trailing=$trailing");

  if (($nick=="") and ($channel=="#freenode") and (strpos(strtolower($trailing),"rodney")!==False) and (strpos(strtolower($trailing),"nethack")!==False))
  {
    pm("#nethack",$trailing);
    if (strpos(strtolower($trailing),"ncommander")!==False)
    {
      pm("#Soylent",$trailing);
    }
  }

}

#####################################################################################################

?>
