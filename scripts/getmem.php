<?php

#####################################################################################################

/*
exec:add ~getmem
exec:edit ~getmem accounts_wildcard +
exec:edit ~getmem cmd php scripts/getmem.php %%trailing%%
exec:enable ~getmem
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);

#privmsg(get_bucket("<<BOT_MEMORY_USAGE>>"));

$output=shell_exec("top -n1 -c -b | grep -m 1 'php irc.php'");
$output=explode(" ",$output);
delete_empty_elements($output);
if (count($output)>=12)
{
  privmsg("RES: ".$output[5]);
}
else
{
  privmsg("something borked");
}

#####################################################################################################

?>
