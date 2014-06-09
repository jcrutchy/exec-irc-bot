<?php

# gpl2
# by crutchy
# 5-june-2014

# irciv.php

# TODO: ~civ-admin spawn-server #game

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("irciv_lib.php");

if ($argv[1]=="<<SAVE>>")
{
  irciv_save_data();
  return;
}

define("TIMEOUT_RANDOM_COORD",10); # sec

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");
define("ACTION_RENAME","rename");
define("ACTION_INIT","init");
define("ACTION_STATUS","status");
define("ACTION_SET","set");
define("ACTION_UNSET","unset");
define("ACTION_FLAG","flag");
define("ACTION_UNFLAG","unflag");

define("ACTION_ADMIN_PLAYER_DATA","player-data");
define("ACTION_ADMIN_PLAYER_UNSET","player-unset");
define("ACTION_ADMIN_PLAYER_EDIT","player-edit");
define("ACTION_ADMIN_OBJECT_EDIT","object-edit");
define("ACTION_ADMIN_PLAYER_LIST","player-list");
define("ACTION_ADMIN_MOVE_UNIT","move-unit");
define("ACTION_ADMIN_PART","part");

define("MIN_CITY_SPACING",3);

# dl,ds,da,al,as,aa
$unit_strengths["settler"]="2,0,0,0,0,0";
$unit_strengths["warrior"]="1,0,0,1,0,0";

$update_players=False;

$admin_alias="~civ-admin";

$map_coords="";
$map_data=array();
$players=array();

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];
$start=$argv[4];
$alias=$argv[5];

if (($trailing=="") or ((in_array($dest,$game_chans)==False) and ($nick<>NICK_EXEC)))
{
  irciv_privmsg("https://github.com/crutchy-/test");
  return;
}

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
$map_coords=array();
$map_data=array();
for ($i=0;$i<count($game_chans);$i++)
{
  $map_coords[$game_chans[$i]]=irciv_get_bucket("map_coords_".$game_chans[$i]);
  $map_data[$game_chans[$i]]=irciv_get_bucket("map_data_".$game_chans[$i]);
  if (($map_coords[$game_chans[$i]]=="") or ($map_data[$game_chans[$i]]==""))
  {
    irciv_privmsg("map for channel \"$dest\" not found");
  }
}

$parts=explode(" ",$trailing);

$action=strtolower($parts[0]);

validate_logins();

if ($nick<>NICK_EXEC)
{
  if (is_logged_in($nick)==False)
  {
    irciv_privmsg("access denied: nick \"$nick\" not logged in");
    return;
  }
}

# ADD NEW PROPERTIES TO EXISTING PLAYER DATA
/*foreach ($players as $player => $data)
{
  for ($i=0;$i<count($players[$player]["units"]);$i++)
  {
    $players[$player]["units"][$i]["strength"]=$unit_strengths[$players[$player]["units"][$i]["type"]];
  }
}*/

