<?php

#####################################################################################################

/*
exec:~stash|10|0|0|1|*||||php scripts/stash.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~unstash|10|0|0|1|*||||php scripts/stash.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$server=$argv[5];

$account=users_get_account($nick);

$fn=DATA_PATH."stash_data_".$server."_".$account;

if (file_exists($fn)==True)
{
  $data=json_decode(file_get_contents($fn),True);
}
else
{
  $data=array();
}

switch ($alias)
{
  case "~stash":
  
    break;
  case "~unstash":
  
    break;
}

if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
{
  privmsg("error writing stash file for $nick");
}

#####################################################################################################

?>
