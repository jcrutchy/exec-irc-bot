<?php

#####################################################################################################

/*
exec:add ~mud
exec:edit ~mud timeout 30
exec:edit ~mud cmd php scripts/mud/mud.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~mud
*/

#####################################################################################################

error_reporting(E_ALL);
date_default_timezone_set("UTC");

require_once(__DIR__."/../lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

#####################################################################################################

?>
