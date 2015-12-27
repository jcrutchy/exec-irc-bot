<?php

#####################################################################################################

/*
exec:~exec-irc-raw|5|0|0|1|@||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~op|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~deop|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~voice|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~devoice|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~invite|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~kick|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~topic|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~mode|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~lockdown|5|0|0|1|+||||php scripts/admin.php %%trailing%% %%dest%% %%nick%% %%alias%%
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
  case "~exec-irc-raw":
    rawmsg($trailing);
    break;
  case "~op":
    rawmsg("MODE $dest +o $target");
    break;
  case "~deop":
    if ($target<>get_bot_nick())
    {
      rawmsg("MODE $dest -o $target");
    }
    break;
  case "~voice":
    rawmsg("MODE $dest +v $target");
    break;
  case "~devoice":
    if ($target<>get_bot_nick())
    {
      rawmsg("MODE $dest -v $target");
    }
    break;
  case "~invite":
    if ($trailing<>"")
    {
      rawmsg("INVITE $trailing :$dest");
    }
    break;
  /*case ".kick":
    if (($target==$nick) and ($target<>get_bot_nick()))
    {
      rawmsg("KICK $dest $target :$nick kicked self");
    }
    break;*/
  case "~kick":
    if (($target<>$nick) and ($target<>get_bot_nick()))
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
  case "~mode":
    if ($trailing<>"")
    {
      rawmsg("MODE $dest $trailing");
    }
    break;
  case "~lockdown":
    rawmsg("MODE $dest +ntipm");
    break;
}

#####################################################################################################

?>
