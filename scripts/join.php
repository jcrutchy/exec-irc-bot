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
$parts=explode(",",$argv[1]);
$prefixes="#&";
for ($i=0;$i<count($parts);$i++)
{
  $chan=trim($parts[$i]);
  if (strpos($prefixes,substr($chan,0,1))===False)
  {
    return;
  }
}
$channel=implode(",",$parts);
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
