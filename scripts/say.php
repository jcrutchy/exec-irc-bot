<?php

# gpl2
# by crutchy

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$parts=explode(" ",$trailing);
$dest=strtolower($parts[0]);
$ischan=users_chan_exists($dest);
$isnick=users_nick_exists($dest);

if (($ischan==True) or ($isnick==True))
{
  array_shift($parts);
  $trailing=implode(" ",$parts);
  pm($dest,$trailing);
  pm("crutchy",$trailing);
}
else
{
  privmsg($trailing);
}

#####################################################################################################

?>
