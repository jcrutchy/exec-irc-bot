define("IRC_LOG_PATH","/var/www/irciv.us.to/irc_logs/");
define("IRC_LOG_INDEX_FILE","/var/www/irciv.us.to/irc_logs/index.php");
define("IRC_LOG_INDEX_FILE_HTML","/var/www/irciv.us.to/irc_logs/index.html");
define("IRC_LOG_URL","http://irciv.us.to/irc_logs/");

define("HTML_NO_CACHE","<meta http-equiv=\"cache-control\" content=\"max-age=0\">\n<meta http-equiv=\"cache-control\" content=\"no-cache\">\n<meta http-equiv=\"expires\" content=\"0\">\n<meta http-equiv=\"expires\" content=\"Tue, 01 Jan 1980 1:00:00 GMT\">\n<meta http-equiv=\"pragma\" content=\"no-cache\">\n");
define("IRC_INDEX_SOURCE","<?php include(\"".IRC_LOG_INDEX_FILE_HTML."\"); ?>");
define("IRC_INDEX_HTML_HEAD","<!DOCTYPE HTML>\n<html>\n<head>\n<title>SoylentNews IRC Log Index</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">\n".HTML_NO_CACHE."<style type=\"text/css\"></style>\n<script type=\"text/javascript\"></script>\n</head>\n<body>\n<p>\n");
define("IRC_CHAN_INDEX_HEAD","<!DOCTYPE HTML>\n<html>\n<head>\n<title>%%title%%</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">\n".HTML_NO_CACHE."<style type=\"text/css\"></style>\n<script type=\"text/javascript\"></script>\n</head>\n<body>\n<p><a href=\"index.html\">return to channel index</a></p>\n<p>\n");
define("IRC_LOG_HEAD","<!DOCTYPE HTML>\n<html>\n<head>\n<title>%%title%%</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">\n".HTML_NO_CACHE."<style type=\"text/css\"></style>\n<script type=\"text/javascript\"></script>\n</head>\n<body>\n<p><a href=\"%%index_href%%\">return to date index</a></p>\n<p>\n");

function log_items($items)
{
  global $log_chans;
  $dest=$items["destination"];
  $cmd=$items["cmd"];
  $trailing=$items["trailing"];
  $nick=$items["nick"];
  if (($dest=="") or (substr($dest,0,1)<>"#") or (strpos($dest," ")!==False))
  {
    return;
  }
  if (isset($log_chans[$dest])==True)
  {
    if ($log_chans[$dest]=="off")
    {
      return;
    }
  }
  else
  {
    return;
  }
  if (file_exists(IRC_LOG_INDEX_FILE)==False)
  {
    file_put_contents(IRC_LOG_INDEX_FILE,IRC_INDEX_SOURCE);
  }
  if (file_exists(IRC_LOG_INDEX_FILE_HTML)==False)
  {
    file_put_contents(IRC_LOG_INDEX_FILE_HTML,IRC_INDEX_HTML_HEAD);
  }
  $contents=file_get_contents(IRC_LOG_INDEX_FILE_HTML);
  $chan_enc=urlencode($dest);
  if (strpos($contents,$dest)===False)
  {
    $line="<a href=\"index_$chan_enc.html\">$dest</a><br>\n";
    file_put_contents(IRC_LOG_INDEX_FILE_HTML,$line,FILE_APPEND);
  }
  $timestamp=date("H:i:s",microtime(True));
  $timestamp_name=date("His",microtime(True));
  $filename=IRC_LOG_PATH.$dest."_".date("Ymd",time()).".html";
  $filename_href=urlencode($dest)."_".date("Ymd",time()).".html";
  $href_caption=date("Y-m-d",time());

  $data="&lt;$nick&gt; $trailing";

  $line="<a href=\"#$timestamp_name\" name=\"$timestamp_name\" class=\"time\">[$timestamp]</a> $data<br>\n";
  if (file_exists($filename)==False)
  {
    $chan_index_filename=IRC_LOG_PATH."index_".$dest.".html";
    if (file_exists($chan_index_filename)==False)
    {
      $head=IRC_CHAN_INDEX_HEAD;
      $head=str_replace("%%title%%","$dest | SoylentNews IRC Log",$head);
      file_put_contents($chan_index_filename,$head);
    }
    $contents=file_get_contents($chan_index_filename);
    if (strpos($contents,$filename_href)===False)
    {
      $line_chan_index="<a href=\"$filename_href\">$href_caption</a><br>\n";
      file_put_contents($chan_index_filename,$line_chan_index,FILE_APPEND);
    }
    $head=IRC_LOG_HEAD;
    $head=str_replace("%%title%%","$dest | $href_caption",$head);
    $head=str_replace("%%index_href%%","index_$chan_enc.html",$head);
    file_put_contents($filename,$head);
  }
  file_put_contents($filename,$line,FILE_APPEND);
}
