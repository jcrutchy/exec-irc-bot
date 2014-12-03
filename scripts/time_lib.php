<?php

# gpl2
# by crutchy
# 2-sep-2014

require_once("lib.php");

# google "time arizona, usa" gives 2 times (due to crossing timezones)

#####################################################################################################

function get_time($location)
{
  $location=trim($location);
  term_echo("*** TIME: http://www.google.com/search?gbv=1&q=time+".urlencode($location));
  $html=wget_ssl("www.google.com.au","/search?gbv=1&q=time+".urlencode($location),ICEWEASEL_UA,"",60);
  $html=strip_headers($html);
  $result="";
  $delim1="<div id=\"ires\">";
  $delim2="</li>";
  $i=strpos($html,$delim1);
  if ($i!==False)
  {
    $html=substr($html,$i);
    $i=strpos($html,$delim2);
    if ($i!==False)
    {
      $html=trim(substr($html,0,$i));
      $html=strip_tags($html);
      while (strpos($html,"  ")!==False)
      {
        $html=str_replace("  "," ",$html);
      }
      if (($html<>"") and (strpos($html,"Time in")!==False))
      {
        $result=substr($html,0,300);
      }
    }
    else
    {
      term_echo("*** TIME: delim2 not found");
    }
  }
  else
  {
    term_echo("*** TIME: delim1 not found");
  }
  return $result;
}

#####################################################################################################

function convert_google_location_time($time)
{
  # 6:00 PM Friday, August 29, 2014 (GMT+10) Time in Traralgon VIC, Australia
  $result=array();
  $parts=explode(" ",$time);
  if (count($parts)<10)
  {
    return False;
  }
  $timestamp=$parts[0];
  for ($i=1;$i<=6;$i++)
  {
    $timestamp=$timestamp." ".$parts[$i];
  }
  $result["timezone"]=substr($parts[6],1,strlen($parts[6])-2);
  for ($i=0;$i<=8;$i++)
  {
    array_shift($parts);
  }
  $result["location"]=implode(" ",$parts);
  $result["timestamp"]=convert_timestamp($timestamp,"g:i A l, F j, Y (TO)");
  return $result;
}

#####################################################################################################

?>
