<?php

#####################################################################################################

/*
exec:~alias|timeout|repeat|0|1|account-list|cmd-list|dest-list|bucket-lock-list|php scripts/blah.php %%trailing%% %%dest%% %%nick%% %%start%% %%alias%% %%cmd%% %%data%% %%params%% %%timestamp%% %%items%%
startup:
init:
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$start=$argv[4];
$alias=$argv[5];
$cmd=$argv[6];
$data=$argv[7];
$params=$argv[8];
$timestamp=$argv[9];
$items=unserialize(base64_decode($argv[10]));

#####################################################################################################

?>
