<?php

# gpl2
# by crutchy
# 8-aug-2014

#####################################################################################################

require_once("lib.php");
require_once("weather_lib.php");

$location=strtolower(trim($argv[1]));
$code=get_location($location);
if (($code===False) or ($code==""))
{
  privmsg("*** location \"$location\" not found");
}
else
{
  privmsg("*** location \"$location\" = \"$code\"");
}

#####################################################################################################

?>
