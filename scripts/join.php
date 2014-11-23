<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~invite|5|0|0|0||||0|php scripts/join.php %%trailing%%
exec:~join|5|0|0|0||||0|php scripts/join.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");

$channel=trim($argv[1]);

$parts=explode(",",$channel);
if (in_array("0",$parts)==True)
{
  return;
}

if (($channel<>"") and ($channel<>"0"))
{
  echo "/IRC JOIN $channel\n";
}
else
{
  privmsg("syntax: ~join <channel>");
}

#####################################################################################################

?>
