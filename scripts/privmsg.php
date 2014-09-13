<?php

# gpl2
# by crutchy
# 10-sep-2014

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];

$ltrailing=strtolower($trailing);

$responses=array(
  "baysplosion"=>"http://www.youtube.com/watch?feature=player_detailpage&v=v7ssUivM-eM#t=5",
  "i like trains"=>"http://www.youtube.com/watch?v=5DjOL2we8ko",
  "sammich"=>"http://www.youtube.com/watch?v=BEGWDuvNLKo");

foreach ($responses as $trigger => $response)
{
  if (strpos($ltrailing,$trigger)!==False)
  {
    privmsg($response);
  }
}

#####################################################################################################

?>
