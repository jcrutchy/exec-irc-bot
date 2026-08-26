<?php

#####################################################################################################

/*
#exec:systemctl|10|0|0|1|||||php scripts/systemctl.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];

$parts=explode(" ",$trailing);
$command=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

privmsg(chr(3)."02trying to $command $trailing...");
sleep(3);
privmsg("...".chr(3)."04failed!".chr(3)." - use epoch instead: http://universe2.us/epoch.html");
return;

switch ($command)
{
  case "start":

    break;
  case "stop":

    break;
  case "reload":

    break;
  case "restart":

    break;
}

#####################################################################################################

?>
