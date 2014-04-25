<?php

# gpl2
# by crutchy
# 25-april-2014

# irciv.php

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");

define("GAME_NAME","IRCiv");
define("NICK_LOGIN","exec");

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");

irciv__term_echo("running...");

$nick=$argv[1];
$trailing=$argv[2];

$parts=explode(" ",$trailing);

if (count($parts)<=1)
{
  irciv__privmsg("by crutchy");
  return;
}

/*require_once(__DIR__."/db.php");
require_once(__DIR__."/db_players.php");
require_once(__DIR__."/db_games.php");

$pdo=db__connect();*/

$action=$parts[0];

switch ($action)
{
  case ACTION_LOGIN:
    if ((isset($parts[1])==True) and (isset($parts[2])==True) and ($nick==NICK_LOGIN))
    {
      $player_nick=$parts[1];
      $player_account=$parts[2]; 
      irciv__privmsg("player \"$player_nick\" logged in under account \"$player_account\"");
    }
    break;
  case ACTION_LOGOUT:
    if (isset($parts[1])==True)
    {
      $player_nick=$parts[1];
      irciv__privmsg("player \"$player_nick\" logged out");
    }
    break;
}

/*
$salt=crypt($parts[0]);
$pwd=crypt($parts[0],$salt);
db_players__insert($nick,$pwd,$email);
*/

#####################################################################################################

function irciv__term_echo($msg)
{
  echo GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__privmsg($msg)
{
  echo "IRC_MSG ".GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__err($msg)
{
  echo "IRC_MSG ".GAME_NAME." error: $msg\n";
  die();
}

#####################################################################################################

?>
