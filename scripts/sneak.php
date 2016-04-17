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

*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
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

$fn=DATA_PATH."sneak_data_$serv.txt";
if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}
else
{
  $data=array();
  $data["goodies"]=array();
  $data["players"]=array();
  $data["map"]=array();
  
  $save=True;
}

$save=False;



if ($save==True)
{
  if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
  {
    privmsg("error writing data file");
  }
}

#####################################################################################################

?>
