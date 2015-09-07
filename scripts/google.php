<?php

#####################################################################################################

/*
exec:~google|20|0|0|0|||||php scripts/google.php %%trailing%%
exec:~g|20|0|0|0|||||php scripts/google.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
require_once("google_lib.php");

$results=google_search($argv[1]);

var_dump($results);

if ($results!==False)
{
  if (count($results)>0)
  {
    if (strlen($results[0])>300)
    {
      return;
    }
    privmsg("[google] ".$results[0]);
  }
}

#####################################################################################################

?>
