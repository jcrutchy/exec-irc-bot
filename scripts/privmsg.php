<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~privmsg-internal|5|0|0|1||INTERNAL||0|php scripts/privmsg.php %%trailing%% %%nick%% %%dest%%
startup:~privmsg-internal register-events
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~privmsg-internal %%trailing%%");
  return;
}

if ($dest<>"crutchy")
{
  if ((strpos(strtolower($trailing),"crutchy")!==False) or (strpos(strtolower($trailing),"exec")!==False))
  {
    pm("crutchy","[$dest] <$nick> $trailing");
  }
}

#####################################################################################################

?>
