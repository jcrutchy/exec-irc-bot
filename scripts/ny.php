<?php

#####################################################################################################

/*
exec:add ~newyear
exec:edit ~newyear timeout 4
exec:edit ~newyear repeat 5
exec:edit ~newyear auto 0
exec:edit ~newyear empty 1
exec:edit ~newyear cmd php scripts/ny.php
exec:enable ~newyear
*/

#####################################################################################################

$channels=array("##newyear2019","##anime");

require_once("lib.php");
require_once("rainbow_lib.php");
date_default_timezone_set("UTC");
$newyear=strtotime("1 January 2019");
$hr=60*60;
$gmt=time();
$diff=$gmt-$newyear;
$tz=-$diff/$hr;
$tz_hr=floor($tz);
#term_echo("gmt=".date("h:i:s",$gmt)." >> tz_hr=".$tz_hr);
if (($tz_hr<-12) or ($tz_hr>13))
{
  return;
}
$min=round(abs($tz-$tz_hr)*60);
$sec=round(abs($tz-$tz_hr)*$hr);
$mod=$sec%60;
term_echo("gmt=".date("h:i:s",$gmt)." >> mod=".$mod.":min=".$min.":sec=".$sec.":tz_hr=".$tz_hr);
if ($tz_hr>0)
{
  $tz_hr="+".$tz_hr;
}
if (($min<10) and ($mod<5) and ($sec>5))
{
  $plural="";
  if ($min>1)
  {
    $plural="s";
  }
  #broadcast(chr(3)."06".$min." minute".$plural." till new year for timezone GMT".$tz_hr);
}
elseif (($sec<30) and ($sec>5))
{
  #broadcast(chr(3)."04".$sec." seconds till new year for timezone GMT".$tz_hr);
}
elseif ($sec<5)
{
  broadcast(rainbowize("HAPPY NEW YEAR")." FOR TIMEZONE GMT".$tz_hr." !!!");
  #broadcast("♪ ┏(°.°)┛ ┗(°.°)┓ ┗(°.°)┛ ┏(°.°)┓ ♪");
}

#####################################################################################################

function broadcast($msg)
{
  global $channels;
  foreach ($channels as $key => $channel)
  {
    pm($channel,$msg);
  }
}

#####################################################################################################
