<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~civ|300|0|0|1||||0|php scripts/irciv/irciv.php %%nick%% %%trailing%% %%dest%% %%start%% %%alias%% %%cmd%%
init:~civ load-data
startup:~join #civ
*/

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

$account=users_get_account($nick);

$gm_accounts=array("crutchy");

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$game_chans=get_game_list();
$player_data=array();
$map_data=array();
$game_data=array();
if ($dest<>"")
{
  $game_data=get_array_bucket(GAME_BUCKET_PREFIX.$dest);
  $player_data=&$game_data["players"];
  $map_data=&$game_data["map"];
}
$irciv_data_changed=False;

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "dev-op":
    var_dump($player_data);
    break;
  case "register-channel":
    if (is_gm()==True)
    {
      register_channel();
    }
    break;
  case "save-data":
    if (is_gm()==True)
    {
      irciv_save_data();
    }
    return;
  case "load-data":
    if (is_gm()==True)
    {
      irciv_load_data();
    }
    return;
  case "game-list":
    $game_chans=get_game_list();
    $n=count($game_chans);
    if ($n==0)
    {
      irciv_privmsg("no irciv games registered");
    }
    else
    {
      irciv_privmsg("registered games:");
      $i=0;
      foreach ($game_chans as $channel => $bucket)
      {
        if ($i==($n-1))
        {
          irciv_privmsg("  └─ $channel");
        }
        else
        {
          irciv_privmsg("  ├─ $channel");
        }
        $i++;
      }
    }
    return;
  case "help":
  case "?":
    if (count($parts)==0)
    {
      output_help();
    }
    break;
  case "player-unset":
    if (is_gm()==True)
    {
      if (isset($irciv_player_data[$trailing])==True)
      {
        unset($irciv_player_data[$trailing]);
        irciv_privmsg("admin: unset \"$trailing\"");
        $irciv_data_changed=True;
      }
      else
      {
        irciv_privmsg("admin: player \"$trailing\" not found");
      }
    }
    break;
  case "player-list":
    if ((count($parts)==1) and ($alias==$admin_alias))
    {
      foreach ($players as $player => $data)
      {
        irciv_privmsg("[".$data["player_id"]."] ".$player);
      }
    }
    break;
  case "player-data":
    if (is_gm()==True)
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
  case "move-unit":
    if (is_gm()==True)
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
          $irciv_data_changed=True;
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
  case "object-edit":
    if (is_gm()==True)
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
            $irciv_data_changed=True;
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
  case "player-edit":
    if (is_gm()==True)
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
            $irciv_data_changed=True;
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
  case "init":
    if (count($parts)==0)
    {
      if (player_init($account)==True)
      {
        $irciv_data_changed=True;
        irciv_privmsg("player \"$account\" has been initialized");
      }
      else
      {
        irciv_privmsg("error initializing player \"$account\"");
      }
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
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (build|b) City Name");
    }
    break;
  case "status":
    output_map($nick);
    status($nick);
    break;
  case "set":
    if (count($parts)==2)
    {
      $pair=explode("=",$parts[1]);
      if (count($pair)==2)
      {
        $key=$pair[0];
        $value=$pair[1];
        $players[$nick]["settings"][$key]=$value;
        $irciv_data_changed=True;
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
  case "unset":
    if (count($parts)==2)
    {
      $key=$parts[1];
      if (isset($players[$nick]["settings"][$key])==True)
      {
        unset($players[$nick]["settings"][$key]);
        $irciv_data_changed=True;
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
  case "flag":
    if (count($parts)==2)
    {
      $flag=$parts[1];
      $players[$nick]["flags"][$flag]="";
      $irciv_data_changed=True;
      irciv_privmsg("flag \"$flag\" set for player \"$nick\"");
    }
    else
    {
      irciv_privmsg("syntax: [~civ] flag name");
    }
    break;
  case "unflag":
    if (count($parts)==2)
    {
      $flag=$parts[1];
      if (isset($players[$nick]["flags"][$flag])==True)
      {
        unset($players[$nick]["flags"][$flag]);
        $irciv_data_changed=True;
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

if (($dest<>"") and ($irciv_data_changed==True))
{
  set_array_bucket($game_data,GAME_BUCKET_PREFIX.$dest);
}

#####################################################################################################

?>
