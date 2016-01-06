<?php

/*
exec:~cat|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~get|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~set|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~unset|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~rd|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~ls|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~cd|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~md|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~mkdir|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~rmdir|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~execfs|20|0|0|1|||||php scripts/execfs.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

# TODO: WEB PAGE VIEWER FOR FILESYSTEM STRUCTURE

ini_set("display_errors","on");

require_once("lib.php");
require_once("execfs_lib.php");

$trailing=trim($argv[1]);
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$fs=get_fs();
$privmsg=True;

switch ($alias)
{
  case "~execfs":
    /*if ($trailing=="sync")
    {
      $data=get_bucket(BUCKET_FS);
      $fp=fsockopen("irciv.us.to",80);
      if ($fp===False)
      {
        privmsg("  execfs sync error: unable to connect to remote host");
      }
      else
      {
        fwrite($fp,$request);
        $response="";
        while (!feof($fp))
        {
          $response=$response.fgets($fp,1024);
        }
       fclose($fp);
      }
    }*/
    var_dump($fs);
    break;
  case "~cat":
  case "~get":
    # ~get [%path%]%name%
    execfs_get($nick,$trailing);
    break;
  case "~set":
    # ~set [%path%]%name% = %value%
    $parts=explode("=",$trailing);
    if (count($parts)>=2)
    {
      $name=trim($parts[0]);
      array_shift($parts);
      $value=trim(implode("=",$parts));
      if ($name<>"")
      {
        execfs_set($nick,$name,$value);
        break;
      }
    }
    privmsg("syntax: ~set [%path%]%name% = %value%");
    break;
  case "~unset":
    # ~unset [%path%]%name%
    execfs_unset($nick,$trailing);
    break;
  case "~rmdir":
  case "~rd":
    # ~rd %child%
    execfs_rd($nick,$trailing);
    break;
  case "~ls":
    # ~ls %path%
    execfs_ls($nick,$trailing);
    break;
  case "~cd":
    # ~cd %path%
    execfs_cd($nick,$trailing);
    break;
  case "~mkdir":
  case "~md":
    # ~md %path%
    execfs_md($nick,$trailing);
    break;
}

set_fs();

#####################################################################################################

?>
