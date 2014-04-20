<?php

# gpl2
# by crutchy
# 20-april-2014

# irciv.php

#####################################################################################################

# exec line: 5|0|1|civ|php irciv.php %%nick%% %%msg%%

# messages are sent privately to irc bot (to avoid pwd visibility)
# %%msg%%: pwd action (action operated on currently highlighted unit)
# cmd: add|edit|delete|move

# IRCiv
# real-time play-by-irc civilization-building game
# aim: to build a civilization, make money, fend off enemies to maintain borders, expand, etc
# (similar to other civilization-branded games)

# TODO:
# - put data files on web server (read-only) so that anyone can draw maps, display info, etc
# - use password_hash function
# - intended to use a query window (private message to server nick) to send commands, typing "password command", or from a public channel typing "/msg servernick password command"

# /msg exec civ crutchy insert player test@test.com

# http://civ.dev/

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");

define("GAME_NAME","IRCiv");

define("CMD_INSERT","insert");
define("OBJ_PLAYER","player");

irciv__term_echo("running...");

require_once(__DIR__."/db.php");
require_once(__DIR__."/db_players.php");
require_once(__DIR__."/db_games.php");

if ((isset($argv[1])==False) or (isset($argv[2])==False))
{
  irciv__err("exec error");
}
$nick=$argv[1];
$msg=$argv[2];

$parts=explode(" ",$msg);

$salt=crypt($parts[0]);
$pwd=crypt($parts[0],$salt);

if (count($parts)<=1)
{
  irciv__privmsg("by crutchy");
  return;
}

$pdo=db__connect();

if (isset($parts[1])==True)
{
  switch (strtolower($parts[1]))
  {
    case CMD_INSERT:
      if (isset($parts[2])==True)
      {
        switch (strtolower($parts[2]))
        {
          case OBJ_PLAYER:
            if (isset($parts[3])==True)
            {
              $email=$parts[3];
              db_players__insert($nick,$pwd,$email);
            }
            break;
        }
      }
      break;
  }
}

#####################################################################################################

function irciv__term_echo($msg)
{
  echo GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__privmsg($msg)
{
  echo "privmsg ".GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__err($msg)
{
  echo "privmsg ".GAME_NAME." error: $msg\n";
  die();
}

#####################################################################################################

?>
