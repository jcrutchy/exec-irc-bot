<?php

#####################################################################################################
################################### ROCK / PAPER / SCISSORS GAME ####################################
#####################################################################################################

/*
exec:~rps|10|0|0|1|*|PRIVMSG|||php scripts/rps.php %%trailing%% %%dest%% %%nick%% %%alias%% %%params%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$params=$argv[5];

switch ($trailing)
{
  case "r":
  case "p":
  case "s":
    $account=users_get_account($nick);

    break;
  case "ranks":
    output_ixio_paste("my cat's breath smells like cat food");
    break;
  default:
    privmsg("syntax: ~rps [ranks|r|p|s]");
}

#####################################################################################################

?>
