<?php

# gpl2
# by crutchy

# http://wiki.soylentnews.org/wiki/Rss_sources

#####################################################################################################

require_once("lib.php");
require_once("feeds_lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$feed_chans=array("#exec");

if ($alias=="~feed-sources-wiki-get")
{
  echo "/INTERNAL ~wiki-feed-sources\n";
  return;
}

define("FEED_FILE","scripts/feeds.txt");
define("PAST_FEED_FILE","/var/www/irciv.us.to/exec_logs/feeds.txt");

$feed_list=load_feeds_from_file(FEED_FILE);
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

if ($alias=="~feed-sources-wiki") # called by wiki after saving sources to bucket
{

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

if ($alias=="~feed-wiki")
{
  echo "/INTERNAL ~wiki login\n";
  sleep(3);
  $items=get_new_items($feed_list);
  $c=count($items);
  for ($i=0;$i<$c;$i++)
  {
    $item=$items[$i];
    $title="Feeds";
    $section=$item["feed_name"].": ".$item["title"];
    $text=$item["url"];
    echo "/INTERNAL ~wiki edit $title|$section|$text\n";
    sleep(10);
  }
  echo "/INTERNAL ~wiki logout\n";
  return;
}

$items=get_new_items($feed_list);
var_dump($items);
$c=min(3,count($items));
for ($i=0;$i<$c;$i++)
{
  for ($j=0;$j<count($feed_chans);$j++)
  {
    if ($j>0)
    {
      sleep(2);
    }
    $item=$items[$i];
    $msg=chr(2)."[".$item["feed_name"]."]".chr(2)." - ".chr(3)."3".$item["title"].chr(3)." - ".$item["url"];
    echo "/IRC :".NICK_EXEC." PRIVMSG ".$feed_chans[$j]." :$msg\n";
  }
}

#####################################################################################################

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

?>
