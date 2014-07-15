<?php

# gpl2
# by crutchy
# 15-july-2014

#####################################################################################################

date_default_timezone_set("UTC");
require_once("lib.php");

define("CACHE_PATH","/var/www/irciv.us.to/logs.sylnt.us/");

$trailing=$argv[1];
$nick=trim($argv[2]);
$dest=strtolower($argv[3]);
$alias=$argv[4];

$uri="/".urlencode($dest)."/index.html";
$html=wget("logs.sylnt.us",$uri,80);
$html=strip_headers($html);

#term_echo("downloaded http://logs.sylnt.us$uri => ".strlen($html)." bytes (content)");

strip_all_tag($html,"head");

$links=explode("<a href=\"",$html);
array_shift($links);
$links2=array();
for ($i=0;$i<count($links);$i++)
{
  $parts=explode("\">",$links[$i]);
  if (strpos($parts[0],"html")!==False)
  {
    $links2[]=$parts[0];
  }
}

$privmsg=array();

$date_today=round(time()/60/60/24);

for ($i=0;$i<count($links2);$i++)
{
  $date_parts=explode(".html",$links2[$i]);
  $date_file=round(convert_timestamp($date_parts[0],"Y-m-d")/60/60/24);
  $cache_filename=CACHE_PATH.urldecode($dest)."_".$date_parts[0].".txt";
  $lines2=array();
  $status_fn="";
  if (file_exists($cache_filename)==True)
  {
    $status_fn=$cache_filename;
    $lines2=explode("\n",file_get_contents($cache_filename));
  }
  else
  {
    $uri="/".urlencode($dest)."/".$links2[$i];
    $status_fn="http://logs.sylnt.us".$uri;
    $html=wget("logs.sylnt.us",$uri,80);
    $html=strip_headers($html);
    strip_all_tag($html,"head");
    $lines=explode("class=\"time\">",$html);
    array_shift($lines);
    $lines2=array();
    for ($j=0;$j<count($lines);$j++)
    {
      $line=trim($lines[$j]);
      $line=strip_tags($line);
      $k=strpos($line,"\n");
      if ($k!==False)
      {
        $line=trim(substr($line,0,$k));
      }
      # [11:36:41] &lt;crutchy&gt; blah test
      $delim="[";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line=trim(substr($line,$k+strlen($delim)));
      # 11:36:41] &lt;crutchy&gt; blah test
      $delim="]";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line_time=substr($line,0,$k);
      $line=trim(substr($line,$k+strlen($delim)));
      # &lt;crutchy&gt; blah test
      $delim="&lt;";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line=trim(substr($line,$k+strlen($delim)));
      # crutchy&gt; blah test
      $delim="&gt;";
      $k=strpos($line,$delim);
      if ($k===False)
      {
        continue;
      }
      $line_nick=substr($line,0,$k);
      $line=trim(substr($line,$k+strlen($delim)));
      # blah test
      $line_msg=$line;
      $line_dest=$dest;
      $line_date=$date_parts[0];
      $record=array();
      $record["dest"]=$line_dest;
      $record["date"]=$line_date;
      $record["time"]=$line_time;
      $record["nick"]=$line_nick;
      $record["msg"]=$line_msg;
      $lines2[$j]=serialize($record);
    }
    if ($date_file<$date_today)
    {
      file_put_contents($cache_filename,implode("\n",$lines2));
    }
  }
  $privmsg=array_merge($privmsg,$lines2);
  #term_echo("processing \"$status_fn\" => lines: ".count($lines2)." [$date_file : $date_today]");
}

$privmsg2=array();
for ($i=0;$i<count($privmsg);$i++)
{
  $privmsg2[]=unserialize($privmsg[$i]);
}

if ($trailing<>"")
{
  $privmsg_nick=array();
  for ($i=0;$i<count($privmsg);$i++)
  {
    if ($privmsg2[$i]["nick"]==$trailing)
    {
      $privmsg_nick[]=$privmsg2[$i];
    }
  }
  if ($alias=="~stats-lines")
  {
    privmsg("privmsg count for $trailing in $dest: ".count($privmsg_nick));
  }
  elseif ($alias=="~stats-first")
  {
    if (count($privmsg_nick)>0)
    {
      $record=$privmsg_nick[0];
      privmsg("first privmsg for $trailing in $dest: [".$record["date"]." ".$record["time"]."] ".$record["msg"]);
    }
    else
    {
      privmsg("first privmsg for $trailing in $dest not found");
    }
  }
}

#term_echo("log line count = ".count($privmsg));

#####################################################################################################

?>
