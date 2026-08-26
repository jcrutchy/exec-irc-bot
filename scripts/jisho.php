<?php

# this script has been superseded by jisho2.php

#####################################################################################################

ini_set("display_errors","on");

require_once("lib.php");

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

$max_items=2;
$host="jisho.org";
$uri="/search/".urlencode($trailing);

$response=wget($host,$uri);
$html=strip_headers($response);
if ($html===False)
{
  privmsg("error downloading");
  return;
}
$items=explode("<div class=\"concept_light clearfix\">",$html);
array_shift($items);

$results=array();

for ($i=0;$i<count($items);$i++)
{
  $result=array();
  # hiragana
  $delim1="<span class=\"kanji-2-up kanji\">";
  $delim2="</span>";
  $result_hiragana_2=extract_text($items[$i],$delim1,$delim2);
  $delim1="<span class=\"kanji-3-up kanji\">";
  $delim2="</span>";
  $result_hiragana_3=extract_text($items[$i],$delim1,$delim2);
  $result_hiragana=False;
  if (($result_hiragana_2!==False) and ($result_hiragana_3===False))
  {
    $result_hiragana=$result_hiragana_2;
  }
  elseif (($result_hiragana_2===False) and ($result_hiragana_3!==False))
  {
    $result_hiragana=$result_hiragana_3;
  }
  elseif (($result_hiragana_2!==False) and ($result_hiragana_3!==False))
  {
    $result_hiragana=$result_hiragana_2.", ".$result_hiragana_3;
  }
  # kanji
  $delim1="<span class=\"text\">";
  $delim2="      </span>";
  $result_kanji=extract_text($items[$i],$delim1,$delim2);
  # english
  $delim1="<span class=\"meaning-meaning\">";
  $delim2="</span>";
  $result_english=extract_text($items[$i],$delim1,$delim2);
  $result["hiragana"]=False;
  if ($result_hiragana!==False)
  {
    $result["hiragana"]=trim(strip_tags($result_hiragana));
  }
  $result["kanji"]=False;
  if ($result_kanji!==False)
  {
    $result["kanji"]=trim(strip_tags($result_kanji));
  }
  if ($result_english!==False)
  {
    $result["english"]=trim(strip_tags($result_english));
    $results[]=$result;
  }
}

$n=0;
for ($i=0;$i<count($results);$i++)
{
  if (($results[$i]["hiragana"]===False) and ($results[$i]["kanji"]!==False))
  {
    privmsg(chr(3).$results[$i]["kanji"].", ".$results[$i]["english"]);
    $n++;
  }
  elseif (($results[$i]["hiragana"]!==False) and ($results[$i]["kanji"]===False))
  {
    privmsg(chr(3).$results[$i]["hiragana"].", ".$results[$i]["english"]);
    $n++;
  }
  elseif (($results[$i]["hiragana"]!==False) and ($results[$i]["kanji"]!==False))
  {
    privmsg(chr(3).$results[$i]["hiragana"].", ".$results[$i]["kanji"].", ".$results[$i]["english"]);
    $n++;
  }
  if ($n>=$max_items)
  {
    break;
  }
}

if ($n==0)
{
  privmsg("no results");
}
else
{
  privmsg(count($results)." results");
}

#####################################################################################################

?>