<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~scramble|10|0|0|1|||||php scripts/scramble.php %%trailing%% %%nick%% %%dest%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));

if ($trailing=="")
{
  $index="last_".$nick."_".$dest;
}
else
{
  $index="last_".$trailing."_".$dest;
}

$last=trim(get_bucket($index));

if ($last=="")
{
  return;
}

$parts=explode(" ",$last);

if (count($parts)<4)
{
  return;
}

if (shuffle($parts)==False)
{
  return;
}

privmsg(implode(" ",$parts));

#####################################################################################################

?>
