<?php

#####################################################################################################

/*
exec:~first|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~last|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~random|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~count|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~log|60|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("chromas_log_lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=substr($argv[4],1);

if ($trailing=="debug on")
{
  set_bucket("chromas_irc_log_debug","on");
  privmsg("  enabled debug pm");
  return;
}
elseif ($trailing=="debug off")
{
  unset_bucket("chromas_irc_log_debug");
  privmsg("  disabled debug pm");
  return;
}

$lines=chromas_log($alias,$trailing,$dest);

if ($lines===False)
{
  $response=wget("chromas.0x.no","/s/soylent_log.php",80);
  $html=trim(strip_headers($response));
  $html=str_replace("\n"," ",$html);
  privmsg(chr(3)."03".$html);
  privmsg(chr(3)."  http://chromas.0x.no/s/soylent_log.php");
  return;
}

$cutoff_index=4;
for ($i=0;$i<count($lines);$i++)
{
  if ($i>$cutoff_index)
  {
    $n=count($lines)-$cutoff_index-1;
    privmsg(chr(3)."03$n records not shown - refer to http://chromas.0x.no/s/soylent_log.php");
    break;
  }
  $msg=trim($lines[$i]);
  if ($msg<>"")
  {
    privmsg(chr(3)."03".$msg);
  }
}

#####################################################################################################

?>
