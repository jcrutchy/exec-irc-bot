<?php

#####################################################################################################

/*
exec:~welcome|10|0|0|0|||||php scripts/welcome.php %%nick%% %%dest%% %%alias%% %%trailing%% %%server%%
exec:~welcome-internal|30|0|0|1||INTERNAL|||php scripts/welcome.php %%nick%% %%dest%% %%alias%% %%trailing%% %%server%%
*/

#####################################################################################################

require_once("lib.php");
require_once("weather_lib.php");
require_once("time_lib.php");
require_once("switches.php");

$nick=$argv[1];
$dest=$argv[2];
$alias=$argv[3];
$trailing=$argv[4];
$server=$argv[5];

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_WELCOME_CHANNELS>>","~welcome","~welcome-internal",$msg);

switch ($flag)
{
  case 1:
    privmsg("welcome enabled for ".chr(3)."10$dest");
    return;
  case 2:
    privmsg("welcome already enabled for ".chr(3)."10$dest");
    return;
  case 3:
    privmsg("welcome disabled for ".chr(3)."10$dest");
    return;
  case 4:
    privmsg("welcome already disabled for ".chr(3)."10$dest");
    return;
  case 9:
    show_welcome($nick,$dest,$server);
    return;
}

#####################################################################################################

function show_welcome($nick,$dest,$server)
{
  $location=get_location($nick);
  if ($location===False)
  {
    return;
  }
  $time=get_time($location);
  if ($time=="")
  {
    return;
  }
  $arr=convert_google_location_time($time);
  $data=process_weather($location,$nick,True);
  if ($data===False)
  {
    return;
  }
  if (($data["tempC"]===False) or ($data["tempF"]===False))
  {
    return;
  }
  # TODO: ADD LAST SEEN TIMESTAMP TO WELCOME MESSAGE (need to work on seen_lib.php)
  privmsg("welcome $nick: ".trim($arr["location"]).", ".$data["tempC"]."/".$data["tempF"].", ".date("g:i a",$arr["timestamp"])." ".$arr["timezone"].", ".date("l, j F Y",$arr["timestamp"]));
}

#####################################################################################################

?>
