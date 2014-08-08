<?php

# gpl2
# by crutchy
# 3-aug-2014

require_once("lib.php");

#####################################################################################################

function get_time($location)
{
  $location=trim($location);
  $html=wget_ssl("www.google.com","/search?gbv=1&q=time+".urlencode($location));
  $html=strip_headers($html);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html,"<div>");
  var_dump($html);
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

function convert_google_location_time($time)
{
  # 9:07pm Sunday (EST) - Time in Traralgon VIC, Australia
  $result=array();
  $parts=explode(" ",$time);
  if (count($parts)<7)
  {
    return False;
  }
  $result["time"]=$parts[0];
  $result["day"]=$parts[1];
  $result["timezone"]=substr($parts[2],1,strlen($parts[2])-2);
  for ($i=0;$i<6;$i++)
  {
    array_shift($parts);
  }
  $result["location"]=implode(" ",$parts);
  $day1=date("N"); # 1 (for Monday) through 7 (for Sunday)
  $day2=date("N",strtotime($result["day"]));
  $delta=$day2-$day1;
  if ($delta<-1)
  {
    $delta=$delta+7;
  }
  if ($delta>1)
  {
    $delta=$delta-7;
  }
  $timestamp=time()+$delta*24*60*60;
  $parts[0];
  $y=date("Y",$timestamp);
  $m=date("n",$timestamp);
  $d=date("j",$timestamp);
  $arr=date_parse_from_format("g:ia",$result["time"]);
  $result["timestamp"]=mktime($arr["hour"],$arr["minute"],0,$m,$d,$y);
  return $result;
}

#####################################################################################################

?>
