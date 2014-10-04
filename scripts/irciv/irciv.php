<?php

# gpl2
# by crutchy

#####################################################################################################

date_default_timezone_set("UTC");

require_once("irciv_lib.php");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

if (($cmd=="INTERNAL") and ($nick==NICK_EXEC) and ($trailing=="startup"))
{
  civ_startup();
}

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);

$user=get_array_bucket("irciv_user_$nick");
$last_game_chan="";
if (substr($dest,0,1)=="#")
{
  $last_game_chan=$dest;
}
elseif (isset($user["last_game_chan"])==True)
{
  $last_game_chan=$user["last_game_chan"];
}

switch ($action)
{
  case "event-join":
    # trailing = <nick> <channel>
    irciv_term_echo("join: $trailing");
    return;
  case "event-kick":
    # trailing = <channel> <nick>
    irciv_term_echo("kick: $trailing");
    return;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    irciv_term_echo("nick: $trailing");
    return;
  case "event-part":
    # trailing = <nick> <channel>
    irciv_term_echo("part: $trailing");
    return;
  case "event-quit":
    # trailing = <nick>
    irciv_term_echo("quit: $trailing");
    return;
}

#####################################################################################################

function civ_startup()
{
  register_event_handler("JOIN",":".NICK_EXEC." INTERNAL :~civ event-join %%nick%% %%params%%");
  register_event_handler("KICK",":".NICK_EXEC." INTERNAL :~civ event-kick %%params%%");
  register_event_handler("NICK",":".NICK_EXEC." INTERNAL :~civ event-nick %%nick%% %%trailing%%");
  register_event_handler("PART",":".NICK_EXEC." INTERNAL :~civ event-part %%nick%% %%params%%");
  register_event_handler("QUIT",":".NICK_EXEC." INTERNAL :~civ event-quit %%nick%%");
}

#####################################################################################################

?>
