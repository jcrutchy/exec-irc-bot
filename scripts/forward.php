<?php

# gpl2
# by crutchy

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

?>
