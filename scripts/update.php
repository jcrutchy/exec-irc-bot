<?php

#####################################################################################################

/*
exec:~update-exec-file|60|0|0|1|@||||php scripts/update.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~delete-exec-file|60|0|0|1|@||||php scripts/update.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
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
      privmsg("syntax: ~update-exec-file <filename> (operator only command)");
      return;
    }
    privmsg("attempting to download https://raw.githubusercontent.com/crutchy-/exec-irc-bot/master/".$trailing);
    $response=wget_ssl("raw.githubusercontent.com","/crutchy-/exec-irc-bot/master/".$trailing);
    if ($response=="")
    {
      privmsg("error downloading file (1)");
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
    $outfile=realpath(__DIR__."/../".$trailing);
    if (file_put_contents($outfile,$content)===False)
    {
      privmsg("error downloading file (3)");
    }
    else
    {
      privmsg("successfully saved downloaded content to \"$outfile\"");
    }
    return;
  case "~delete-exec-file":

    return;
}

#####################################################################################################

?>
