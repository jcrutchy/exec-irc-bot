<?php

#####################################################################################################

/*

exec:add ~youtube
exec:edit ~youtube timeout 20
exec:edit ~youtube cmd php scripts/youtube.php %%trailing%%
exec:enable ~youtube
help:~youtube|syntax: ~youtube <query>
help:~youtube|returns first result URL for a youtube search

exec:add ~yt
exec:edit ~yt timeout 20
exec:edit ~yt cmd php scripts/youtube.php %%trailing%%
exec:enable ~yt
help:~yt|syntax: ~yt <query>
help:~yt|returns first result URL for a youtube search

*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);

if ($trailing=="")
{
  privmsg("syntax: ~youtube <query>");
  privmsg("returns first result URL for a youtube search");
  return;
}

$results=youtube_search($trailing);

if ($results!==False)
{
  if (count($results)>0)
  {
    if (strlen($results[0])>300)
    {
      return;
    }
    privmsg($results[0]);
  }
}

#####################################################################################################

function youtube_search($query)
{
  $agent=ICEWEASEL_UA;
  $host="www.youtube.com";
  $uri="/results";
  $port=443;
  $params=array();
  $params["search_query"]=$query;
  $response=wpost($host,$uri,$port,$agent,$params);
  $html=strip_headers($response);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  $delim1="class=\"item-section\">";
  $delim2="</ol>";
  $html=extract_text_nofalse($html,$delim1,$delim2);
  $results=explode("<li><div class=\"yt-lockup yt-lockup-tile yt-lockup-video vve-check clearfix yt-uix-tile\"",$html);
  array_shift($results);
  if (count($results)==0)
  {
    return False;
  }
  for ($i=0;$i<count($results);$i++)
  {
    $parts=explode(">",$results[$i]);
    array_shift($parts);
    $results[$i]=implode(">",$parts);
    $delim1="<h3 class=\"yt-lockup-title \">";
    $delim2="</h3>";
    $results[$i]=extract_text_nofalse($results[$i],$delim1,$delim2);
    $delim1="<a href=\"";
    $delim2="\" ";
    $url="https://www.youtube.com".extract_text_nofalse($results[$i],$delim1,$delim2);
    $delim1="dir=\"ltr\">";
    $delim2="</a>";
    $title=extract_text_nofalse($results[$i],$delim1,$delim2);
    $title=html_decode($title);
    $title=html_decode($title);
    $delim1="> - Duration: ";
    $delim2=".</span>";
    $time=extract_text_nofalse($results[$i],$delim1,$delim2);
    $results[$i]=$url." - ".$title." - ".$time;
  }
  return $results;
}

#####################################################################################################

?>
