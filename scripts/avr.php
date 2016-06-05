<?php

#####################################################################################################

/*
exec:add ~avr
exec:edit ~avr timeout 0
exec:edit ~avr dests #crutchy
exec:edit ~avr cmd php scripts/avr.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%% %%server%%
exec:enable ~avr
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];
$server=$argv[6];

if ($trailing=="start")
{
  set_bucket("avr_rs232_enabled","1");
  $previous="";
  while (get_bucket("avr_rs232_enabled")<>"")
  {
    
  }
}

#####################################################################################################

?>
