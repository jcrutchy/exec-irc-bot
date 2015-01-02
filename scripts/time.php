<?php

#####################################################################################################

/*
exec:~time|60|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
exec:~time-add|10|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
exec:~time-del|10|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");
require_once("time_lib.php");
require_once("weather_lib.php");
date_default_timezone_set("UTC");

$alias=$argv[1];
$trailing=trim($argv[2]);
$nick=strtolower(trim($argv[3]));

switch ($alias)
{
  case "~time-add":
    set_location_alias($alias,$trailing);
    break;
  case "~time-del":
    if (del_location($trailing)==True)
    {
      privmsg("location \"$trailing\" deleted");
    }
    else
    {
      if (trim($trailing)<>"")
      {
        privmsg("location for \"$trailing\" not found");
      }
      else
      {
        privmsg("syntax: ~time-del <name>");
      }
    }
    break;
  case "~time":
    $loc=get_location($trailing,$nick);
    if ($loc===False)
    {
      if ($trailing=="")
      {
        privmsg("syntax: ~time location");
        privmsg("time data courtesy of Google");
        return;
      }
      $loc=$trailing;
    }
    term_echo("*** TIME LOCATION: $loc");
    $result=get_time($loc);
    if ($result<>"")
    {
      $arr=convert_google_location_time($result);
      #privmsg($result);
      privmsg(date("l, j F Y @ g:i a",$arr["timestamp"])." ".$arr["timezone"]." - ".$arr["location"]);
    }
    else
    {
      privmsg("location not found - UTC timestamp: ".date("l, j F Y, g:i a"));
    }
    break;
}

#####################################################################################################

?>
