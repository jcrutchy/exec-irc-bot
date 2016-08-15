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

privmsg(get_bucket("<<BOT_MEMORY_USAGE>>"));

#####################################################################################################

?>
