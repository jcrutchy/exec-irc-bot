<?php

/*
exec:~bucket|5|0|0|1|@||||php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%

exec:alias=~bucket
exec:timeout=5
exec:repeat=0
exec:auto-privmsg=0
exec:empty-trailing-allowed=1
exec:account-list=@
exec:cmd-list=
exec:dest-list=
exec:bucket-lock=
exec:shell-cmd=php scripts/bucket.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:server-list=irc.sylnt.us

*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

if ($trailing=="")
{
  privmsg("GET:   ~bucket <index>");
  privmsg("SET:   ~bucket <index> <data>");
  privmsg("UNSET: ~bucket <index> unset");
  return;
}

$parts=explode(" ",$trailing);

$index=$parts[0];

if (count($parts)==2)
{
  if ($parts[1]=="unset")
  {
    unset_bucket($index);
    if (get_bucket($index)=="")
    {
      privmsg("unset bucket");
    }
    else
    {
      privmsg("error unsetting bucket");
    }
    return;
  }
}

if (count($parts)>=2)
{
  array_shift($parts);
  $data=implode(" ",$parts);
  set_bucket($index,$data);
  if (get_bucket($index)=="")
  {
    privmsg("error setting bucket");
  }
  else
  {
    privmsg("set bucket");
  }
  return;
}

if (count($parts)==1)
{
  $data=get_bucket($index);
  if ($data=="")
  {
    privmsg("bucket not found");
  }
  else
  {
    privmsg($data);
  }
  return;
}

#####################################################################################################

?>
