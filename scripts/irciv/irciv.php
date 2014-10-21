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

$gm_accounts=array("crutchy");

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$irciv_data_changed=False;

$irciv_players=get_array_bucket("IRCIV_PLAYERS");
$irciv_channels=get_array_bucket("IRCIV_CHANNELS");

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "register-events":
    if ($cmd=="INTERNAL")
    {
      register_all_events("~civ");
    }
    break;
  case "event-join":
    # trailing = <nick> <channel>
    civ_event_join();
    break;
  case "event-kick":
    # trailing = <channel> <nick>
    civ_event_kick();
    break;
  case "event-nick":
    # trailing = <old-nick> <new-nick>
    civ_event_nick();
    break;
  case "event-part":
    # trailing = <nick> <channel>
    civ_event_part();
    break;
  case "event-quit":
    # trailing = <nick>
    civ_event_quit();
    break;
  case "register-channel":
    if (is_gm()==True)
    {
      register_channel();
    }
    break;
  case ACTION_MAP_GENERATE:
    if ($generated==True)
    {
      irciv_term_echo("map already generated for channel \"$dest\"");
      return;
    }
    $landmass_count=50;
    $landmass_size=80;
    $land_spread=100;
    if (($landmass_count*$landmass_size)>=(0.8*$data["cols"]*$data["rows"]))
    {
      irciv_privmsg("landmass parameter error in generating map for channel \"$dest\"");
      return;
    }
    $coords=map_generate($data,$landmass_count,$landmass_size,$land_spread,TERRAIN_OCEAN,TERRAIN_LAND);
    irciv_privmsg("map coords generated for channel \"$dest\"");
    break;
  case ACTION_MAP_DUMP:
    map_dump($coords,$data,$dest);
    return;
  case ACTION_MAP_IMAGE:
    map_img($coords,$data,$dest,"","","png");
    irciv_privmsg("saved map image file to \"$dest.png\"");
    return;
  case ACTION_SAVE_DATA:
    irciv_save_data();
    return;
  case "help":
  case "?":
    if (count($parts)==1)
    {
      output_help();
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

if ($irciv_data_changed==True)
{
  set_array_bucket($irciv_players,"IRCIV_PLAYERS");
  set_array_bucket($irciv_channels,"IRCIV_CHANNELS");
}

#####################################################################################################

?>
