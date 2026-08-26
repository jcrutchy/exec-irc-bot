<?php

#####################################################################################################

/*
exec:~privmsg-internal|5|0|0|1||INTERNAL|||php scripts/privmsg.php %%trailing%% %%nick%% %%dest%%
init:~privmsg-internal register-events
*/

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

$trailing=trim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~privmsg-internal %%trailing%%");
  return;
}

if (strtolower($trailing)==get_bot_nick().": help")
{
  privmsg("http://sylnt.us/exec");
  return;
}

if (strtolower(substr($trailing,0,5))=="!isr ")
{
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  if (count($parts)<3)
  {
    #privmsg(chr(3)."04In Soviet Russia, error causes YOU!");
    return;
  }
  $x1=$parts[1];
  if (strtolower(substr($x1,strlen($x1)-3))=="ing")
  {
    $x1=substr($x1,0,strlen($x1)-3)."e";
  }
  array_shift($parts);
  array_shift($parts);
  $x2=implode(" ",$parts);
  privmsg(chr(3)."04In Soviet Russia, ".$x2." ".$x1."s YOU!");
  return;
}

/*if ($trailing=="!stats")
{
  privmsg("http://stats.sylnt.us/social/soylent/");
  privmsg("http://antiartificial.com/stats/soylent/soylentnews.html");
  return;
}*/

define("PREFIX_POKE",".poke ");
if (substr(strtolower($trailing),0,strlen(PREFIX_POKE))==PREFIX_POKE)
{
  $target=substr($trailing,strlen(PREFIX_POKE));
  action("pokes $target");
}

$keywords=array(
  "crutchy",
  "exec",
  "irciv");

# TODO: color code "crutchy" lines

$ltrailing=strtolower($trailing);

if ($dest<>"crutchy")
{
  for ($i=0;$i<count($keywords);$i++)
  {
    if (strpos($ltrailing,$keywords[$i])!==False)
    {
      pm("crutchy","[$dest] <$nick> $trailing");
      return;
    }
  }
}

$hex2dec=".hex2dec";
if (substr($ltrailing,0,strlen($hex2dec))==$hex2dec)
{
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  if (count($parts)==2)
  {
    privmsg("  ".hexdec(trim($parts[1])));
  }
}
$dec2hex=".dec2hex";
if (substr($ltrailing,0,strlen($dec2hex))==$dec2hex)
{
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  if (count($parts)==2)
  {
    privmsg("  ".dechex(trim($parts[1])));
  }
}

#####################################################################################################

?>
