<?php

# gpl2
# by crutchy
# 26-june-2014

require_once("lib.php");
date_default_timezone_set("UTC");
$html=wget("www.google.com","/search?source=hp&q=time+".urlencode($argv[1]),80);
$html=strip_headers($html);
strip_all_tag($html,"head");
strip_all_tag($html,"script");
strip_all_tag($html,"style");
strip_all_tag($html,"a");
$html=strip_tags($html,"<div>");
$result="";
$delim1="<div id=\"res\"><div id=\"topstuff\"></div><div id=\"search\"><div id=\"ires\"><div>";
$delim2="</div>";
$i=strpos($html,$delim1);
if ($i!==False)
{
  $html=substr($html,$i+strlen($delim1));
  $i=strpos($html,$delim2);
  if ($i!==False)
  {
    $html=trim(substr($html,0,$i));
    if (($html<>"") and (strpos($html,"Time in")!==False))
    {
      $result=substr($html,0,300);
    }
  }
}
if ($result<>"")
{
  privmsg("[Google] ".$result);
}
else
{
  privmsg("location not found - UTC timestamp: ".date("l, j F Y, h:i:s a"));
  /*$delim1="</div><div style=\"display:none\" class=\"am-dropdown-menu\" role=\"menu\" tabindex=\"-1\"></div></div></div>";
  $delim2="</div";
  $i=strpos($html,$delim1);
  if ($i!==False)
  {
    $html=substr($html,$i+strlen($delim1));
    $i=strpos($html,$delim2);
    if ($i!==False)
    {
      $html=trim(substr($html,0,$i));
      if ($html<>"")
      {
        $result=html_entity_decode($html,ENT_QUOTES,"UTF-8");
        $result=str_replace("\n","",$result);
        $result=strip_tags($result);
        $delim=" - ‎ - ‎ - ‎";
        if (substr($result,strlen($result)-strlen($delim))==$delim)
        {
          $result=trim(substr($result,0,strlen($result)-strlen($delim)));
        }
        $delim=" - ‎ - ‎‎";
        if (substr($result,strlen($result)-strlen($delim))==$delim)
        {
          $result=trim(substr($result,0,strlen($result)-strlen($delim)));
        }
        $result=trim(substr($result,0,400));
        $result=trim($result," ");
        if ($result<>"")
        {
          privmsg("[Google] ".$result);
        }
      }
    }
  }*/
}

?>
