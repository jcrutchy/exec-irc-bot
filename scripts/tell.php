<?php

#####################################################################################################

/*
exec:~tell|10|0|0|1|*||||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~tell-internal|10|0|0|1|*|INTERNAL|||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
init:~tell-internal register-events
*/

#####################################################################################################

ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
date_default_timezone_set("UTC");

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=strtolower($argv[3]);
$alias=$argv[4];
$server=$argv[5];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~tell-internal %%trailing%%");
  return;
}
if ($alias=="~tell")
{
  if ($trailing=="")
  {
    privmsg("syntax: ~tell <nick> <message>");
    return;
  }
  $parts=explode(" ",$trailing);
  $target=strtolower($parts[0]);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  $tell_ignore_list=get_array_bucket("TELL_IGNORE_".$server."_".$nick);
  if ($target==">ignore")
  {
    $tell_ignore_list[]=strtolower($target);
    set_array_bucket($tell_ignore_list,"TELL_IGNORE_".$server."_".$nick);
    return;
  }
  if ($target=="<ignore")
  {
    set_array_bucket($tell_ignore_list,"TELL_IGNORE_".$server."_".$nick);
    return;
  }
  if (in_array(strtolower($target),$tell_ignore_list)==True)
  {
    return;
  }
  append_array_bucket("TELL_MESSAGES_".$server."_".$target,$target.", at ".date("Y-m-d H:i:s",microtime(True))." (UTC), ".$nick." left message from ".$dest.": ".$trailing);
  privmsg("message saved");
  return;
}
if (substr($trailing,0,5)=="~tell")
{
  return;
}
$messages=get_array_bucket("TELL_MESSAGES_".$server."_".$nick);
for ($i=0;$i<count($messages);$i++)
{
  notice($nick,$messages[$i]);
}
unset_bucket("TELL_MESSAGES_".$server."_".$nick);

#####################################################################################################

?>
