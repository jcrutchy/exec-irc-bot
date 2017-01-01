<?php

#####################################################################################################

/*
exec:~ny|4|5|0|1|||||php scripts/ny.php
*/

#####################################################################################################

require_once("lib.php");
require_once("rainbow_lib.php");
date_default_timezone_set("UTC");
$channel="##freenode-newyears";
$newyear=strtotime("1 January 2017");
$hr=60*60;
$gmt=time();
$diff=$gmt-$newyear;
$tz=-$diff/$hr;
$tz_hr=floor($tz);
if (($tz_hr<-12) or ($tz_hr>12))
{
  return;
}
$min=round(abs($tz-$tz_hr)*60);
$sec=round(abs($tz-$tz_hr)*$hr);
$mod=$sec%60;
term_echo("gmt=".date("h:i:s",$gmt)." >> mod=".$mod.":min=".$min.":sec=".$sec.":tz_hr=".$tz_hr);
if (($min<10) and ($mod<5) and ($sec>5))
{
  $plural="";
  if ($min>1)
  {
    $plural="s";
  }
  pm($channel,chr(3)."06".$min." minute".$plural." till new year for timezone GMT".$tz_hr);
}
if (($sec<30) and ($sec>5))
{
  pm($channel,chr(3)."04".$sec." seconds till new year for timezone GMT".$tz_hr);
}
if ($sec<=5)
{
  pm($channel,rainbowize("HAPPY NEW YEAR")." FOR TIMEZONE GMT".$tz_hr." !!!");
}

#####################################################################################################
