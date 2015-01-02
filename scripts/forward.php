<?php

#####################################################################################################

/*
exec:~forward|60|0|0|1|crutchy||||php scripts/forward.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
init:~forward register-events
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "register-events":
    register_all_events("~forward",True);
    return;
  case "event-join":
    # trailing = <nick> <channel>
    forward_join();
    break;
  case "event-kick":
    # trailing = <channel> <nick>
    forward_kick();
    break;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    forward_nick();
    break;
  case "event-part":
    # trailing = <nick> <channel>
    forward_part();
    break;
  case "event-quit":
    # trailing = <nick>
    forward_quit();
    break;
  case "event-privmsg":
    # trailing = <nick> <channel> <trailing>
    forward_privmsg();
    break;
}

#####################################################################################################

function forward_join()
{
  global $parts;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <nick> <channel>
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  forward_msg(chr(2)." * $nick has joined $channel");
}

#####################################################################################################

function forward_kick()
{
  global $parts;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <channel> <nick>
  $nick=strtolower($parts[1]);
  $channel=strtolower($parts[0]);
  forward_msg(chr(2)." * $nick has been kicked from $channel");
}

#####################################################################################################

function forward_nick()
{
  global $parts;
  if (count($parts)<>2)
  {
    return;
  }
  # trailing = <old-nick> <new-nick>
  $old_nick=strtolower($parts[0]);
  $new_nick=strtolower($parts[1]);
  forward_msg(chr(2)." * $old_nick is now known as $new_nick");
}

#####################################################################################################

function forward_part()
{
  global $parts;
  if (count($parts)<>2)
  {
    return;
  }
  $nick=$parts[0];
  $channel=$parts[1];
  forward_msg(chr(2)." * $nick has left $channel");
}

#####################################################################################################

function forward_quit()
{
  global $trailing;
  forward_msg(chr(2)." * $trailing has quit");
}

#####################################################################################################

function forward_privmsg()
{
  global $parts;
  if (count($parts)<3)
  {
    return;
  }
  # trailing = <nick> <channel> <trailing>
  $nick=$parts[0];
  $channel=$parts[1];
  array_shift($parts);
  array_shift($parts);
  $trailing=implode(" ",$parts);
  forward_msg(" $channel: <$nick> $trailing");
}

#####################################################################################################

function forward_msg($msg)
{
  privmsg(chr(3)."10".$msg);
}

#####################################################################################################

?>
