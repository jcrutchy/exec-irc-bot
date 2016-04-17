<?php

#####################################################################################################

/*
exec:add .sneak
exec:edit .sneak cmd php scripts/sneak.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable .sneak
startup:~join #sneak
*/

#####################################################################################################

/*

sneak
=====

sneak is an irc game where each player aims to increase their kills by moving into the same coordinate as other players

the play area is square and continuous (when a player moves off one edge they appear on the opposite egde)

the size of the play area is dependent on the number of players; when there are lots of players, the size increases
when the number of players decreases and an edge is unoccupoied, the play area is reduced

when a player is killed, their handicap decreases by one. when a player kills another, their handicap increases by one

players are identified by their nick!user@hostname

there are goody boxes randomly dispersed around the play area, that are consumed when occupied and appear randomly at unoccupied coordinates
goody boxes could kill the player, relocate them to a random coordinate (if occupied resulting in a kill for that player), or could indicate the relative location of the nearest player (eg: 2 spaces up, 4 spaces right)

output to players is via pm
channel output to #sneak is only certain events (when someone dies or the map changes)


LEAVE GAME SCRIPT RUNNING FULL TIME AS A SOCKET / PIPE SERVER
MAKE ANOTHER SMALLER SCRIPT THAT COMMUNICATES WITH SERVER
(TO PREVENT DATA FILE CORRUPTION)

*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

$id="$nick!$user@$hostname";
$serv=base64_encode($server);

$save=False;

$fn=DATA_PATH."sneak_data_$serv.txt";
if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}
else
{
  $data=array();
  $save=True;
}

$chan="#sneak";

if (isset($data[$chan])==False)
{
  init_chan($chan);
}

$gm_accounts=array("crutchy");

$parts=explode(" ",$trailing);
if (count($parts)==2)
{
  $chan=array_shift($parts);
  if (isset($data[$chan])==False)
  {
    pm($nick,"sneak: invalid game channel");
    return;
  }
}

if (count($parts)<>1)
{
  pm($nick,"sneak: invalid game command");
  return;
}

if (isset($data["players"][$id])==False)
{
  $data["players"][$id]=array();
  $save=True;
}

switch ($trailing)
{
  case "gm-del-chan":
    if (is_gm($nick)==True)
    {
      if (isset($data[$chan])==True)
      {
        unset($data[$chan]);
        $save=True;
        pm($nick,"sneak: chan deleted");
      }
      else
      {
        pm($nick,"sneak: chan not found");
      }
    }
    break;
  case "gm-init-chan":
    if (is_gm($nick)==True)
    {
      if (isset($data[$chan])==True)
      {
        unset($data[$chan]);
      }
      init_chan($chan);
      pm($nick,"sneak: chan initialized");
    }
    break;
  case "gm-kill":
    if (is_gm($nick)==True)
    {

    }
    break;
  case "gm-player-data":
    if (is_gm($nick)==True)
    {

    }
    break;
  case "gm-map":
    if (is_gm($nick)==True)
    {

    }
    break;
  case "gm-edit-player":
    if (is_gm($nick)==True)
    {

    }
    break;
  case "gm-edit-goody":
    if (is_gm($nick)==True)
    {

    }
    break;
  case "help":
  case "?":

    break;
  case "player-list":

    break;
  case "chan-list":

    break;
  case "start":

    break;
  case "status":

    break;
  case "die":

    break;
  case "rank":

    break;
  case "l":
  case "left":

    break;
  case "r":
  case "right":

    break;
  case "u":
  case "up":

    break;
  case "d":
  case "down":

    break;
}

if ($save==True)
{
  if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
  {
    privmsg("error writing data file");
  }
}

#####################################################################################################

function is_gm($nick)
{
  global $gm_accounts;
  $account=users_get_account($nick);
  if ($account=="")
  {
    return False;
  }
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

function init_chan($chan)
{
  global $save;
  global $data;
  $data[$chan]=array();
  $data[$chan]["players"]=array();
  $data[$chan]["goodies"]=array();
  $data[$chan]["map_size"]=30;
  $save=True;
}

#####################################################################################################

?>
