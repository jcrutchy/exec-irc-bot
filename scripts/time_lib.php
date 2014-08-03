<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

function get_time($location)
{
  $location=trim($location);
  $html=wget("www.google.com","/search?source=hp&q=time+".urlencode($location),80);
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
  return $result;
}

#####################################################################################################

?>
