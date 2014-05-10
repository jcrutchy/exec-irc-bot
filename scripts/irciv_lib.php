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

function upload_map_image($nick)
{
#The boundary parameter is set to a number of hyphens plus a random string at the end, but you can set it to anything at all. The problem is, if the boundary string shows up in the request data, it will be treated as a boundary.
#Note: Content-Length should be changed whene the boundary change
/*

    The encapsulation boundary must occur at the beginning of a line, i.e.,
    following a CRLF (Carriage Return-Line Feed)
    The boundary must be followed immediately either by another CRLF and the header fields for
    the next part, or by two CRLFs, in which case there are no header fields for the next part
    (and it is therefore assumed to be of Content-Type text/plain).
    Encapsulation boundaries must not appear within the encapsulations, and must be no longer
    than 70 characters, not counting the two leading hyphens.

Last but not least:

    The encapsulation boundary following the last body part is a distinguished delimiter that
    indicates that no further body parts will follow. Such a delimiter is identical to the previous
    delimiters, with the addition of two more hyphens at the end of the line:

 --gc0p4Jq0M2Yt08jU534c0p-- 

*/
  #map_gif($coords,$filename,$scale);
  /*$fp=fsockopen($host,80);
  if ($fp===False)
  {
    echo "Error connecting to \"$host\".\r\n";
    return;
  }
  fwrite($fp,"GET $uri HTTP/1.0\r\nHost: $host\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;*/
}

#####################################################################################################

?>
