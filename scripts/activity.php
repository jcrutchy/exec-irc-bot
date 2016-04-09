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
    handle_privmsg($parts);
    break;
}

#####################################################################################################

function handle_privmsg($parts)
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
  nick_follow($nick,$channel,$trailing);
  minion_talk($nick,$channel,$trailing);
  minion_relay($nick,$channel,$trailing);
}

#####################################################################################################

function nick_follow($nick,$channel,$trailing)
{
  $landing_channel="#freenode";
  $freenode_follows=array(
    "Rodney"=>array("from"=>"#NetHack","to"=>"#nethack"),
    "Gretell"=>array("from"=>"##crawl","to"=>"#crawl"),
    "Henzell"=>array("from"=>"##crawl","to"=>"#crawl"),
    "Sizzell"=>array("from"=>"##crawl","to"=>"#crawl"),
    "Cheibriados"=>array("from"=>"##crawl","to"=>"#crawl"),
    "##vibratingbuttplugsandhorsedildos"=>array("from"=>"##vibratingbuttplugsandhorsedildos","to"=>"##vibratingbuttplugsandhorsedildos"));
  $highlight_follows=array("NCommander"=>array("from"=>"#NetHack","to"=>"#Soylent"));
  foreach ($freenode_follows as $freenode_nick => $follow_channels)
  {
    $landing=chr(3)." [".chr(3)."02".$follow_channels["from"].chr(3)."] ".chr(3)."05";
    if ($freenode_nick<>$follow_channels["from"])
    {
      $landing=chr(3)."03".$freenode_nick.$landing;
      if (substr($trailing,0,strlen($landing))<>$landing)
      {
        continue;
      }
      $msg=substr($trailing,strlen($landing));
      $out="[".chr(3)."02".$follow_channels["from"].chr(3)."] ".chr(3)."07".$msg;
    }
    else
    {
      $i=strpos($trailing,$landing);
      if ($i===False)
      {
        continue;
      }
      $msg=substr($trailing,$i+strlen($landing));
      $out="[".substr($trailing,0,$i)."] ".$msg;
    }
    if (($nick=="") and ($channel==$landing_channel))
    {
      pm($follow_channels["to"],$out);
      foreach ($highlight_follows as $keyword => $keyword_follow_channels)
      {
        if ((strpos(strtolower($msg),strtolower($keyword))!==False) and ($keyword_follow_channels["from"]==$follow_channels["from"]))
        {
          pm($keyword_follow_channels["to"],$out);
        }
      }
    }
  }
}

#####################################################################################################

function minion_talk($nick,$channel,$trailing)
{
  $relays_bucket="activity.php/minion_talk/relays";
  $relays=get_array_bucket($relays_bucket);
  # flush all outdated relays
  $save_bucket=False;
  foreach ($relays as $freenode_nick => $freenode_channels)
  {
    foreach ($relays[$freenode_nick] as $freenode_channel => $data)
    {
      if ((microtime(True)-$data["timestamp"])>(10*60))
      {
        unset($relays[$freenode_nick][$freenode_channel]);
        $save_bucket=True;
      }
    }
  }
  if ($nick<>"")
  {
    $account=users_get_account($nick);
    $allowed=array("crutchy","chromas","mrcoolbp","NCommander","juggs","TheMightyBuzzard","cmn32480");
    if (in_array($account,$allowed)==True)
    {
      if ($trailing==".relays")
      {
        $n=0;
        foreach ($relays as $freenode_nick => $freenode_channels)
        {
          foreach ($relays[$freenode_nick] as $freenode_channel => $data)
          {
            $rem=round(($data["timestamp"]+10*60-microtime(True))/60,0);
            pm($channel,chr(3)."13  $freenode_nick: $freenode_channel => ".$data["channel"]." (unset in $rem minutes)");
            $n++;
          }
        }
        if ($n==0)
        {
          pm($channel,chr(3)."13  no channel relays currently active");
        }
        return;
      }
      $params=explode(">",$trailing);
      if (count($params)>=2)
      {
        $freenode_channel=strtolower(trim($params[0]));
        if (substr($freenode_channel,0,1)=="#")
        {
          array_shift($params);
          $msg=trim(implode(">",$params));
          if (strlen($msg)>0)
          {
            $commands=array("~minion raw sylnt :sylnt PRIVMSG $freenode_channel :<$nick> $msg");
            internal_macro($commands);
            $parts=explode(",",$msg);
            $freenode_nick=strtolower(trim($parts[0]));
            if ((count($parts)>1) and (strpos($freenode_nick," ")===False))
            {
              $relays[$freenode_nick][$freenode_channel]=array("channel"=>$channel,"timestamp"=>microtime(True));
              pm($channel,chr(3)."13  ten minute relay set for \"$freenode_nick\" in \"$freenode_channel\" on freenode to \"$channel\" on this server");
              $save_bucket=True;
            }
          }
        }
      }
    }
  }
  if ($channel=="#freenode")
  {
    $freenode_nick=extract_text($trailing,chr(3)."03",chr(3)." [",False);
    $freenode_channel=extract_text($trailing,chr(3)." [".chr(3)."02",chr(3)."] ".chr(3)."05",False);
    if (isset($relays[strtolower($freenode_nick)][$freenode_channel])==True)
    {
      $freenode_trailing=extract_text($trailing,chr(3)."] ".chr(3)."05",chr(3),True);
      pm($relays[strtolower($freenode_nick)][$freenode_channel]["channel"],chr(3)."03".$freenode_nick.chr(3)." [".chr(3)."02".$freenode_channel.chr(3)."] ".chr(3)."05".$freenode_trailing);
    }
  }
  if ($save_bucket==True)
  {
    set_array_bucket($relays,$relays_bucket);
  }
}

#####################################################################################################

function minion_relay($nick,$channel,$trailing)
{
  if (($channel=="##vibratingbuttplugsandhorsedildos") and ($nick!==get_bot_nick()))
  {
    echo "/INTERNAL ~minion privmsg sylnt ##vibratingbuttplugsandhorsedildos ".chr(3)."05"."[$nick] $trailing\n";
  }
}

#####################################################################################################

?>
