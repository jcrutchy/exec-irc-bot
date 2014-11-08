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

$old_cmds=array("~weather","~time");
for ($i=0;$i<count($old_cmds);$i++)
{
  $old_cmd=$old_cmds[$i];
  $L=strlen($old_cmd);
  if (substr($ltrailing,0,$L)==$old_cmd)
  {
    pm($nick,"the $old_cmd command is no longer working properly so it has been relegated to the dustbin of exec history (for now). someday it may be fixed, but don't hold your breath. if you're interested in tinkering, check out http://sylnt.us/execsrc");
    pm("crutchy","$nick entered old command $old_cmd in $dest");
  }
}

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
