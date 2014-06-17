<?php

# gpl2
# by crutchy
# 17-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

define("CACHE_PATH","/var/www/irciv.us.to/logs.sylnt.us/");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$html=wget("logs.sylnt.us","/".urlencode($dest)."/index.html",80);
$html=strip_headers($html);
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

$date_today=intval(time());

for ($i=0;$i<count($links2);$i++)
{
  $date_parts=explode(".html",$links2[$i]);
  $date_file=intval(convert_timestamp($date_parts[0],"Y-m-d"));
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
      $parts=explode("\n",$lines[$j]);
      $line=strip_tags($parts[0]);
      # [11:36:41] &lt;crutchy&gt; blah test
      #test 2014-03-06 11:36:41 crutchy blah test
      # dest unix_timestamp nick trailing
      $lines2[$j]=$line;
    }
    if ($date_file<$date_today)
    {
      file_put_contents($cache_filename,implode("\n",$lines2));
    }
  }
  $privmsg=array_merge($privmsg,$lines2);
  term_echo("processing \"$status_fn\" => lines: ".count($lines2));
}

term_echo("log line count = ".count($privmsg));

#####################################################################################################

?>
