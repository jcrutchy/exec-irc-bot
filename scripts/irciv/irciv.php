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
}

if ($irciv_data_changed==True)
{
  set_array_bucket($irciv_players,"IRCIV_PLAYERS");
  set_array_bucket($irciv_channels,"IRCIV_CHANNELS");
}

#####################################################################################################

function civ_event_join()
{
  global $parts;
  global $irciv_players;
  global $irciv_channels;
  global $irciv_data_changed;
  if (count($parts)<>2)
  {
    return;
  }
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  irciv_term_echo("civ_event_join: nick=$nick, channel=$channel");
  if (isset($irciv_channels[$channel])==False)
  {
    return;
  }
  $account=users_get_account($nick);
  if ($account<>"")
  {
    $irciv_players[$nick]["account"]=$account;
    $irciv_players[$nick]["channels"][$channel]="";
    $irciv_data_changed=True;
  }
}

#####################################################################################################

function civ_event_kick()
{
  global $parts;
  global $irciv_players;
  global $irciv_data_changed;
  if (count($parts)<>2)
  {
    return;
  }
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  irciv_term_echo("civ_event_kick: nick=$nick, channel=$channel");
  if (isset($irciv_players[$nick]["channels"][$channel])==True)
  {
    unset($irciv_players[$nick]["channels"][$channel]);
    $irciv_data_changed=True;
  }
}

#####################################################################################################

function civ_event_nick()
{
  global $parts;
  global $irciv_players;
  global $irciv_data_changed;
  if (count($parts)<>2)
  {
    return;
  }
  $old_nick=strtolower($parts[0]);
  $new_nick=strtolower($parts[1]);
  irciv_term_echo("civ_event_nick: old_nick=$old_nick, new_nick=$new_nick");
  if (isset($irciv_players[$old_nick]["account"])==True)
  {
    $old_account=$irciv_players[$old_nick]["account"];
    $new_account=users_get_account($new_nick);
    if ($old_account==$new_account)
    {
      $irciv_players[$new_nick]=$irciv_players[$old_nick];
      unset($irciv_players[$old_nick]);
      $irciv_data_changed=True;
    }
  }
}

#####################################################################################################

function civ_event_part()
{
  global $parts;
  global $irciv_players;
  global $irciv_data_changed;
  if (count($parts)<>2)
  {
    return;
  }
  $nick=strtolower($parts[0]);
  $channel=strtolower($parts[1]);
  irciv_term_echo("civ_event_part: nick=$nick, channel=$channel");
  if (isset($irciv_players[$nick]["channels"][$channel])==True)
  {
    unset($irciv_players[$nick]["channels"][$channel]);
    $irciv_data_changed=True;
  }
}

#####################################################################################################

function civ_event_quit()
{
  global $trailing;
  global $irciv_players;
  global $irciv_data_changed;
  $nick=strtolower($trailing);
  irciv_term_echo("civ_event_quit: nick=$nick");
  if (isset($irciv_players[$nick])==True)
  {
    unset($irciv_players[$nick]);
    $irciv_data_changed=True;
  }
}

#####################################################################################################

function is_gm()
{
  global $nick;
  global $gm_accounts;
  $account=users_get_account($nick);
  if (in_array($account,$gm_accounts)==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function register_channel()
{
  global $trailing;
  global $dest;
  global $irciv_channels;
  global $irciv_data_changed;
  $channel="";
  if ($trailing<>"")
  {
    $channel=strtolower($trailing);
  }
  elseif ($dest<>"")
  {
    $channel=strtolower($dest);
  }
  if ($channel=="")
  {
    irciv_term_echo("register_channel: channel not specified");
    return;
  }
  if (isset($irciv_channels[$channel])==True)
  {
    unset($irciv_channels[$channel]);
  }
  # TODO: clear all player data for this channel
  $map_data=generate_map_data();
  $irciv_channels[$channel]=$map_data;
  $irciv_data_changed=True;
  $msg="registered and generated map for channel $channel";
  if ($trailing<>"")
  {
    irciv_privmsg_dest($trailing,$msg);
  }
  if (($dest<>"") and ($dest<>$trailing))
  {
    irciv_privmsg_dest($dest,$msg);
  }
}

#####################################################################################################

function generate_map_data()
{
  $cols=128;
  $rows=64;
  $landmass_count=50;
  $landmass_size=80;
  $land_spread=100;
  if (($landmass_count*$landmass_size)>=(0.8*$cols*$rows))
  {
    irciv_privmsg("landmass parameter error in generating map");
    return;
  }
  $coords=map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,TERRAIN_OCEAN,TERRAIN_LAND);
  $data=array();
  $data["cols"]=$cols;
  $data["rows"]=$rows;
  $data["coords"]=$coords;
  return $data;
}

#####################################################################################################

?>
