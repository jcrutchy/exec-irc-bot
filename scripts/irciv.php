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
define("ACTION_STATUS","status");
define("ACTION_SET","set");
define("ACTION_UNSET","unset");
define("ACTION_FLAG","flag");
define("ACTION_UNFLAG","unflag");

$map_coords="";
$map_data=array();
$players=array();

$players_bucket=irciv_get_bucket("players");
if ($players_bucket=="")
{
  irciv_term_echo("player bucket contains no data");
}
else
{
  $players=unserialize($players_bucket);
  if ($players===False)
  {
    irciv_err("error unserializing player bucket data");
  }
}
$coords_bucket=irciv_get_bucket("map_coords");
$data_bucket=irciv_get_bucket("map_data");
if (($coords_bucket<>"") and ($data_bucket<>""))
{
  $map_coords=map_unzip($coords_bucket);
  $map_data=unserialize($data_bucket);
}

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

if (($trailing=="") or (($dest<>GAME_CHAN) and ($nick<>NICK_EXEC) and ($dest<>NICK_EXEC)))
{
  irciv_privmsg("https://github.com/crutchy-/test");
  return;
}

$parts=explode(" ",$trailing);

$action=strtolower($parts[0]);

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
        irciv_privmsg("login: player \"$player\" is now logged in");
      }
      else
      {
        irciv_privmsg("login: player \"$player\" already logged in");
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
        irciv_privmsg("player \"$old\" renamed to \"$new\"");
      }
      else
      {
        irciv_privmsg("error renaming player \"$old\" to \"$new\"");
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
        irciv_privmsg("logout: player \"$player\" logged out");
      }
      else
      {
        irciv_privmsg("logout: there is no player logged in as \"$player\"");
      }
    }
    break;
  case "u":
  case "up":
    if (isset($players[$nick]["active"])==True)
    {
      $y=$players[$nick]["active"]["y"]-1;
      if ($y>=0)
      {
        if ($map_coords[map_coord($map_data["cols"],$players[$nick]["active"]["x"],$y)]==TERRAIN_LAND)
        {
          $players[$nick]["active"]["y"]=$y;
          $players[$nick]["status_msg"]="active unit moved up";
          cycle_active($nick);
        }
        else
        {
          $players[$nick]["status_msg"]="move up failed for active unit (already @ edge of landmass)";
        }
      }
      else
      {
        $players[$nick]["status_msg"]="move up failed for active unit (already @ top edge of map)";
      }
      status($nick);
    }
    break;
  case "d":
  case "down":
    if (isset($players[$nick]["active"])==True)
    {
      $y=$players[$nick]["active"]["y"]+1;
      if ($y<$map_data["rows"])
      {
        if ($map_coords[map_coord($map_data["cols"],$players[$nick]["active"]["x"],$y)]==TERRAIN_LAND)
        {
          $players[$nick]["active"]["y"]=$y;
          $players[$nick]["status_msg"]="active unit moved down";
          cycle_active($nick);
        }
        else
        {
          $players[$nick]["status_msg"]="move down failed for active unit (already @ edge of landmass)";
        }
      }
      else
      {
        $players[$nick]["status_msg"]="move down failed for active unit (already @ bottom edge of map)";
      }
      status($nick);
    }
    break;
  case "l":
  case "left":
    if (isset($players[$nick]["active"])==True)
    {
      $x=$players[$nick]["active"]["x"]-1;
      if ($x>=0)
      {
        if ($map_coords[map_coord($map_data["cols"],$x,$players[$nick]["active"]["y"])]==TERRAIN_LAND)
        {
          $players[$nick]["active"]["x"]=$x;
          $players[$nick]["status_msg"]="active unit moved left";
          cycle_active($nick);
        }
        else
        {
          $players[$nick]["status_msg"]="move left failed for active unit (already @ edge of landmass)";
        }
      }
      else
      {
        $players[$nick]["status_msg"]="move left failed for active unit (already @ left edge of map)";
      }
      status($nick);
    }
    break;
  case "r":
  case "right":
    if (isset($players[$nick]["active"])==True)
    {
      $x=$players[$nick]["active"]["x"]+1;
      if ($x<$map_data["cols"])
      {
        if ($map_coords[map_coord($map_data["cols"],$x,$players[$nick]["active"]["y"])]==TERRAIN_LAND)
        {
          $players[$nick]["active"]["x"]=$x;
          $players[$nick]["status_msg"]="active unit moved right";
          cycle_active($nick);
        }
        else
        {
          $players[$nick]["status_msg"]="move right failed for active unit (already @ edge of landmass)";
        }
      }
      else
      {
        $players[$nick]["status_msg"]="move right failed for active unit (already @ right edge of map)";
      }
      status($nick);
    }
    break;
  case ACTION_STATUS:
    status($nick);
    break;
  case ACTION_SET:
    if (isset($parts[1])==True)
    {
      $pair=explode("=",$parts[1]);
      if (count($pair)==2)
      {
        $key=$pair[0];
        $value=$pair[1];
        $players[$nick]["settings"][$key]=$value;
        irciv_privmsg("key \"$key\" set to value \"$value\" for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("syntax: civ set key=value");
      }
    }
    else
    {
      irciv_privmsg("syntax: civ set key=value");
    }
    break;
  case ACTION_UNSET:
    if (isset($parts[1])==True)
    {
      $key=$parts[1];
      if (isset($players[$nick]["settings"][$key])==True)
      {
        unset($players[$nick]["settings"][$key]);
        irciv_privmsg("key \"$key\" unset for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("setting \"$key\" not found for player \"$nick\"");
      }
    }
    else
    {
      irciv_privmsg("syntax: civ unset key");
    }
    break;
  case ACTION_FLAG:
    if (isset($parts[1])==True)
    {
      $flag=$parts[1];
      $players[$nick]["flags"][$flag]="";
      irciv_privmsg("flag \"$flag\" set for player \"$nick\"");
    }
    else
    {
      irciv_privmsg("syntax: civ flag name");
    }
    break;
  case ACTION_UNFLAG:
    if (isset($parts[1])==True)
    {
      $flag=$parts[1];
      if (isset($players[$nick]["flags"][$flag])==True)
      {
        unset($players[$nick]["flags"][$flag]);
        irciv_privmsg("flag \"$flag\" unset for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("flag \"$flag\" not set for player \"$nick\"");
      }
    }
    else
    {
      irciv_privmsg("syntax: civ unflag name");
    }
    break;
}

