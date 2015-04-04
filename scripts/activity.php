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
  nethack_follow($nick,$channel,$trailing);
  minion_talk($nick,$channel,$trailing);
}

#####################################################################################################

function nethack_follow($nick,$channel,$trailing)
{
  $action="ACTION";
  $rodney="03Rodney [02#NetHack] 05";
  $follow="NCommander";
  if (($nick=="") and ($channel=="#freenode") and (substr($trailing,0,strlen($rodney))==$rodney))
  {
    $msg=substr($trailing,strlen($rodney));
    if (substr($msg,0,strlen($action))==$action)
    {
      $msg=substr($msg,strlen($action));
      $msg=substr($msg,0,strlen($msg)-1);
      $msg="--".$msg;
    }
    $out="[02#NetHack] 05".$msg;
    pm("#nethack",$out);
    if (substr($msg,0,strlen($follow))==$follow)
    {
      pm("#Soylent",$out);
    }
  }
}

#####################################################################################################

function minion_talk($nick,$channel,$trailing)
{
  if ($nick<>"")
  {
    $account=users_get_account($nick);
    $allowed=array("crutchy","chromas","mrcoolbp","NCommander","juggs");
    if (in_array($account,$allowed)==False)
    {
      return;
    }
    $commands=array();
    # #epoch > g'day subsentient
    # ~minion raw sylnt :sylnt PRIVMSG #epoch :g'day subsentient
    $params=explode(">",$trailing);
    if (count($params)<2)
    {
      return;
    }
    $target=trim($params[0]);
    if (substr($target,0,1)<>"#")
    {
      return;
    }
    array_shift($params);
    $msg=trim(implode(">",$params));
    if (strlen($msg)>0)
    {
      $commands[]="~minion raw sylnt :sylnt PRIVMSG $target :<$nick> $msg";
    }
    if (count($commands)==1)
    {
      internal_macro($commands);
    }
  }
}

#####################################################################################################

?>
