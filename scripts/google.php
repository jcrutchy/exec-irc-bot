<?php

#####################################################################################################

/*
exec:~google|20|0|0|0|||||php scripts/google.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
require_once("google_lib.php");

$results=google_search($argv[1]);

privmsg("test");

return;

if ($results!==False)
{
  if (count($results)>0)
  {
    privmsg("[Google] ".$results[0]);
  }
}

#####################################################################################################

?>
