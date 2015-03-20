<?php

#####################################################################################################

/*
exec:~first|20|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~last|20|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~random|20|0|0|1|||||php scripts/chromas_log.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=substr($argv[4],1);

if ($trailing=="")
{
  $response=wget("chromas.0x.no","/s/soylent_log.php",80);
  $html=trim(strip_headers($response));
  $html=str_replace("\n"," ",$html);
  privmsg(chr(3)."03".$html);
  return;
}

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

$params=parse_parameters($trailing,"="," ");

var_dump($params);

$paramstr="";
foreach ($params as $key => $value)
{
  $paramstr=$paramstr."&".urlencode($key)."=".urlencode($value);
}

if (isset($params["channel"])==False)
{
  $paramstr=$paramstr."&channel=".urlencode($dest);
}

$uri="/s/soylent_log.php?op=".$alias.$paramstr;

var_dump($uri);

if (get_bucket("chromas_irc_log_debug")=="on")
{
  pm("chromas",$uri);
}

$response=wget("chromas.0x.no",$uri,80,ICEWEASEL_UA,"",20,"",1024,False);
$html=trim(strip_headers($response));

if ($html=="")
{
  return;
}

$lines=explode("\n",$html);

$msg=trim($lines[0]);

privmsg(chr(3)."03".$msg);

#####################################################################################################

?>
