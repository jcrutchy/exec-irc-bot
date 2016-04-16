<?php

#####################################################################################################

/*
exec:~exec-irc-raw|5|0|0|1|@||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~op|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~deop|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~voice|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~devoice|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~invite|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~kick|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~topic|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~mode|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~lockdown|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~alias-info|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~event-info|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=trim($argv[1]);
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$target=$nick;
if ($trailing<>"")
{
  $target=$trailing;
}

switch ($alias)
{
  case "~exec-irc-raw":
    rawmsg($trailing);
    break;
  case "~op":
    rawmsg("MODE $dest +o $target");
    break;
  case "~deop":
    if ($target<>get_bot_nick())
    {
      rawmsg("MODE $dest -o $target");
    }
    break;
  case "~voice":
    rawmsg("MODE $dest +v $target");
    break;
  case "~devoice":
    if ($target<>get_bot_nick())
    {
      rawmsg("MODE $dest -v $target");
    }
    break;
  case "~invite":
    if ($trailing<>"")
    {
      rawmsg("INVITE $trailing :$dest");
    }
    break;
  case "~kick":
    if (($target<>$nick) and ($target<>get_bot_nick()))
    {
      rawmsg("KICK $dest $target :commanded by $nick");
    }
    break;
  case "~topic":
    if ($trailing<>"")
    {
      rawmsg("TOPIC $dest :$trailing");
    }
    break;
  case "~mode":
    if ($trailing<>"")
    {
      rawmsg("MODE $dest $trailing");
    }
    break;
  case "~lockdown":
    rawmsg("MODE $dest +ntipm");
    break;
  case "~alias-info":
    if ($trailing==="")
    {
      privmsg(chr(3)."02"."syntax: ~alias-info <alias>");
      return;
    }
    $exec_list_bucket=get_bucket("<<EXEC_LIST>>");
    if ($exec_list_bucket=="")
    {
      privmsg(chr(3)."02"."  *** error getting exec list bucket");
      return;
    }
    $exec_list_bucket=base64_decode($exec_list_bucket);
    if ($exec_list_bucket===False)
    {
      privmsg(chr(3)."02"."  *** error decoding exec list bucket");
      return;
    }
    $exec_list=unserialize($exec_list_bucket);
    if ($exec_list===False)
    {
      privmsg(chr(3)."02"."  *** error unserializing exec list bucket");
      return;
    }
    if (isset($exec_list[$trailing])===False)
    {
      privmsg(chr(3)."02"."  *** error: alias not found");
      return;
    }
    $enabled_str="enabled";
    if ($exec_list[$trailing]["enabled"]==False)
    {
      $enabled_str="disabled";
    }
    $record=$exec_list[$trailing];
    privmsg(chr(3)."02"."exec [$enabled_str]: ".$record["alias"]."|".$record["timeout"]."|".$record["repeat"]."|".$record["auto"]."|".$record["empty"]."|".implode(",",$record["accounts"])."|".$record["accounts_wildcard"]."|".implode(",",$record["cmds"])."|".implode(",",$record["dests"])."|".implode(",",$record["bucket_locks"])."|".$record["cmd"]);
    if (file_exists($exec_list[$trailing]["file"])==True)
    {
      $stat=stat($exec_list[$trailing]["file"]);
      privmsg(chr(3)."02"."file: ".$exec_list[$trailing]["file"]." [modified: ".date("Y-m-d H:i:s",$stat["mtime"]).", size: ".$stat["size"]." bytes]");
    }
    else
    {
      privmsg(chr(3)."02"."file: ".$exec_list[$trailing]["file"]." [FILE NOT FOUND]");
    }
    return;
  case "~event-info":
    if ($trailing==="")
    {
      privmsg(chr(3)."02"."syntax: ~event-info <cmd>");
      return;
    }
    $trailing=strtoupper($trailing);
    $events_bucket=get_bucket("<<EXEC_EVENT_HANDLERS>>");
    if ($events_bucket=="")
    {
      privmsg(chr(3)."02"."  *** error getting event handlers bucket");
      return;
    }
    $events_bucket=base64_decode($events_bucket);
    if ($events_bucket===False)
    {
      privmsg(chr(3)."02"."  *** error decoding event handlers bucket");
      return;
    }
    $events=unserialize($events_bucket);
    if ($events===False)
    {
      privmsg(chr(3)."02"."  *** error unserializing event handlers bucket");
      return;
    }
    $results=array();
    for ($i=0;$i<count($events);$i++)
    {
      $record=unserialize($events[$i]);
      if (isset($record[$trailing])==True)
      {
        $results[]=$record[$trailing];
      }
    }
    if (count($results)==0)
    {
      privmsg("no events found for cmd \"$trailing\"");
    }
    else
    {
      for ($i=0;$i<count($results);$i++)
      {
        privmsg($results[$i]);
      }
    }
    return;
}

#####################################################################################################

?>
