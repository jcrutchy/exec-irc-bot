<?php

#####################################################################################################

/*
exec:~antispam-internal|10|0|0|1||INTERNAL|||php scripts/antispam.php %%trailing%%
init:~antispam-internal register-events
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

$trailing=trim($argv[1]);

if ($trailing=="register-events")
{
  register_all_events("~antispam-internal",True);
  return;
}

#var_dump($trailing);
return;

# event-privmsg crutchy #crutchy fart
# event-part crutchy #crutchy
# event-join crutchy #crutchy
# event-nick fromnick tonick

/*
  register_event_handler("JOIN",":".get_bot_nick()." INTERNAL :$alias event-join %%nick%% %%params%%");
  register_event_handler("KICK",":".get_bot_nick()." INTERNAL :$alias event-kick %%params%%");
  register_event_handler("NICK",":".get_bot_nick()." INTERNAL :$alias event-nick %%nick%% %%trailing%%");
  register_event_handler("PART",":".get_bot_nick()." INTERNAL :$alias event-part %%nick%% %%params%%");
  register_event_handler("QUIT",":".get_bot_nick()." INTERNAL :$alias event-quit %%nick%%");
  register_event_handler("PRIVMSG",":".get_bot_nick()." INTERNAL :$alias event-privmsg %%nick%% %%dest%% %%trailing%%");
*/

$parts=explode(" ",$trailing);

if (count($parts)<3)
{
  return;
}

$nick=strtolower($parts[0]);
$channel=strtolower($parts[1]);
array_shift($parts);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

return;

define("PREVIOUS_MSG_TRACK",5);
if (get_bucket("<<IRC_CONNECTION_ESTABLISHED>>")<>"1")
{
  return;
}
$items=unserialize($argv[1]);
$nick=$items["nick"];
if (users_get_account($nick)==get_bot_nick())
{
  return;
}
$trailing=$items["trailing"];
$dest=strtolower($items["destination"]);
$exec=users_get_data(get_bot_nick());
if (isset($exec["channels"][$dest])==True)
{
  if (strpos($exec["channels"][$dest],"@")===False)
  {
    return;
  }
}
else
{
  return;
}
$user=users_get_data($nick);
if (isset($user["channels"][$dest])==False)
{
  return;
}

var_dump($items);
return; # TODO

$timestamp=$items["time"];
$index="ANTISPAM_DATA_".$dest."_".$nick;
$bucket=get_array_bucket($index);
if (isset($bucket["timestamps"])==False)
{
  $bucket["timestamps"]=array();
  $bucket["trailings"]=array();
}
$bucket["timestamps"][]=$timestamp;
$bucket["trailings"][]=$trailing;
$n=count($bucket["timestamps"]);
if ($n>PREVIOUS_MSG_TRACK)
{
  array_shift($bucket);
}
set_array_bucket($bucket,$index);
if ($n<PREVIOUS_MSG_TRACK)
{
  return;
}
$delta=$timestamp-$bucket["timestamps"][0];
$trailings=array_count_values($bucket["trailings"]);
if (($delta<5) and (count($trailings)==1))
{
  rawmsg("KICK $dest $nick :suspected flood");
}

#####################################################################################################

?>
