<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~say|10|0|0|1|crutchy,chromas||||php scripts/say.php %%trailing%% %%nick%%
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$parts=explode(" ",$trailing);
$dest=substr(strtolower($parts[0]),1,strlen($parts[0])-2);
$ischan=False;
$isnick=False;
$delim1=substr($parts[0],0,1);
$delim2=substr($parts[0],strlen($parts[0])-1);
if (($delim1=="<") and ($delim2==">"))
{
  $ischan=users_chan_exists($dest);
  $isnick=users_nick_exists($dest);
}
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
