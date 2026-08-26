<?php

#####################################################################################################

/*
exec:~aur|30|0|0|1|||||php scripts/aur.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg(chr(3)."02syntax: ~aur <query>");
  return;
}

$uri="/packages/?O=0&SeB=nd&K=".urlencode($trailing)."&outdated=off&SB=p&SO=a&PP=50&do_Search=Go";

$response=wget("aur.archlinux.org",$uri,443);
$html=strip_headers($response);

$delim="<div id=\"pkglist-results\" class=\"box\">";
$i=strpos($html,$delim);
if ($i===False)
{
  return;
}
$html=substr($html,$i+strlen($delim));

$count=extract_text($html,"<div class=\"pkglist-stats\">","packages");
if ($count===False)
{
  return;
}
$count=clean_text(strip_tags($count));

$delim="<form id=\"pkglist-results-form\"";
$i=strpos($html,$delim);
if ($i===False)
{
  return;
}
$html=substr($html,$i+strlen($delim));

$delim="<table class=\"results\">";
$i=strpos($html,$delim);
if ($i===False)
{
  return;
}
$html=substr($html,$i+strlen($delim));

$delim="<tbody>";
$i=strpos($html,$delim);
if ($i===False)
{
  return;
}
$html=substr($html,$i+strlen($delim));

$delim="</tr>";
$i=strpos($html,$delim);
if ($i===False)
{
  return;
}
$html=substr($html,0,$i);

$parts=explode("</td>",$html);
array_pop($parts);
if (count($parts)<>6)
{
  return;
}

$addr=extract_text($parts[0],"<a href=\"","\">");
if ($addr===False)
{
  return;
}

$addr="https://aur.archlinux.org".$addr;

for ($i=0;$i<count($parts);$i++)
{
  $parts[$i]=clean_text(strip_tags($parts[$i]));
}

$name=$parts[0];
$version=$parts[1];
$description=$parts[4];

privmsg(chr(3)."02"."top result out of $count: $name [$version]");
privmsg(chr(3)."02".$description);
privmsg(chr(3)."02".$addr);

#####################################################################################################

?>
