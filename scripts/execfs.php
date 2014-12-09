<?php

# gpl2
# by crutchy

/*
exec:~get|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~set|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~cp|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~mv|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~rm|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~ls|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~cd|5|0|0|1|*|||0|php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");
require_once("execfs_lib.php");

$trailing=trim($argv[1]);
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$fs=get_fs();

switch ($alias)
{
  case "~get":
    # ~get [%path%]%name%
    break;
  case "~set":
    # ~set [%path%]%name% = %value%
    execfs_set($nick,$trailing,"");
    break;
  case "~cp":
    # ~cp [%from_path%]%from_name% > %to_path%[%to_name%]
    break;
  case "~mv":
    # ~mv [%from_path%]%from_name% > %to_path%[%to_name%]
    break;
  case "~rm":
    # ~rm [%path%]%name%
    break;
  case "~ls":
    # ~ls %path%
    break;
  case "~cd":
    # ~cd %path%
    break;
}

#####################################################################################################

?>
