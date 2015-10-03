<?php

#####################################################################################################

# .jisho|20|0|0|1|||##anime-japanese,#irciv||php scripts/japanese.php %%trailing%% %%dest%% %%nick%% %%alias%%

#####################################################################################################

require_once("lib.php");

define("HOST","jisho.org");
define("MAX_ITEMS",2);

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("syntax: .jisho <word>");
  privmsg("looks up jisho.org");
  return;
}

$uri="/search/".urlencode($trailing);

$response=wget(HOST,$uri);
$html=strip_headers($response);
if ($html===False)
{
  privmsg("error downloading");
  return;
}
$items=explode("<div class=\"concept_light clearfix\">",$html);
array_shift($items);

$n=min(MAX_ITEMS,count($items));

$results=array();

for ($i=0;$i<$n;$i++)
{
  $result=array();
  # hiragana
  $delim1="<span class=\"kanji-2-up kanji\">";
  $delim2="</span>";
  $result_hiragana=extract_text($items[$i],$delim1,$delim2);
  # kanji
  $delim1="<span class=\"text\">";
  $delim2="      </span>";
  $result_kanji=extract_text($items[$i],$delim1,$delim2);
  # english
  $delim1="<span class=\"meaning-meaning\">";
  $delim2="</span>";
  $result_english=extract_text($items[$i],$delim1,$delim2);
  if (($result_hiragana!==False) and ($result_kanji!==False) and ($result_english!==False))
  {
    $result["hiragana"]=trim(strip_tags($result_hiragana));
    $result["kanji"]=trim(strip_tags($result_kanji));
    $result["english"]=trim(strip_tags($result_english));
    $results[]=$result;
  }
}

if (count($results)>0)
{
  for ($i=0;$i<count($results);$i++)
  {
    privmsg($results[$i]["hiragana"].", ".$results[$i]["kanji"].", ".$results[$i]["english"]);
  }
  privmsg(HOST."/search/".$trailing);
}
else
{
  privmsg("no results");
}

#####################################################################################################

?>