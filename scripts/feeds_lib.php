<?php

# gpl2
# by crutchy
# 14-sep-2014

#####################################################################################################

function get_new_items($feed_list)
{
  $results=array();
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
        $delim1="<![CDATA[";
        $delim2="]]>";
        if (strtoupper(substr($item["title"],0,strlen($delim1)))==$delim1)
        {
          $item["title"]=substr($item["title"],strlen($delim1),strlen($item["title"])-strlen($delim1)-strlen($delim2));
        }
        $item["title"]=str_replace("&apos;","'",$item["title"]);
        $item["feed_name"]=$feed["name"];
        $results[]=$item;
        if (count($results)>=3)
        {
          break;
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
  return $results;
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
    $entry["type"]="atom_entry";
    $entry["title"]=html_entity_decode(extract_raw_tag($parts[$i],"title"),ENT_QUOTES,"UTF-8");
    $entry["title"]=html_entity_decode($entry["title"],ENT_QUOTES,"UTF-8");
    $item["title"]=replace_ctrl_chars($item["title"]," ");
    $item["title"]=str_replace("  "," ",$item["title"]);
    # <updated>2014-07-20T21:07:00+00:00</updated>
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
    $item["title"]=html_entity_decode($item["title"],ENT_QUOTES,"UTF-8");
    $item["title"]=replace_ctrl_chars($item["title"]," ");
    $item["title"]=str_replace("  "," ",$item["title"]);
    # <dc:date>2014-07-20T19:05:00+00:00</dc:date>
    # <pubDate>Sun, 20 Jul 2014 19:08:38 +0000</pubDate>
    # <pubDate><![CDATA[Mon, 21 Jul 2014 08:30:06 +1000]]></pubDate>
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

function parse_xml($html)
{
  $parts=explode("<story",$html);
  array_shift($parts);
  $items=array();
  for ($i=0;$i<count($parts);$i++)
  {
    $item=array();
    $item["type"]="xml_story";
    $item["title"]=html_entity_decode(extract_raw_tag($parts[$i],"title"),ENT_QUOTES,"UTF-8");
    $item["title"]=html_entity_decode($item["title"],ENT_QUOTES,"UTF-8");
    $item["title"]=replace_ctrl_chars($item["title"]," ");
    $item["title"]=str_replace("  "," ",$item["title"]);
    # <dc:date>2014-07-20T19:05:00+00:00</dc:date>
    # <pubDate>Sun, 20 Jul 2014 19:08:38 +0000</pubDate>
    # <pubDate><![CDATA[Mon, 21 Jul 2014 08:30:06 +1000]]></pubDate>
    $url=str_replace("&amp;","&",strip_ctrl_chars(extract_raw_tag($parts[$i],"url")));
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

function load_feeds_from_file($filename)
{
  if (file_exists($filename)==False)
  {
    return False;
  }
  $data=file_get_contents($filename);
  if ($data===False)
  {
    return False;
  }
  $data=explode("\n",$data);
  return load_feeds($data);
}

#####################################################################################################

function load_feeds_from_wiki($title)
{
  $data="";
  return load_feeds($data);
}

#####################################################################################################

function load_feeds($data)
{
  $feed_list=array();
  $c=count($data);
  for ($i=0;$i<$c;$i++)
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
