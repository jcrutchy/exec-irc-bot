<?php

#####################################################################################################

/*
exec:~civ|300|0|0|1|||||php scripts/irciv/irciv.php %%nick%% %%trailing%% %%dest%% %%start%% %%alias%% %%cmd%%
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

irc_pause();

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
    if (is_gm()==True)
    {
      /*foreach ($player_data as $account => $data)
      {
        $n=count($player_data[$account]["units"]);
        for ($i=0;$i<$n;$i++)
        {
          $player_data[$account]["units"][$i]["movement"]=$unit_movement[$player_data[$account]["units"][$i]["type"]];
        }
      }*/
      $irciv_data_changed=True;
    }
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
    break;
  case "load-data":
    if ((is_gm()==True) or ($cmd=="INTERNAL"))
    {
      irciv_load_data();
    }
    break;
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
    break;
  case "help":
  case "?":
    if (count($parts)==0)
    {
      output_help();
    }
    break;
  case "player-list":
    if ($trailing=="")
    {
      $n=count($player_data);
      if ($n==0)
      {
        irciv_privmsg("no players registered");
        break;
      }
      $i=0;
      foreach ($player_data as $player => $data)
      {
        $msg="[".$data["player_id"]."] ".$player;
        if ($i==($n-1))
        {
          irciv_privmsg("  └─ $msg");
        }
        else
        {
          irciv_privmsg("  ├─ $msg");
        }
        $i++;
      }
    }
    break;
  case "player-unset":
    if ((is_gm()==True) and ($trailing<>""))
    {
      if (isset($player_data[$trailing])==True)
      {
        unset($player_data[$trailing]);
        irciv_privmsg("admin: unset \"$trailing\"");
        $irciv_data_changed=True;
      }
      else
      {
        irciv_privmsg("admin: player \"$trailing\" not found");
      }
    }
    break;
  case "player-data":
    if (is_gm()==True)
    {
      if ($trailing<>"")
      {
        if (isset($player_data[$trailing])==True)
        {
          var_dump($player_data[$trailing]);
        }
        else
        {
          irciv_privmsg("player \"$trailing\" not found");
        }
      }
      else
      {
        var_dump($player_data);
      }
    }
    break;
  case "move-unit":
    if (is_gm()==True)
    {
      if (count($parts)==4)
      {
        $player=$parts[0];
        $index=$parts[1];
        $x=$parts[2];
        $y=$parts[3];
        if (isset($player_data[$player]["units"][$index])==True)
        {
          $player_data[$player]["units"][$index]["x"]=$x;
          $player_data[$player]["units"][$index]["y"]=$y;
          unfog($player,$x,$y,$player_data[$player]["units"][$index]["sight_range"]);
          $irciv_data_changed=True;
          update_other_players($player,$index);
          output_map($player);
          status($player);
        }
        else
        {
          irciv_privmsg("players[$player][units][$index] not found");
        }
      }
      else
      {
        irciv_privmsg("syntax: [~civ] move-unit <account> <index> <x> <y>");
      }
    }
    break;
  case "object-edit":
    if (is_gm()==True)
    {
      if (count($parts)>=4)
      {
        $player=$parts[0];
        $array=$parts[1];
        $index=$parts[2];
        $key=$parts[3];
        for ($i=1;$i<=4;$i++)
        {
          array_shift($parts);
        }
        $value=implode(" ",$parts);
        if ($key<>"")
        {
          if (isset($player_data[$player][$array][$index])==True)
          {
            if ($value=="<unset>")
            {
              unset($player_data[$player][$array][$index][$key]);
              irciv_privmsg("players[$player][$array][$index][$key] unset");
            }
            else
            {
              $player_data[$player][$array][$index][$key]=$value;
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
        irciv_privmsg("syntax: [~civ] object-edit <account> <array> <index> <key> [<value>|\"<unset>\"]");
      }
    }
    break;
  case "player-edit":
    if (is_gm()==True)
    {
      if (count($parts)>=2)
      {
        $player=$parts[0];
        $key=$parts[1];
        for ($i=1;$i<=2;$i++)
        {
          array_shift($parts);
        }
        $value=implode(" ",$parts);
        if ($key<>"")
        {
          if (isset($player_data[$player])==True)
          {
            if ($value=="<unset>")
            {
              unset($player_data[$player][$key]);
              irciv_privmsg("key \"$key\" unset for player \"$player\"");
            }
            else
            {
              $player_data[$player][$key]=$value;
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
    if ($trailing=="")
    {
      if (player_init($account)==True)
      {
        $irciv_data_changed=True;
        irciv_privmsg("player \"$account\" has been initialized");
      }
    }
    else
    {
      irciv_privmsg("syntax: [~civ] init");
    }
    break;
  case "u":
  case "up":
    if ($trailing=="")
    {
      move_active_unit($account,0);
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (up|u)");
    }
    break;
  case "r":
  case "right":
    if ($trailing=="")
    {
      move_active_unit($account,1);
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (right|r)");
    }
    break;
  case "d":
  case "down":
    if ($trailing=="")
    {
      move_active_unit($account,2);
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (down|d)");
    }
    break;
  case "l":
  case "left":
    if ($trailing=="")
    {
      move_active_unit($account,3);
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (left|l)");
    }
    break;
  case "b":
  case "build":
    if ($trailing<>"")
    {
      build_city($account,$trailing);
      $irciv_data_changed=True;
    }
    else
    {
      irciv_privmsg("syntax: [~civ] (build|b) City Name");
    }
    break;
  case "status":
    if (output_map($account)==True)
    {
      status($account,trim($trailing));
    }
    break;
  case "set":
    if (player_ready($account)==False)
    {
      break;
    }
    if ($trailing<>"")
    {
      $pair=explode("=",$trailing);
      if (count($pair)==2)
      {
        $key=$pair[0];
        $value=$pair[1];
        $player_data[$account]["settings"][$key]=$value;
        $irciv_data_changed=True;
        irciv_privmsg("key \"$key\" set to value \"$value\" for player \"$account\"");
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
    if (player_ready($account)==False)
    {
      break;
    }
    if ($trailing<>"")
    {
      $key=$trailing;
      if (isset($player_data[$account]["settings"][$key])==True)
      {
        unset($player_data[$account]["settings"][$key]);
        $irciv_data_changed=True;
        irciv_privmsg("key \"$key\" unset for player \"$account\"");
      }
      else
      {
        irciv_privmsg("setting \"$key\" not found for player \"$account\"");
      }
    }
    else
    {
      irciv_privmsg("syntax: [~civ] unset key");
    }
    break;
  case "flag":
    if (player_ready($account)==False)
    {
      break;
    }
    if ($trailing<>"")
    {
      $name=$trailing;
      $player_data[$account]["flags"][$name]="";
      $irciv_data_changed=True;
      irciv_privmsg("flag \"$name\" set for player \"$account\"");
    }
    else
    {
      irciv_privmsg("syntax: [~civ] flag name");
    }
    break;
  case "unflag":
    if (player_ready($account)==False)
    {
      break;
    }
    if ($trailing<>"")
    {
      $name=$trailing;
      if (isset($player_data[$account]["flags"][$name])==True)
      {
        unset($player_data[$account]["flags"][$name]);
        $irciv_data_changed=True;
        irciv_privmsg("flag \"$name\" unset for player \"$account\"");
      }
      else
      {
        irciv_privmsg("flag \"$name\" not set for player \"$account\"");
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

irc_unpause();

#####################################################################################################

?>
