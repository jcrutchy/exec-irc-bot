<?php

# gpl2
# by crutchy
# 25-july-2014

require_once("lib.php");

$uptime=microtime(True)-$argv[1];
privmsg("uptime: ".secsToTime($uptime));

function secsToTime($secs) # by chromas, 17-april-2014
{
  $ss=$secs;
  $dd=floor($secs/86400);
  $secs=$secs%86400;
  $hh=floor($secs/3600);
  $secs=$secs%3600;
  $mm=floor($secs/60);
  $secs=$secs%60;
  return $dd."d ".$hh.":".$mm.":".$secs;
}

?>
