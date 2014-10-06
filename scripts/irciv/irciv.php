<?php

# gpl2
# by crutchy

#####################################################################################################

ini_set("display_errors","on");

date_default_timezone_set("UTC");

require_once("irciv_lib.php");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$irciv_data_changed=False;

$irciv_games=get_array_bucket("IRCIV_GAMES");
$irciv_accounts=get_array_bucket("IRCIV_ACCOUNTS");
$irciv_maplist=get_array_bucket("IRCIV_MAPLIST");
$irciv_mapdata=get_array_bucket("IRCIV_MAPDATA");
$irciv_players=get_array_bucket("IRCIV_PLAYERS_".$dest);

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "register-events":
    register_all_events("~civ");
    break;
  case "event-join":
    # trailing = <nick> <channel>
    irciv_term_echo("join: $trailing");
    break;
  case "event-kick":
    # trailing = <channel> <nick>
    irciv_term_echo("kick: $trailing");
    break;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    irciv_term_echo("nick: $trailing");
    break;
  case "event-part":
    # trailing = <nick> <channel>
    irciv_term_echo("part: $trailing");
    break;
  case "event-quit":
    # trailing = <nick>
    irciv_term_echo("quit: $trailing");
    break;
}

if ($irciv_data_changed==True)
{
  set_array_bucket($irciv_games,"IRCIV_GAMES");
  set_array_bucket($irciv_accounts,"IRCIV_ACCOUNTS");
  set_array_bucket($irciv_maplist,"IRCIV_MAPLIST");
  set_array_bucket($irciv_mapdata,"IRCIV_MAPDATA");
  if ($dest<>"")
  {
    set_array_bucket($irciv_players,"IRCIV_PLAYERS_".$dest);
  }
}

#####################################################################################################

?>
