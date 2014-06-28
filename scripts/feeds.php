<?php

# gpl2
# by crutchy
# 27-june-2014

# http://wiki.soylentnews.org/wiki/Rss_sources

/*
Available feeds from Regurgitator:
!SoylentNews
!arstechnica
!bbc-tech
!bugtraq
!cnet
!computerworld
!darpa
!forbes-tech
!itworld
!krebs
!mosaicscience
!nasa
!nature
!nist-bioscience
!nist-buildfire
!nist-chemistry
!nist-electronics
!nist-energy
!nist-forensics
!nist-it
!nist-manufacturing
!nist-math
!nist-nano
!nist-physics
!nist-standards
!physorg
!pipedot
!sciencedaily_all
!sciencemag
!securityweek
!taosecurity
!theregister
!wired-enterprise
!wired-scie
*/

# add an authenticated alias for adding/removing feeds

/*
martyb
Woods
juggs
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$feed_chans=array("#~");

define("FEED_FILE",__DIR__."/feeds.txt");
define("PAST_FEED_FILE","/var/www/irciv.us.to/exec_logs/feeds.txt");

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

if ($alias=="~feed-sources")
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

$current_feeds=array();

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
    $current_feeds[]=$item["url"];
    if (in_array($item["url"],$past_feeds)==False)
    {
      if ($j>0)
      {
        sleep(3);
      }
      $past_feeds[]=$item["url"];
      # <![CDATA[Elon Musk "Hopeful" First People Can Be Taken To Mars in 10-12 Years]]>
      $delim="<![CDATA[";
      if (strtoupper(substr($item["title"],0,strlen($delim)))==$delim)
      {
        $item["title"]=substr($item["title"],strlen($delim),strlen($item["title"])-strlen($delim)-3);
      }
      $item["title"]=str_replace("&apos;","'",$item["title"]);
      $msg=chr(2)."[".$feed["name"]."]".chr(2)." - ".chr(3)."3".$item["title"].chr(3)." - ".$item["url"];
      for ($k=0;$k<count($feed_chans);$k++)
      {
        if ($k>0)
        {
          sleep(2);
        }
        echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$feed_chans[$k]." :$msg\n";
      }
    }
  }
}

$data="";
for ($i=0;$i<count($current_feeds);$i++)
{
  if ($data<>"")
  {
    $data=$data."\n";
  }
  $data=$data.$current_feeds[$i];
}
file_put_contents(PAST_FEED_FILE,$data);
set_bucket("<<PAST_FEEDS>>",serialize($current_feeds));

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
    $url=str_replace("&amp;","&",trim(strip_ctrl_chars(extract_void_tag($parts[$i],"link href=")),"\""));
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
    $url=str_replace("&amp;","&",strip_ctrl_chars(extract_raw_tag($parts[$i],"link")));
    $item["url"]=get_redirected_url($url);
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
