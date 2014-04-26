<?php

# gpl2
# by crutchy
# 26-april-2014

# irciv.php

#####################################################################################################

ini_set("display_errors","on");

define("GAME_NAME","IRCiv");
define("GAME_CHAN","#civ");
define("NICK_EXEC","exec");

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");
define("ACTION_RENAME","rename");

irciv__term_echo("running...");

$buckets["civ"]["players"]=array();
get_bucket();

$players=&$buckets["civ"]["players"];

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

echo "dest=$dest\n";

$parts=explode(" ",$trailing);

if ((count($parts)<=1) or (($dest<>GAME_CHAN) and ($nick<>NICK_EXEC)))
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

set_bucket();

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

function get_bucket()
{
  global $buckets;
  echo ":".NICK_EXEC." BUCKET_GET :\$buckets[\"civ\"]\n";
  $f=fopen("php://stdin","r");
  $line=fgets($f);
  if ($line===False)
  {
    irciv__err("unable to read bucket data");
  }
  else
  {
    $line=trim($line);
    if (($line<>"") and ($line<>"NO BUCKET DATA FOR WRITING TO STDIN") and ($line<>"BUCKET EVAL ERROR"))
    {
      echo "$line\n";
      $tmp=unserialize($line);
      if ($tmp!==False)
      {
        $buckets["civ"]=$tmp;
        irciv__term_echo("successfully loaded bucket data");
      }
      else
      {
        irciv__term_echo("error unserializing bucket data");
      }
    }
    else
    {
      irciv__term_echo("no bucket data to load");
    }
  }
  fclose($f);
}

#####################################################################################################

function set_bucket()
{
  global $buckets;
  $data=serialize($buckets);
  echo ":".NICK_EXEC." BUCKET_SET :$data\n";
}

#####################################################################################################

?>
