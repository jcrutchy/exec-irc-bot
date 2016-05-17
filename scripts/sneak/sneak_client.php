<?php

#####################################################################################################

/*
exec:add ~sneak
exec:edit ~sneak cmd php scripts/sneak/sneak_client.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~sneak
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

*/

#####################################################################################################

error_reporting(E_ALL);
ob_implicit_flush();
date_default_timezone_set("UTC");

define("APP_NAME","sneak");

require_once("data_client.php");

#####################################################################################################

?>
