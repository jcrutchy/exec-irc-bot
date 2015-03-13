<?php

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
    $entry["title"]=extract_raw_tag($parts[$i],"title");
    $entry["title"]=html_decode($entry["title"]);
    $entry["title"]=html_decode($entry["title"]);
    $entry["title"]=strip_tags($entry["title"]);
    $entry["title"]=replace_ctrl_chars($entry["title"]," ");
    $entry["title"]=str_replace("  "," ",$entry["title"]);
    # <updated>2014-07-20T21:07:00+00:00</updated>
    # <link rel="alternate" type="text/html" href="http://wiki.soylentnews.org/wiki/User:LeonardAEBE"/>
    $url=extract_void_tag($parts[$i],"link");
    # rel="alternate" type="text/html" href="http://wiki.soylentnews.org/wiki/User:GeriGilson"
    $url_parts=explode(" ",$url);
    $url="";
    for ($j=0;$j<count($url_parts);$j++)
    {
      if (substr($url_parts[$j],0,6)=="href=\"")
      {
        $url=trim(substr($url_parts[$j],6,strlen($url_parts[$j])-7));
      }
    }
    $url=trim(strip_ctrl_chars($url),"\"");
    $url=str_replace("&amp;","&",$url);
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
    $item["title"]=extract_raw_tag($parts[$i],"title");
    $item["title"]=html_decode($item["title"]);
    $item["title"]=html_decode($item["title"]);
    $item["title"]=strip_tags($item["title"]);
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
    $item["title"]=extract_raw_tag($parts[$i],"title");
    $item["title"]=html_decode($item["title"]);
    $item["title"]=html_decode($item["title"]);
    $item["title"]=replace_ctrl_chars($item["title"]," ");
    $item["title"]=str_replace("  "," ",$item["title"]);
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
