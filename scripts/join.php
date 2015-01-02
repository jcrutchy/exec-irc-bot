<?php

#####################################################################################################

/*
exec:~invite|5|0|0|0|||||php scripts/join.php %%trailing%%
exec:~join|5|0|0|0|||||php scripts/join.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
$parts=explode(",",$argv[1]);
$prefixes="#&";
for ($i=0;$i<count($parts);$i++)
{
  $parts[$i]=trim($parts[$i]);
  if (strpos($prefixes,substr($parts[$i],0,1))===False)
  {
    return;
  }
}
$chans=trim(implode(",",$parts));
if ($chans<>"")
{
  echo "/IRC JOIN $chans\n";
}
else
{
  privmsg("syntax: ~join <channel>[,<channel>[,<channel>]]");
}

#####################################################################################################

?>
