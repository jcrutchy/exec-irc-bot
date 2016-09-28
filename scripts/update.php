<?php

#####################################################################################################

/*
exec:~update-exec-file|60|0|0|1|+||||php scripts/update.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~delete-exec-file|60|0|0|1|+||||php scripts/update.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$server=$argv[5];

switch ($alias)
{
  case "~update-exec-file":
    if ($trailing=="")
    {
      privmsg("syntax: ~update-exec-file <filename>");
      return;
    }
    privmsg("attempting to download https://raw.githubusercontent.com/crutchy-/exec-irc-bot/master/".$trailing);
    $response=wget("raw.githubusercontent.com","/crutchy-/exec-irc-bot/master/".$trailing,443);
    if ($response=="")
    {
      privmsg("no response from github");
      return;
    }
    $headers=exec_get_headers($response);
    if ($headers===False)
    {
      privmsg("error downloading file (2)");
      return;
    }
    $lines=explode(PHP_EOL,$headers);
    if ((strpos($lines[0],"404")!==False) or (strpos($lines[0],"Error")!==False))
    {
      privmsg("error downloading file (3)");
      return;
    }
    $content=strip_headers($response);
    $outfile=realpath(__DIR__."/../")."/".$trailing;
    if (file_exists($outfile)==False)
    {
      $path=dirname($outfile);
      mkdir($path,0777,True);
    }
    if (file_put_contents($outfile,$content)===False)
    {
      privmsg("error saving file");
    }
    else
    {
      privmsg("successfully saved downloaded content to \"$outfile\"");
    }
    return;
  case "~delete-exec-file":
    $root=realpath(__DIR__."/../")."/";
    $skip=array(".","..",".git");
    $list=array();
    recurse_scandir($root,"",$list,$skip);
    $skip=array("irc.php","irc_lib.php","exec.txt");
    $list=array_diff($list,$skip);
    $list=array_values($list);
    if (in_array($trailing,$list)==False)
    {
      privmsg("error: invalid filename");
      return;
    }
    $delfile=$root.$trailing;
    if (file_exists($delfile)==False)
    {
      privmsg("error: file not found");
      return;
    }
    if (unlink($delfile)==False)
    {
      privmsg("error: unable to delete file");
    }
    else
    {
      privmsg("successfully deleted \"$delfile\"");
    }
    return;
}

#####################################################################################################

function recurse_scandir($root,$path,&$list,$skip)
{
  $local=scandir($root.$path);
  $local=array_diff($local,$skip);
  $local=array_values($local);
  $dirs=array();
  for ($i=0;$i<count($local);$i++)
  {
    if ($path<>"")
    {
      $local[$i]=$path."/".$local[$i];
    }
    if (is_dir($root.$local[$i])==True)
    {
      $dirs[]=$local[$i];
    }
  }
  $list=array_merge($list,$local);
  for ($i=0;$i<count($dirs);$i++)
  {
    recurse_scandir($root,$dirs[$i],$list,$skip);
  }
}

#####################################################################################################

?>
