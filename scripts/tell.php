<?php

#####################################################################################################

/*
exec:~tell|10|0|0|1|*||||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~tell-internal|10|0|0|1|*|INTERNAL|||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
init:~tell-internal register-events
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$server=$argv[5];

return;

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~tell-internal %%trailing%%");
  return;
}

if ($alias=="~tell")
{
  $msg="";
  append_array_bucket("TELL_MESSAGES_".$server."_".$nick,$msg);
  return;
}

$messages=get_array_bucket("TELL_MESSAGES_".$server."_".$nick);



#####################################################################################################

?>