$players_bucket=serialize($players);
if ($players_bucket===False)
{
  irciv_term_echo("error serializing player bucket data");
}
else
{
  irciv_set_bucket("players",$players_bucket);
}

#####################################################################################################

function player_init($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (isset($players[$nick])==False)
  {
    return;
  }
  do
  {
    $x=mt_rand(0,$map_data["cols"]-1);
    $y=mt_rand(0,$map_data["rows"]-1);
    $coord=map_coord($map_data["cols"],$x,$y);
  }
  while ($map_coords[$coord]<>TERRAIN_LAND);
  $units=&$players[$nick]["units"];
  $units[]=unit_init("warrior",$x,$y);
  $units[0]["index"]=0;
  $players[$nick]["active"]=&$players[$nick]["units"][0];
  status($nick);
}

#####################################################################################################

function status($nick)
{
  global $players;
  global $map_data;
  if (isset($players[$nick])==False)
  {
    return;
  }
  if (isset($players[$nick]["units"])==False)
  {
    player_init($nick);
    return;
  }
  $public=False;
  if (isset($players[$nick]["flags"]["public_status"])==True)
  {
    $public=True;
  }
  $unit=&$players[$nick]["active"];
  $index=$unit["index"];
  $type=$unit["type"];
  $health=$unit["health"];
  $x=$unit["x"];
  $y=$unit["y"];
  $n=count($players[$nick]["units"]);
  status_msg($nick,GAME_CHAN."/$nick => $index/$n, $type, +$health, ($x,$y)",$public);
  if (isset($players[$nick]["status_msg"])==True)
  {
    status_msg($nick,GAME_CHAN."/$nick => ".$players[$nick]["status_msg"],$public);
    unset($players[$nick]["status_msg"]);
  }
}

#####################################################################################################

function status_msg($nick,$msg,$public)
{
  if ($public==False)
  {
    pm($nick,$msg);
  }
  else
  {
    irciv_privmsg($msg);
  }
}

#####################################################################################################

function unit_init($type,$x,$y)
{
  global $map_data;
  $data["type"]=$type;
  $data["health"]=100;
  $data["x"]=$x;
  $data["y"]=$y;
  return $data;
}

#####################################################################################################

function cycle_active($nick)
{
  global $players;

}

#####################################################################################################

?>
