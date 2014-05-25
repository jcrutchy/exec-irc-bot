<?php

# gpl2
# by crutchy
# 24-may-2014

# irciv.php

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

define("MIN_CITY_SPACING",3);

$update_players=False;

$admin_nicks=array("crutchy");

$map_coords="";
$map_data=array();
$players=array();

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];
$start=$argv[4];

if (($trailing=="") or (($dest<>GAME_CHAN) and ($nick<>NICK_EXEC) and ($dest<>NICK_EXEC)))
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
$coords_bucket=irciv_get_bucket("map_coords");
$data_bucket=irciv_get_bucket("map_data");
if (($coords_bucket<>"") and ($data_bucket<>""))
{
  $map_coords=map_unzip($coords_bucket);
  $map_data=unserialize($data_bucket);
}
else
{
  $landmass_count=50;
  $landmass_size=80;
  $land_spread=100;
  if (($landmass_count*$landmass_size)>=(0.8*$map_data["cols"]*$map_data["rows"]))
  {
    irciv_privmsg("landmass parameter error in generating map for channel \"$dest\"");
    return;
  }
  $map_coords=map_generate($map_data,$landmass_count,$landmass_size,$land_spread,TERRAIN_OCEAN,TERRAIN_LAND);
  irciv_privmsg("map coords generated for channel \"$dest\"");
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
/*for ($i=0;$i<count($players[$nick]["cities"]);$i++)
{
  $players[$nick]["cities"][$i]["size"]=1;
}*/
/*foreach ($players as $player => $data)
{
  $players[$player]["color"]="0,0,0";
}
foreach ($players as $player => $data)
{
  set_player_color($player);
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
        $player_id=1;
        if (isset($players[NICK_EXEC]["player_count"])==True)
        {
          $player_id=$players[NICK_EXEC]["player_count"]+1;
        }
        $players[NICK_EXEC]["player_count"]=$player_id;
        $players[$player]["account"]=$account;
        $players[$player]["player_id"]=$player_id;
        player_init($player);
        irciv_privmsg("login: welcome new player \"$player\"");
      }
      else
      {
        irciv_privmsg("login: welcome back \"$player\"");
      }
      $players[$player]["login_time"]=microtime(True);
      $players[$player]["logged_in"]=True;
      $update_players=True;
    }
    break;
  case ACTION_RENAME:
    if ((count($parts)==3) and (($nick==NICK_EXEC) or (in_array($nick,$admin_nicks)==Tue)))
    {
      $old=$parts[1];
      $new=$parts[2];
      if ((isset($players[$old])==True) and (isset($players[$new])==False))
      {
        $player_data=$players[$old];
        $players[$new]=$player_data;
        unset($players[$old]);
        $update_players=True;
        irciv_privmsg("player \"$old\" renamed to \"$new\"");
      }
      else
      {
        if (isset($players[$old])==True)
        {
          irciv_privmsg("error renaming player \"$old\" to \"$new\"");
        }
        else
        {
          #irciv_err("ACTION_RENAME: old nick not found");
        }
      }
    }
    else
    {
      if ($nick<>NICK_EXEC)
      {
        irciv_err("ACTION_RENAME: only exec can perform logins");
      }
      else
      {
        irciv_err("ACTION_RENAME: invalid login message");
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
        irciv_privmsg("logout: player \"$player\" logged out");
      }
      else
      {
        irciv_privmsg("logout: there is no player logged in as \"$player\"");
      }
    }
    break;
  case ACTION_ADMIN_PLAYER_UNSET:
    if ((count($parts)==2) and (in_array($nick,$admin_nicks)==True))
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
  case ACTION_ADMIN_PLAYER_DATA:
    if (in_array($nick,$admin_nicks)==True)
    {
      if (count($parts)==2)
      {
        var_dump($players[$parts[1]]);
      }
      else
      {
        var_dump($players);
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
      irciv_privmsg("syntax: [civ] init");
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
      irciv_privmsg("syntax: [civ] (up|u)");
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
      irciv_privmsg("syntax: [civ] (right|r)");
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
      irciv_privmsg("syntax: [civ] (down|d)");
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
      irciv_privmsg("syntax: [civ] (left|l)");
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
      irciv_privmsg("syntax: [civ] (build|b) City Name");
    }
    break;
  case ACTION_STATUS:
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
        irciv_privmsg("syntax: [civ] set key=value");
      }
    }
    else
    {
      irciv_privmsg("syntax: [civ] set key=value");
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
      irciv_privmsg("syntax: [civ] unset key");
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
      irciv_privmsg("syntax: [civ] flag name");
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
      irciv_privmsg("syntax: [civ] unflag name");
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
  irciv_privmsg("flags: public_status,grid");
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
  $players[$nick]["color"]=set_player_color($nick);
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
      status_msg($nick,GAME_CHAN."/$nick => ".$players[$nick]["status_messages"][$i],$public);
    }
    unset($players[$nick]["status_messages"]);
  }
  status_msg($nick,GAME_CHAN."/$nick => $index/$n, $type, +$health, ($x,$y)",$public);
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
      $players[$nick]["units"][$active]["x"]=$x;
      $players[$nick]["units"][$active]["y"]=$y;
      unfog($nick,$x,$y,$players[$nick]["units"][$active]["sight_range"]);
      $type=$players[$nick]["units"][$active]["type"];
      $players[$nick]["status_messages"][]="successfully moved $type $caption from ($old_x,$old_y) to ($x,$y)";
      $update_players=True;
      update_other_players($nick,$active);
      cycle_active($nick);
    }
    status($nick);
  }
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
    if (($player==$nick) or ($player==NICK_EXEC))
    {
      continue;
    }
    if (player_ready($player)==False)
    {
      continue;
    }
    if (is_fogged($player,$x,$y)==False)
    {
      $players[$player]["status_messages"][]="player \"$nick\" moved a unit into your field of vision";
      $players[$nick]["status_messages"][]="you moved a unit into the field of vision of player \"$player\"";
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