switch ($action)
{
  case "help":
  case "?":
    if (count($parts)==1)
    {
      output_help();
    }
    break;
  case ACTION_LOGIN:
    if ((count($parts)==3) and ($nick==NICK_EXEC))
    {
      $player=$parts[1];
      $account=$parts[2];
      if (isset($players[$player])==False)
      {
        $player_id=get_new_player_id();
        $players[$player]["account"]=$account;
        $players[$player]["player_id"]=$player_id;
        player_init($player);
        privmsg_player_game_chans($player,"login: welcome new player \"$player\"");
      }
      else
      {
        privmsg_player_game_chans($player,"login: welcome back \"$player\"");
      }
      $players[$player]["login_time"]=microtime(True);
      $players[$player]["logged_in"]=True;
      $update_players=True;
      irciv_term_echo("PLAYER \"$player\" LOGIN");
    }
    break;
  case ACTION_RENAME:
    if ((count($parts)==3) and (($nick==NICK_EXEC) or ($alias==$admin_alias)))
    {
      $old=$parts[1];
      $new=$parts[2];
      if ((isset($players[$old])==True) and (isset($players[$new])==False))
      {
        $player_data=$players[$old];
        $players[$new]=$player_data;
        unset($players[$old]);
        $update_players=True;
        privmsg_player_game_chans($old,"player \"$old\" renamed to \"$new\"");
        $chan_list=get_bucket($old."_channel_list");
        if ($chan_list<>"")
        {
          set_bucket($new."_channel_list",$chan_list);
        }
        irciv_term_echo("PLAYER \"$old\" RENAMED TO \"$new\"");
      }
      else
      {
        if (isset($players[$old])==True)
        {
          privmsg_player_game_chans($old,"error renaming player \"$old\" to \"$new\"");
        }
      }
    }
    else
    {
      if ($nick<>NICK_EXEC)
      {
        irciv_term_echo("ACTION_RENAME: only exec can perform logins");
      }
      else
      {
        irciv_term_echo("ACTION_RENAME: invalid login message");
      }
    }
    break;
  case ACTION_LOGOUT:
    if (count($parts)==2)
    {
      $player=$parts[1];
      if (isset($players[$player])==True)
      {
        $players[$player]["logged_in"]=False;
        $update_players=True;
        privmsg_player_game_chans($player,"logout: player \"$player\" logged out");
        irciv_term_echo("PLAYER \"$player\" LOGOUT");
      }
      else
      {
        irciv_term_echo("logout: there is no player logged in as \"$player\"");
      }
    }
    break;
  case ACTION_ADMIN_PART:
    if ((count($parts)==1) and ($alias==$admin_alias))
    {
      echo "IRC_RAW PART $dest :bye\n";
    }
    break;
  case ACTION_ADMIN_PLAYER_UNSET:
    if ((count($parts)==2) and ($alias==$admin_alias))
    {
      $player=$parts[1];
      if (isset($players[$player])==True)
      {
        unset($players[$player]);
        irciv_privmsg("admin: unset \"$player\"");
        $update_players=True;
      }
      else
      {
        irciv_privmsg("admin: player \"$player\" not found");
      }
    }
    break;
  case ACTION_ADMIN_PLAYER_LIST:
    if ((count($parts)==1) and ($alias==$admin_alias))
    {
      foreach ($players as $player => $data)
      {
        irciv_privmsg("[".$data["player_id"]."] ".$player);
      }
    }
    break;
  case ACTION_ADMIN_PLAYER_DATA:
    if ($alias==$admin_alias)
    {
      if (count($parts)==2)
      {
        $player=$parts[1];
        if (isset($players[$player])==True)
        {
          var_dump($players[$player]);
        }
        else
        {
          irciv_privmsg("player \"$player\" not found");
        }
      }
      else
      {
        var_dump($players);
      }
    }
    break;
  case ACTION_ADMIN_MOVE_UNIT:
    if ($alias==$admin_alias)
    {
      if (count($parts)==5)
      {
        $player=$parts[1];
        $index=$parts[2];
        $x=$parts[3];
        $y=$parts[4];
        if (isset($players[$player]["units"][$index])==True)
        {
          $players[$player]["units"][$index]["x"]=$x;
          $players[$player]["units"][$index]["y"]=$y;
          unfog($player,$x,$y,$players[$player]["units"][$index]["sight_range"]);
          $update_players=True;
          update_other_players($player,$index);
        }
        else
        {
          irciv_privmsg("players[$player][units][$index] not found");
        }
      }
      else
      {
        irciv_privmsg("syntax: [~civ] move-unit nick index x y");
      }
    }
    break;
  case ACTION_ADMIN_OBJECT_EDIT:
    if ($alias==$admin_alias)
    {
      if (count($parts)>=5)
      {
        $player=$parts[1];
        $array=$parts[2];
        $index=$parts[3];
        $key=$parts[4];
        for ($i=1;$i<=5;$i++)
        {
          array_shift($parts);
        }
        $value=implode(" ",$parts);
        if ($key<>"")
        {
          if (isset($players[$player][$array][$index])==True)
          {
            if ($value=="<unset>")
            {
              unset($players[$player][$array][$index][$key]);
              irciv_privmsg("players[$player][$array][$index][$key] unset");
            }
            else
            {
              $players[$player][$array][$index][$key]=$value;
              irciv_privmsg("players[$player][$array][$index][$key]=$value");
            }
            $update_players=True;
          }
          else
          {
            irciv_privmsg("players[$player][$array][$index] not found");
          }
        }
        else
        {
          irciv_privmsg("invalid key");
        }
      }
      else
      {
        irciv_privmsg("syntax: [~civ] object-edit <nick> <array> <index> <key> [<value>|\"<unset>\"]");
      }
    }
    break;
  case ACTION_ADMIN_PLAYER_EDIT:
    if ($alias==$admin_alias)
    {
      if (count($parts)>=3)
      {
        $player=$parts[1];
        $key=$parts[2];
        for ($i=1;$i<=3;$i++)
        {
          array_shift($parts);
        }
        $value=implode(" ",$parts);
        if ($key<>"")
        {
          if (isset($players[$player])==True)
          {
            if ($value=="<unset>")
            {
              unset($players[$player][$key]);
              irciv_privmsg("key \"$key\" unset for player \"$player\"");
            }
            else
            {
              $players[$player][$key]=$value;
              irciv_privmsg("key \"$key\" set with value \"$value\" for player \"$player\"");
            }
            $update_players=True;
          }
          else
          {
            irciv_privmsg("player \"$player\" not found");
          }
        }
        else
        {
          irciv_privmsg("invalid key");
        }
      }
      else
      {
        irciv_privmsg("syntax: [~civ] player-edit <nick> <key> [<value>|\"<unset>\"]");
      }
    }
    break;
  case ACTION_INIT:
    if (count($parts)==1)
    {
      player_init($nick);
      $update_players=True;
      irciv_privmsg("data player \"$nick\" has been initialized");
    }
    else
    {
      irciv_privmsg("syntax: [~civ] init");
    }
    break;
  case "u":
  case "up":
    if (count($parts)==1)
    {
      move_active_unit($nick,0);
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (up|u)");
    }
    break;
  case "r":
  case "right":
    if (count($parts)==1)
    {
      move_active_unit($nick,1);
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (right|r)");
    }
    break;
  case "d":
  case "down":
    if (count($parts)==1)
    {
      move_active_unit($nick,2);
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (down|d)");
    }
    break;
  case "l":
  case "left":
    if (count($parts)==1)
    {
      move_active_unit($nick,3);
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (left|l)");
    }
    break;
  case "b":
  case "build":
    if (count($parts)>1)
    {
      unset($parts[0]);
      $city_name=implode(" ",$parts);
      build_city($nick,$city_name);
      $update_players=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (build|b) City Name");
    }
    break;
  case ACTION_STATUS:
    output_map($nick);
    status($nick);
    break;
  case ACTION_SET:
    if (count($parts)==2)
    {
      $pair=explode("=",$parts[1]);
      if (count($pair)==2)
      {
        $key=$pair[0];
        $value=$pair[1];
        $players[$nick]["settings"][$key]=$value;
        $update_players=True;
        irciv_privmsg("key \"$key\" set to value \"$value\" for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("syntax: [~civ] set key=value");
      }
    }
    else
    {
      irciv_privmsg("syntax: [~civ] set key=value");
    }
    break;
  case ACTION_UNSET:
    if (count($parts)==2)
    {
      $key=$parts[1];
      if (isset($players[$nick]["settings"][$key])==True)
      {
        unset($players[$nick]["settings"][$key]);
        $update_players=True;
        irciv_privmsg("key \"$key\" unset for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("setting \"$key\" not found for player \"$nick\"");
      }
    }
    else
    {
      irciv_privmsg("syntax: [~civ] unset key");
    }
    break;
  case ACTION_FLAG:
    if (count($parts)==2)
    {
      $flag=$parts[1];
      $players[$nick]["flags"][$flag]="";
      $update_players=True;
      irciv_privmsg("flag \"$flag\" set for player \"$nick\"");
    }
    else
    {
      irciv_privmsg("syntax: [~civ] flag name");
    }
    break;
  case ACTION_UNFLAG:
    if (count($parts)==2)
    {
      $flag=$parts[1];
      if (isset($players[$nick]["flags"][$flag])==True)
      {
        unset($players[$nick]["flags"][$flag]);
        $update_players=True;
        irciv_privmsg("flag \"$flag\" unset for player \"$nick\"");
      }
      else
      {
        irciv_privmsg("flag \"$flag\" not set for player \"$nick\"");
      }
    }
    else
    {
      irciv_privmsg("syntax: [~civ] unflag name");
    }
    break;
}

if ($update_players==True)
{
  $players_bucket=serialize($players);
  if ($players_bucket===False)
  {
    irciv_term_echo("error serializing player bucket data");
  }
  else
  {
    irciv_unset_bucket("players");
    irciv_set_bucket("players",$players_bucket);
  }
}

#####################################################################################################

function privmsg_player_game_chans($nick,$msg)
{
  global $game_chans;
  $nick_chans=get_bucket($nick."_channel_list");
  if ($nick_chans=="")
  {
    irciv_term_echo("priv_msg_all_player_game_chans: nick \"$nick\" channels not set");
  }
  $nick_chans=explode(" ",$nick_chans);
  for ($i=0;$i<count($game_chans);$i++)
  {
    if (in_array($game_chans[$i],$nick_chans)==True)
    {
      echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$game_chans[$i]." :$msg\n";
    }
  }
}

#####################################################################################################

function set_player_color($nick,$color="")
{
  global $players;
  $reserved_colors=array("255,0,255","0,0,0","255,255,255");
  if ($color=="")
  {
    do
    {
      $color=mt_rand(0,255).",".mt_rand(0,255).",".mt_rand(0,255);
      foreach ($players as $player => $data)
      {
        if (($player<>$nick) and ($color==$players[$player]["color"]))
        {
          continue;
        }
      }
    }
    while (in_array($color,$reserved_colors)==True);
    $players[$nick]["color"]=$color;
  }
  else
  {
    foreach ($players as $player => $data)
    {
      if (($player<>$nick) and ($color==$players[$player]["color"]))
      {
        return False;
      }
    }
    if (in_array($color,$reserved_colors)==True)
    {
      return False;
    }
    $players[$nick]["color"]=$color;
    return True;
  }
}

#####################################################################################################

function get_new_player_id()
{
  global $players;
  $player_id=1;
  foreach ($players as $nick => $data)
  {
    if ($player_id<=$data["player_id"])
    {
      $player_id=$data["player_id"]+1;
    }
  }
  return $player_id;
}

#####################################################################################################

function validate_logins()
{
  global $players;
  global $start;
  foreach ($players as $nick => $data)
  {
    if (isset($players[$nick]["login_time"])==True)
    {
      if ($players[$nick]["login_time"]<$start)
      {
        $players[$nick]["logged_in"]=False;
      }
    }
  }
}

#####################################################################################################

function is_logged_in($nick)
{
  global $players;
  if (isset($players[$nick]["logged_in"])==False)
  {
    return False;
  }
  if ($players[$nick]["logged_in"]==False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function output_help()
{
  irciv_privmsg("QUICK START GUIDE");
  irciv_privmsg("unit movement: (left|l),(right|r),(up|u),(down|d)");
  irciv_privmsg("settler actions: (build|b)");
  irciv_privmsg("player functions: (help|?),status,init,flag/unflag,set/unset");
  irciv_privmsg("flags: public_status,grid,coords,city_names");
}

#####################################################################################################

function player_ready($nick)
{
  global $players;
  global $map_data;
  if (isset($map_data["cols"])==False)
  {
    irciv_privmsg("error: map not ready");
    return False;
  }
  if (isset($players[$nick])==False)
  {
    irciv_privmsg("player \"$nick\" not found");
    return False;
  }
  return True;
}

#####################################################################################################

function player_init($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
  {
    return;
  }
  $players[$nick]["init_time"]=time();
  set_player_color($nick);
  $players[$nick]["units"]=array();
  $players[$nick]["cities"]=array();
  $players[$nick]["fog"]=str_repeat("0",strlen($map_coords));
  $start_x=-1;
  $start_y=-1;
  if (random_coord(TERRAIN_LAND,$start_x,$start_y)==False)
  {
    return;
  }
  add_unit($nick,"settler",$start_x,$start_y);
  add_unit($nick,"warrior",$start_x,$start_y);
  $players[$nick]["active"]=-1;
  cycle_active($nick);
  $players[$nick]["start_x"]=$start_x;
  $players[$nick]["start_y"]=$start_y;
  status($nick);
}

#####################################################################################################

function random_coord($terrain,&$x,&$y)
{
  global $map_coords;
  global $map_data;
  $start=microtime(True);
  do
  {
    $x=mt_rand(0,$map_data["cols"]-1);
    $y=mt_rand(0,$map_data["rows"]-1);
    $coord=map_coord($map_data["cols"],$x,$y);
    $dt=microtime(True)-$start;
    if ($dt>TIMEOUT_RANDOM_COORD)
    {
      irciv_privmsg("error: random_coord timeout");
      return False;
    }
  }
  while ($map_coords[$coord]<>$terrain);
  return True;
}

#####################################################################################################

function add_unit($nick,$type,$x,$y)
{
  global $players;
  global $unit_strengths;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $units=&$players[$nick]["units"];
  $data["type"]=$type;
  $data["health"]=100;
  $data["sight_range"]=4;
  $data["x"]=$x;
  $data["y"]=$y;
  $data["strength"]=$unit_strengths[$type];
  $units[]=$data;
  $i=count($units)-1;
  $units[$i]["index"]=$i;
  unfog($nick,$x,$y,$data["sight_range"]);
  return True;
}

#####################################################################################################

function add_city($nick,$x,$y,$city_name)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return;
  }
  $cities=&$players[$nick]["cities"];
  $data["name"]=$city_name;
  $data["population"]=1;
  $data["size"]=1;
  $data["sight_range"]=7;
  $data["x"]=$x;
  $data["y"]=$y;
  $cities[]=$data;
  $i=count($cities)-1;
  $cities[$i]["index"]=$i;
  unfog($nick,$x,$y,$data["sight_range"]);
}

#####################################################################################################

function unfog($nick,$x,$y,$range)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  $size=2*$range+1;
  $region=imagecreate($size,$size);
  $white=imagecolorallocate($region,255,255,255);
  $black=imagecolorallocate($region,0,0,0);
  imagefill($region,0,0,$white);
  imagefilledellipse($region,$range,$range,$size,$size,$black);
  for ($j=0;$j<$size;$j++)
  {
    for ($i=0;$i<$size;$i++)
    {
      $xx=$x-$range+$i;
      $yy=$y-$range+$j;
      if (imagecolorat($region,$i,$j)==$black)
      {
        if (($xx>=0) and ($yy>=0) and ($xx<$cols) and ($yy<$rows))
        {
          $coord=map_coord($cols,$xx,$yy);
          $players[$nick]["fog"][$coord]="1";
        }
      }
    }
  }
  imagedestroy($region);
}

