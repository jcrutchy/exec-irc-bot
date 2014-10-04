<?php

# gpl2
# by crutchy

#####################################################################################################

define("BUCKET_IRCIV_GAMES","IRCIV_GAMES");
define("BUCKET_IRCIV_ACCOUNTS","IRCIV_ACCOUNTS");
define("BUCKET_IRCIV_PLAYERS_PREFIX","IRCIV_PLAYERS_");
define("BUCKET_IRCIV_MAPLIST","IRCIV_MAPLIST");
define("BUCKET_IRCIV_MAPDATA","IRCIV_MAPDATA");

date_default_timezone_set("UTC");

require_once("irciv_lib.php");
require_once("irciv_lib_data.php");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

if (($cmd=="INTERNAL") and ($nick==NICK_EXEC) and ($trailing=="startup"))
{
  civ_startup();
  return;
}

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);

$irciv_data_changed=False;

$irciv_games=get_array_bucket(BUCKET_IRCIV_GAMES);
$irciv_accounts=get_array_bucket(BUCKET_IRCIV_ACCOUNTS);
$irciv_maplist=get_array_bucket(BUCKET_IRCIV_MAPLIST);
$irciv_mapdata=get_array_bucket(BUCKET_IRCIV_MAPDATA);
$irciv_players=get_array_bucket(BUCKET_IRCIV_PLAYERS_PREFIX.$dest);

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

if ($irciv_data_changed==True)
{
  get_array_bucket(BUCKET_IRCIV_GAMES,$irciv_games);
  get_array_bucket(BUCKET_IRCIV_ACCOUNTS,$irciv_accounts);
  get_array_bucket(BUCKET_IRCIV_MAPLIST,$irciv_maplist);
  get_array_bucket(BUCKET_IRCIV_MAPDATA,$irciv_mapdata);
  if ($dest<>"")
  {
    get_array_bucket(BUCKET_IRCIV_PLAYERS_PREFIX.$dest,$irciv_players);
  }
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
