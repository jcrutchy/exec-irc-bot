<?php

# gpl2
# by crutchy
# 17-april-2014

# 5|0|1|bacon-up|php up.php %%start%%

echo "start=".$argv[1]."\n";
$uptime=microtime(True)-$argv[1];
echo "privmsg bacon up: ".secsToTime($uptime)."\n";

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
