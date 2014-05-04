<?php

# gpl2
# by crutchy
# 02-may-2014

# irciv_lib.php

require_once("lib.php");

define("GAME_NAME","IRCiv");
define("GAME_CHAN","#civ");
define("BUCKET_PREFIX",GAME_NAME."_".GAME_CHAN."_");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

#####################################################################################################

function irciv_term_echo($msg)
{
  term_echo(GAME_NAME.": $msg");
}

#####################################################################################################

function irciv_privmsg($msg)
{
  privmsg(GAME_NAME.": $msg");
}

#####################################################################################################

function irciv_err($msg)
{
  err(GAME_NAME." error: $msg");
}

#####################################################################################################

function irciv_get_bucket($suffix)
{
  return get_bucket(BUCKET_PREFIX.$suffix);
}

#####################################################################################################

function irciv_set_bucket($suffix,$data)
{
  set_bucket(BUCKET_PREFIX.$suffix,$data);
}

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function map_zip($coords)
{
  # replace consecutive characters with one character followed by the number of repetitions
  # or maybe use gzcompress but escape the control characters (prolly easier)
  return $coords;
}

#####################################################################################################

function map_unzip($coords)
{
  return $coords;
}

#####################################################################################################

?>
