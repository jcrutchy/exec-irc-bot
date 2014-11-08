<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~privmsg-internal|5|0|0|1||INTERNAL||0|php scripts/privmsg.php %%trailing%% %%nick%% %%dest%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];

$ltrailing=strtolower($trailing);

$responses=array(
  "i like trains"=>"http://www.youtube.com/watch?v=5DjOL2we8ko",
  "sammich"=>"http://www.youtube.com/watch?v=BEGWDuvNLKo",
  "goat"=>"https://www.youtube.com/watch?v=t8JOboMVhyM");

foreach ($responses as $trigger => $response)
{
  if (strpos($ltrailing,$trigger)!==False)
  {
    privmsg($response);
  }
}

#####################################################################################################

?>
