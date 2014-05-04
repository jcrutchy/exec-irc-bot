<?php

# gpl2
# by crutchy
# 04-may-2014

# irciv.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");
define("ACTION_RENAME","rename");
define("ACTION_MOVE","move");

$map_coords="";
$map_data=array();
$players=array();

$players_bucket=irciv__get_bucket("players");
if ($players_bucket=="")
{
  irciv__term_echo("player bucket contains no data");
}
else
{
  $players=unserialize($players_bucket);
  if ($players===False)
  {
    irciv__err("error unserializing player bucket data");
  }
}

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

$parts=explode(" ",$trailing);

if ((count($parts)<=1) or (($dest<>GAME_CHAN) and ($nick<>NICK_EXEC)))
{
  irciv__privmsg("https://github.com/crutchy-/test");
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
        irciv__privmsg("login: player \"$player\" is now logged in");
      }
      else
      {
        irciv__privmsg("login: player \"$player\" already logged in");
      }
    }
    break;
  case ACTION_RENAME:
    if ((isset($parts[1])==True) and (isset($parts[2])==True) and ($nick==NICK_EXEC))
    {
      $old=$parts[1];
      $new=$parts[2];
      if ((isset($players[$old])==True) and (isset($players[$new])==False))
      {
        $player_data=$players[$old];
        $players[$new]=$player_data;
        unset($players[$old]);
        irciv__privmsg("player \"$old\" renamed to \"$new\"");
      }
      else
      {
        irciv__privmsg("error renaming player \"$old\" to \"$new\"");
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
        irciv__privmsg("logout: player \"$player\" logged out");
      }
      else
      {
        irciv__privmsg("logout: there is no player logged in as \"$player\"");
      }
    }
    break;
  case ACTION_MOVE:
    player_init($nick);
    break;
}

$players_bucket=serialize($players);
if ($players_bucket===False)
{
  irciv__term_echo("error serializing player bucket data");
}
else
{
  irciv__set_bucket("players",$players_bucket);
}

#####################################################################################################

function player_init($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (isset($players[$nick])==False)
  {
    die();
  }
  $coords_bucket=irciv__get_bucket("map_coords");
  $data_bucket=irciv__get_bucket("map_data");
  if (($coords_bucket<>"") and ($data_bucket<>""))
  {
    $map_coords=map_unzip($coords_bucket);
    $map_data=unserialize($data_bucket);
  }
  else
  {
    irciv__privmsg("map coords and/or data bucket(s) not found");
    die();
  }
  do
  {
    $coord=mt_rand(0,$map_data["cols"]*$map_data["rows"]);
  }
  while ($map_coords[$coord]<>TERRAIN_LAND);
  $players[$nick]["units"][]=unit_init("warrior",$coord);
}

#####################################################################################################

function unit_init($type,$coord)
{
  $data["type"]=$type;
  $data["health"]=100;
  $data["coord"]=$coord;
  return $data;
}

#####################################################################################################

?>
