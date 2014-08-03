<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

require_once("lib.php");
require_once("time_lib.php");
require_once("weather_lib.php");
date_default_timezone_set("UTC");
$location=$argv[1];
$loc=get_location($location);
if ($loc===False)
{
  $loc=$location;
}
term_echo("*** TIME LOCATION: $loc");
$result=get_time($loc);
if ($result<>"")
{
  privmsg("[Google] ".$result);
}
else
{
  privmsg("location not found - UTC timestamp: ".date("l, j F Y, h:i:s a"));
}

#####################################################################################################

?>
