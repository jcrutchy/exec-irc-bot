<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~privmsg-internal|5|0|0|1||INTERNAL||0|php scripts/privmsg.php %%trailing%% %%nick%% %%dest%%
init:~privmsg-internal register-events
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

if ($trailing=="!stats")
{
  privmsg("http://stats.sylnt.us/social/soylent/");
  return;
}

$keywords=array(
  "crutchy",
  "exec",
  "irciv");

# TODO: color code "crutchy" lines

$ltrailing=strtolower($trailing);

if ($dest<>"crutchy")
{
  for ($i=0;$i<count($keywords);$i++)
  {
    if (strpos($ltrailing,$keywords[$i])!==False)
    {
      pm("crutchy","[$dest] <$nick> $trailing");
      return;
    }
  }
}

#####################################################################################################

?>
