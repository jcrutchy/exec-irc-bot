<?php

#####################################################################################################

# .jdict|20|0|0|1|||##anime-japanese,#irciv||php scripts/japanese.php %%trailing%% %%dest%% %%nick%% %%alias%%

#####################################################################################################

require_once("lib.php");

define("HOST","www.romajidesu.com");
define("MAX_ITEMS",2);

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("syntax: .jdict <word>");
  privmsg("looks up www.romajidesu.com");
  return;
}

$uri="/dictionary/meaning-of-".urlencode($trailing).".html";

$response=wget(HOST,$uri);
$html=strip_headers($response);
if ($html===False)
{
  privmsg("error downloading");
  return;
}
$items=explode("<div class=\"search_items\">",$html);
array_shift($items);

$n=min(MAX_ITEMS,count($items));

$results=array();

for ($i=0;$i<$n;$i++)
{
  $delim1="<ruby>";
  $delim2="</ruby>";
  $result=extract_text($items[$i],$delim1,$delim2);
  if ($result!==False)
  {
    $result=str_replace("<rp>("," <rp>(",$result);
    $results[]=strip_tags($result);
  }
}

if (count($results)>0)
{
  for ($i=0;$i<count($results);$i++)
  {
    privmsg($results[$i]);
  }
  privmsg(HOST.$uri);
}
else
{
  privmsg("no results");
}

#####################################################################################################

?>