#####################################################################################################

function status($nick)
{
  global $players;
  global $map_data;
  global $dest;
  if (isset($players[$nick])==False)
  {
    return;
  }
  /*$public=False;
  if (isset($players[$nick]["flags"]["public_status"])==True)
  {
    $public=True;
  }*/
  $public=True; # TODO: DELETE & RESTORE CODE ABOVE
  $i=$players[$nick]["active"];
  $unit=$players[$nick]["units"][$i];
  $index=$unit["index"];
  $type=$unit["type"];
  $health=$unit["health"];
  $x=$unit["x"];
  $y=$unit["y"];
  $n=count($players[$nick]["units"]);
  if (isset($players[$nick]["status_messages"])==True)
  {
    for ($i=0;$i<count($players[$nick]["status_messages"]);$i++)
    {
      status_msg($nick,$dest." $nick => ".$players[$nick]["status_messages"][$i],$public);
    }
    unset($players[$nick]["status_messages"]);
  }
  status_msg($nick,$dest." $nick => $index/$n, $type, +$health, ($x,$y)",$public);
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

function move_active_unit($nick,$dir)
{
  global $players;
  global $map_data;
  global $map_coords;
  global $update_players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  $captions=array("up","right","down","left");
  if (isset($players[$nick]["active"])==True)
  {
    $active=$players[$nick]["active"];
    $old_x=$players[$nick]["units"][$active]["x"];
    $old_y=$players[$nick]["units"][$active]["y"];
    $x=$old_x+$dir_x[$dir];
    $y=$old_y+$dir_y[$dir];
    $caption=$captions[$dir];
    if (($x<0) or ($x>=$map_data["cols"]) or ($y<0) or ($y>=$map_data["rows"]))
    {
      $players[$nick]["status_messages"][]="move $caption failed for active unit (already @ edge of map)";
    }
    elseif ($map_coords[map_coord($map_data["cols"],$x,$y)]<>TERRAIN_LAND)
    {
      $players[$nick]["status_messages"][]="move $caption failed for active unit (already @ edge of landmass)";
    }
    else
    {
      $player=is_foreign_unit($nick,$x,$y);
      if ($player===False)
      {
        $players[$nick]["units"][$active]["x"]=$x;
        $players[$nick]["units"][$active]["y"]=$y;
        unfog($nick,$x,$y,$players[$nick]["units"][$active]["sight_range"]);
        $type=$players[$nick]["units"][$active]["type"];
        $players[$nick]["status_messages"][]="successfully moved $type $caption from ($old_x,$old_y) to ($x,$y)";
        $update_players=True;
        update_other_players($nick,$active);
        cycle_active($nick);
      }
      else
      {
        $players[$nick]["status_messages"][]="move $caption failed for active unit (player \"$player\" is occupying)";
        # if player is enemy, attack!
      }
    }
    status($nick);
  }
}

#####################################################################################################

function is_foreign_unit($nick,$x,$y)
{
  global $players;
  foreach ($players as $player => $data)
  {
    if ($player<>$nick)
    {
      for ($i=0;$i<count($players[$player]["units"]);$i++)
      {
        $unit=$players[$player]["units"][$i];
        if (($unit["x"]==$x) and ($unit["y"]==$y))
        {
          return $player;
        }
      }
    }
  }
  return False;
}

#####################################################################################################

function is_fogged($nick,$x,$y)
{
  global $players;
  global $map_data;
  $cols=$map_data["cols"];
  $coord=map_coord($cols,$x,$y);
  if ($players[$nick]["fog"][$coord]=="0")
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function update_other_players($nick,$active)
{
  global $players;
  global $map_data;
  global $map_coords;
  $x=$players[$nick]["units"][$active]["x"];
  $y=$players[$nick]["units"][$active]["y"];
  foreach ($players as $player => $data)
  {
    if ($player==$nick)
    {
      continue;
    }
    if (player_ready($player)==False)
    {
      continue;
    }
    if (is_fogged($player,$x,$y)==False)
    {
      $players[$player]["status_messages"][]="player \"$nick\" moved a unit within your field of vision";
      $players[$nick]["status_messages"][]="you moved a unit within the field of vision of player \"$player\"";
      output_map($player);
      status($player);
    }
  }
}

#####################################################################################################

function delete_unit($nick,$index)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  if (isset($players[$nick]["units"][$index])==False)
  {
    return False;
  }
  $count=count($players[$nick]["units"]);
  $next=$index+1;
  for ($i=$next;$i<$count;$i++)
  {
    $players[$nick]["units"][$i]["index"]=$i-1;
  }
  unset($players[$nick]["units"][$index]);
  $players[$nick]["units"]=array_values($players[$nick]["units"]);
  return True;
}

#####################################################################################################

function build_city($nick,$city_name)
{
  global $players;
  global $map_data;
  global $map_coords;
  if (player_ready($nick)==False)
  {
    return False;
  }
  if (isset($players[$nick]["active"])==False)
  {
    return False;
  }
  $unit=$players[$nick]["units"][$players[$nick]["active"]];
  if ($unit["type"]<>"settler")
  {
    $players[$nick]["status_messages"][]="only settlers can build cities";
  }
  else
  {
    $x=$unit["x"];
    $y=$unit["y"];
    $city_exists=False;
    $city_adjacent=False;
    $cities=&$players[$nick]["cities"];
    for ($i=0;$i<count($cities);$i++)
    {
      if ($cities[$i]["name"]==$city_name)
      {
        $city_exists=True;
        $players[$nick]["status_messages"][]="city named \"$city_name\" already exists";
        break;
      }
      $dx=abs($cities[$i]["x"]-$x);
      $dy=abs($cities[$i]["y"]-$y);
      if (($dx<MIN_CITY_SPACING) and ($dy<MIN_CITY_SPACING))
      {
        $city_adjacent=True;
        $players[$nick]["status_messages"][]="city \"".$cities[$i]["name"]."\" is too close";
        break;
      }
    }
    if (($city_exists==False) and ($city_adjacent==False))
    {
      add_city($nick,$x,$y,$city_name);
      #delete_unit($nick,$players[$nick]["active"]); # WORKS BUT LEAVE OUT FOR TESTING
      $players[$nick]["status_messages"][]="successfully established the new city of \"$city_name\" at coordinates ($x,$y)";
      cycle_active($nick);
    }
  }
  status($nick);
}

#####################################################################################################

function output_map($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $game_id=sprintf("%02d",0);
  $player_id=sprintf("%02d",$players[$nick]["player_id"]);
  $timestamp=date("YmdHis",time());
  $key=random_string(16);
  $filename=$game_id.$player_id.$timestamp.$key;
  $response=upload_map_image($filename,$map_coords,$map_data,$players,$nick);
  $response_lines=explode("\n",$response);
  $msg=trim($response_lines[count($response_lines)-1]);
  if (trim($response_lines[0])=="HTTP/1.1 200 OK")
  { 
    if ($msg=="SUCCESS")
    {
      $players[$nick]["status_messages"][]="http://irciv.port119.net/?pid=".$players[$nick]["player_id"];
      #$players[$nick]["status_messages"][]="http://irciv.port119.net/?map=$filename";
    }
  }
  else
  {
    $players[$nick]["status_messages"][]=$msg;
  }
}

#####################################################################################################

function cycle_active($nick)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  output_map($nick);
  $n=count($players[$nick]["units"]);
  if (isset($players[$nick]["active"])==False)
  {
    $players[$nick]["active"]=0;
  }
  else
  {
    $players[$nick]["active"]=$players[$nick]["active"]+1;
    if ($players[$nick]["active"]>=$n)
    {
      $players[$nick]["active"]=0;
    }
  }
}

#####################################################################################################

?>
