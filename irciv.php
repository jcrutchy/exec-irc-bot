<?php

# gpl2
# by crutchy
# 26-april-2014

# irciv.php

#####################################################################################################

ini_set("display_errors","on");

define("GAME_NAME","IRCiv");
define("NICK_EXEC","exec");

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");
define("ACTION_RENAME","rename");

irciv__term_echo("running...");

$players=array();

$nick=$argv[1];
$trailing=$argv[2];

$parts=explode(" ",$trailing);

if (count($parts)<=1)
{
  irciv__privmsg("by crutchy");
  return;
}

$action=$parts[0];

switch ($action)
{
  case ACTION_LOGIN:
    if ((isset($parts[1])==True) and (isset($parts[2])==True) and ($nick==NICK_EXEC))
    {
      $player=$parts[1];
      $account=$parts[2];
      if (isset($players[$player])==False)
      {
        $players[$player]["account"]=$account;
        irciv__privmsg("player \"$player\" is now logged in");
      }
      else
      {
        irciv__privmsg("player \"$player\" already logged in");
      }
    }
    break;
  case ACTION_RENAME:
    if ((isset($parts[1])==True) and (isset($parts[2])==True) and ($nick==NICK_EXEC))
    {
      $old=$parts[1];
      $new=$parts[2];
      if (isset($players[$old])==True)
      {
        irciv__privmsg("player \"$old\" is now known as \"$new\"");
      }
      else
      {
        irciv__privmsg("there is no player logged in as \"$old\"");
      }
    }
    break;
  case ACTION_LOGOUT:
    if (isset($parts[1])==True)
    {
      $player=$parts[1];
      if (isset($players[$player])==True)
      {
        unset($players[$player]);
        irciv__privmsg("player \"$player\" logged out");
      }
      else
      {
        irciv__privmsg("there is no player logged in as \"$player\"");
      }
    }
    break;
}

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
