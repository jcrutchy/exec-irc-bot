<?php

#####################################################################################################

/*
#exec:add .sneak
#exec:edit .sneak cmd php scripts/sneak.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
#exec:enable .sneak
#startup:~join #sneak
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


LEAVE GAME SCRIPT RUNNING FULL TIME AS A SOCKET SERVER
MAKE ANOTHER SMALLER SCRIPT THAT COMMUNICATES WITH SERVER
(TO PREVENT DATA FILE CORRUPTION)

*/

#####################################################################################################

# move most of the following stuff to the server. the client should mostly just connect to the server, send its message and disconnect

# ONLY SEND STUFF TO THE SERVER THAT AFFECTS THE DATA FILES, LIKE COMMANDS THAT CHANGE DATA AND REQUESTS FOR DATA
# THINGS LIKE PROCESSING ix.io OUTPUT ETC SHOULD BE DONE IN THE CLIENT SCRIPT

# JUST SEND ENTIRE REQUESTS AS BASE64 ENCODED SERIALIZED STRINGS (USE PHP SERIALIZE INSTEAD OF JSON)

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

$socket=fsockopen("127.0.0.1",50000);
if ($socket===False)
{
  privmsg("error connecting to game server");
  return;
}
stream_set_blocking($socket,0);
fputs($socket,$msg."\n");

$id="$nick!$user@$hostname";
$serv=base64_encode($server);

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

#####################################################################################################

?>
