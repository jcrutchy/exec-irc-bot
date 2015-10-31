<?php

#####################################################################################################

/*
exec:~google|20|0|0|1|||||php scripts/google.php %%trailing%%
exec:~g|20|0|0|1|||||php scripts/google.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
require_once("google_lib.php");

$trailing=trim($argv[1]);

if ($trailing=="")
{
  return;
}

$results=google_search($trailing);

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
