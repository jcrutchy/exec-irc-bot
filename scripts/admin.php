<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~op|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~deop|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~voice|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~devoice|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~kick|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~topic|5|0|0|1|crutchy||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
#exec:.kick|5|0|0|1|||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=trim($argv[1]);
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$target=$nick;
if ($trailing<>"")
{
  $target=$trailing;
}

switch ($alias)
{
  case "~op":
    rawmsg("MODE $dest +o $target");
    break;
  case "~deop":
    if ($target<>NICK_EXEC)
    {
      rawmsg("MODE $dest -o $target");
    }
    break;
  case "~voice":
    rawmsg("MODE $dest +v $target");
    break;
  case "~devoice":
    if ($target<>NICK_EXEC)
    {
      rawmsg("MODE $dest -v $target");
    }
    break;
  /*case ".kick":
    if (($target==$nick) and ($target<>NICK_EXEC))
    {
      rawmsg("KICK $dest $target :$nick kicked self");
    }
    break;*/
  case "~kick":
    if (($target<>$nick) and ($target<>NICK_EXEC))
    {
      rawmsg("KICK $dest $target :commanded by $nick");
    }
    break;
  case "~topic":
    if ($trailing<>"")
    {
      rawmsg("TOPIC $dest :$trailing");
    }
    break;
}

#####################################################################################################

?>
