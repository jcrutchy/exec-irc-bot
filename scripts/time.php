<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

require_once("lib.php");
require_once("time_lib.php");
require_once("weather_lib.php");
date_default_timezone_set("UTC");

$alias=$argv[1];
$trailing=$argv[2];
switch ($alias)
{
  case "~time-add":
    set_location_alias($alias,$trailing);
    break;
  case "~time":
    $location=trim($argv[2]);
    if ($location<>"")
    {
      $loc=get_location($location);
      if ($loc===False)
      {
        $loc=$location;
      }
      term_echo("*** TIME LOCATION: $loc");
      $result=get_time($loc);
      if ($result<>"")
      {
        #privmsg($result);
        $arr=convert_google_location_time($result);
        privmsg(date("l, j F Y @ g:i a",$arr["timestamp"])." ".$arr["timezone"]." - ".$arr["location"]);
      }
      else
      {
        privmsg("location not found - UTC timestamp: ".date("l, j F Y, g:i a"));
      }
    }
    else
    {
      privmsg("syntax: ~time location");
      privmsg("time data courtesy of Google");
    }
    break;
}

#####################################################################################################

?>
