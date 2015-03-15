<?php

#####################################################################################################

/*
exec:~feeds|1000|0|0|1|crutchy||||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~feeds-internal|500|1800|0|1||INTERNAL|||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
exec:~feed-list|5|0|0|1|||||php scripts/feeds.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

# TODO: FEED ITEM LIMIT PARAMETER FOR EACH FEED IN FEED LIST FILE

require_once("lib.php");
require_once("feeds_lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$feed_chan="#feeds";

define("FEED_LIST_FILE","../data/feed_list.txt");
define("FEED_HISTORY_FILE","../data/feed_history.txt");

$feed_list=load_feeds_from_file(FEED_LIST_FILE);

if ($feed_list===False)
{
  term_echo("error loading feed list file");
  return;
}
if (count($feed_list)==0)
{
  term_echo("no valid sources in feed list file");
  return;
}

if ($alias=="~feeds-sources")
{
  $msg="";
  for ($i=0;$i<count($feed_list);$i++)
  {
    if ($msg<>"")
    {
      $msg=$msg." ";
    }
    $msg=$msg."[".$feed_list[$i]["name"]."]";
  }
  if ($msg<>"")
  {
    privmsg(chr(2).$msg.chr(2));
  }
  return;
}

$feed_history=array();
if (file_exists(FEED_HISTORY_FILE)==True)
{
  $data=file_get_contents(FEED_HISTORY_FILE);
  if ($data!==False)
  {
    $feed_history=explode(PHP_EOL,$data);
  }
}

$results=array();
$new_history=array();
for ($i=0;$i<count($feed_list);$i++)
{
  $feed=$feed_list[$i];
  term_echo("processing ".$feed["name"]." ".$feed["type"]." feed @ \"".$feed["url"]."\"");
  $response=wget($feed["host"],$feed["uri"],$feed["port"]);
  $html=strip_headers($response);
  $items=array();
  if ($feed["type"]=="atom")
  {
    $items=parse_atom($html);
  }
  elseif ($feed["type"]=="rss")
  {
    $items=parse_rss($html);
  }
  if ($items===False)
  {
    term_echo("feed parse error");
    continue;
  }
  term_echo("feed items for ".$feed["name"].": ".count($items));
  for ($j=0;$j<count($items);$j++)
  {
    $item=$items[$j];
    if (in_array($item["url"],$feed_history)==False)
    {
      $new_history[]=$item["url"];
      $delim1="<![CDATA[";
      $delim2="]]>";
      if (strtoupper(substr($item["title"],0,strlen($delim1)))==$delim1)
      {
        $item["title"]=substr($item["title"],strlen($delim1),strlen($item["title"])-strlen($delim1)-strlen($delim2));
      }
      $item["title"]=str_replace("&apos;","'",$item["title"]);
      $item["feed_name"]=$feed["name"];
      $item["dest"]=$feed["dest"];
      $results[]=$item;
    }
  }
}
$data="";
for ($i=0;$i<count($new_history);$i++)
{
  $data=$data.$new_history[$i].PHP_EOL;
}
file_put_contents(FEED_HISTORY_FILE,$data,FILE_APPEND);
pm($feed_chan,chr(3)."08"."************");
for ($i=count($results)-1;$i>=0;$i--)
{
  $item=$results[$i];
  $msg=chr(2)."[".$item["feed_name"]."]".chr(2)." - ".chr(3)."03".$item["title"].chr(3)." - ".$item["url"];
  if ($item["dest"]<>"")
  {
    pm($item["dest"],$msg);
  }
  else
  {
    pm($feed_chan,$msg);
  }
}

#####################################################################################################

?>
