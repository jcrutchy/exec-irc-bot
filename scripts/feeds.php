<?php

# gpl2
# by crutchy
# 13-june-2014

# /nas/server/git/data/atom.feeds contains a list of urls for scraping

# http://phys.org/rss-feed/

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

define("FEED_FILE","feeds.txt");

#$feed_chans=array("#rss-bot");
$feed_chans=array("#~"); # testing channel

$feed_list=array(); # loaded from FEED_FILE

$html=wget("soylentnews.org","/index.atom",80);
$html=strip_headers($html);

$entries=parse_atom($html);
if ($entries===False)
{
  privmsg("error parsing atom feed");
}

#####################################################################################################

function parse_atom($html)
{
  $parts=explode("<entry",$html);
  array_shift($parts);
  $entries=array();
  for ($i=0;$i<count($parts);$i++)
  {
    $entry=array();
    $entry["title"]=extract_raw_tag($parts[$i],"title");
    $entry["link"]=extract_void_tag($parts[$i],"link");
    if (($entry["title"]===False) or ($entry["link"]===False))
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
    $item["title"]=extract_raw_tag($parts[$i],"title");
    $item["link"]=extract_raw_tag($parts[$i],"link");
    if (($item["title"]===False) or ($item["link"]===False))
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
  global $feed_list;
  $feed_list=array();
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
    if (count($parts)<>2)
    {
      continue;
    }
    $feed=array();
    $feed["type"]=trim($parts[0]);
    $feed["url"]=trim($parts[1]);
    $feed["host"]="";
    $feed["uri"]="";
    $feed["scheme"]="";
    $feed["port"]="";
    if (($feed["type"]=="") or ($feed["url"]==""))
    {
      continue;
    }
    $feed_list[]=$feed;
  }
  return $exec_list;
}

#####################################################################################################

?>
