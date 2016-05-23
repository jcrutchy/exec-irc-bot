<?php

#####################################################################################################

/*
exec:add ~sneak
exec:edit ~sneak cmd php scripts/sneak/sneak_client.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~sneak
*/

# sneak is an irc game where each player aims to increase their kills by moving into the same coordinate as other players

#####################################################################################################

error_reporting(E_ALL);
ob_implicit_flush();
date_default_timezone_set("UTC");

define("APP_NAME","sneak");

require_once("data_client.php");

#####################################################################################################

?>
