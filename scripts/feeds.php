<?php

# gpl2
# by crutchy
# 22-june-2014

# add an authenticated alias for adding/removing feeds, and maybe another for adding/removing users that can add/remove feeds
# add command to output last X feeds from a particular source <- NO

/*
Bytram, 9-june-14
it would be nice if you could get together with Juggs and have his Regurgitator
output not just the raw RSS feed link, but also snag the title, AND follow past
the feed-redirect-crap to get the REAL URL
Bytram, 11-june-14
Those sound interesting. I'm interested in your thoughts on feed scraping. It seems to me that many of the RSS feeds actually contain redirects before you actually get to the destination article.
For example, take a look at this feed item from The Register:
    http://go.theregister.com/feed/www.theregister.co.uk/2014/06/11/privacy_invasion_by_the_state_is_far_worse_than_by_private_firms_worstall_weds/
It would be nice to see that de-referenced to be just:
    http://www.theregister.co.uk/2014/06/11/privacy_invasion_by_the_state_is_far_worse_than_by_private_firms_worstall_weds/
I don't know whether this can be abstracted to just following the link and watching return codes (e.g. 501, 502, etc.(IIRC)) or whether a feed-specific filter would be needed.
In case of any parsing problems, it might be nice to show the before and after URLs, just in case.
Maybe send as a channel message? Processing: "raw-url"; it has title: "title-text"; it is [not redirected|redirected to "cleaned-url"].
This would make the URLs more useful when including in a story based on a feed item.
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

$feed_chans=array("#rss-bot");

define("FEED_FILE",__DIR__."/feeds.txt");
define("PAST_FEED_FILE","../data/past_feeds");

$feed_list=load_feeds();
if ($feed_list===False)
{
  term_echo("error loading feeds file");
  return;
}
if (count($feed_list)==0)
{
  term_echo("no valid sources in feeds file");
  return;
}

$past_feeds=array();
$past_feeds_bucket=get_bucket("<<PAST_FEEDS>>");

if ($past_feeds_bucket<>"")
{
  $past_feeds_bucket=unserialize($past_feeds_bucket);
  if ($past_feeds_bucket!==False)
  {
    $past_feeds=$past_feeds_bucket;
  }
}
else
{
  if (file_exists(PAST_FEED_FILE)==True)
  {
    $data=file_get_contents(PAST_FEED_FILE);
    if ($data!==False)
    {
      $past_feeds=explode("\n",$data);
    }
  }
}

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
  for ($j=0;$j<count($items);$j++)
  {
    $item=$items[$j];
    $url=strtolower($item["url"]);
    if (in_array($url,$past_feeds)==False)
    {
      $past_feeds[]=$url;
      file_put_contents(PAST_FEED_FILE,$item["url"]."\n",FILE_APPEND);
      $msg=chr(2)."[".$feed["name"]."]".chr(2)." - ".$item["title"]." - ".$item["url"];
      for ($k=0;$k<count($feed_chans);$k++)
      {
        if ($k>0)
        {
          sleep(3);
        }
        echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$feed_chans[$k]." :$msg\n";
        #term_echo($msg);
      }
    }
  }
}

# TODO: clear out old feeds (need to extract timestamp)

set_bucket("<<PAST_FEEDS>>",serialize($past_feeds));

#####################################################################################################

function parse_atom($html)
{
  $parts=explode("<entry",$html);
  array_shift($parts);
  $entries=array();
  for ($i=0;$i<count($parts);$i++)
  {
    $entry=array();
    $entry["type"]="atom_entry";
    $entry["title"]=html_entity_decode(extract_raw_tag($parts[$i],"title"),ENT_QUOTES,"UTF-8");
    $url=trim(extract_void_tag($parts[$i],"link href="),"\"");
    $entry["url"]=get_redirected_url($url);
    $entry["timestamp"]=time();
    if (($entry["title"]===False) or ($entry["url"]===False))
    {
      continue;
    }
    $entries[]=$entry;
  }
  return $entries;
}

#####################################################################################################

function parse_rss($html)
{
  $parts=explode("<item",$html);
  array_shift($parts);
  $items=array();
  for ($i=0;$i<count($parts);$i++)
  {
    $item=array();
    $item["type"]="rss_item";
    $item["title"]=html_entity_decode(extract_raw_tag($parts[$i],"title"),ENT_QUOTES,"UTF-8");
    $item["url"]=get_redirected_url(extract_raw_tag($parts[$i],"link"));
    $item["timestamp"]=time();
    if (($item["title"]===False) or ($item["url"]===False))
    {
      continue;
    }
    $items[]=$item;
  }
  return $items;
}

#####################################################################################################

function load_feeds()
{
  $feed_list=array();
  if (file_exists(FEED_FILE)==False)
  {
    return False;
  }
  $data=file_get_contents(FEED_FILE);
  if ($data===False)
  {
    return False;
  }
  $data=explode("\n",$data);
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    if ($line=="")
    {
      continue;
    }
    if (substr($line,0,1)=="#")
    {
      continue;
    }
    $parts=explode("|",$line);
    if (count($parts)<>3)
    {
      continue;
    }
    $feed=array();
    $feed["type"]=strtolower(trim($parts[0]));
    $feed["name"]=trim($parts[1]);
    $feed["url"]=trim($parts[2]);
    $comp=parse_url($feed["url"]);
    $feed["host"]=$comp["host"];
    $feed["uri"]=$comp["path"];
    if (isset($comp["query"])==True)
    {
      if ($comp["query"]<>"")
      {
        $feed["uri"]=$feed["uri"]."?".$comp["query"];
      }
    }
    if (isset($comp["fragment"])==True)
    {
      if ($comp["fragment"]<>"")
      {
        $feed["uri"]=$feed["uri"]."#".$comp["fragment"];
      }
    }
    $feed["port"]=80;
    if (isset($comp["scheme"])==True)
    {
      if ($comp["scheme"]=="https")
      {
        $feed["port"]=443;
      }
    }
    if (($feed["type"]=="") or ($feed["url"]=="") or ($feed["host"]=="") or ($feed["uri"]==""))
    {
      continue;
    }
    $feed_list[]=$feed;
  }
  return $feed_list;
}

#####################################################################################################

?>
