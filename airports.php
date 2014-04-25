<?php

# gpl2
# by crutchy
# 14-april-2014
# http://openflights.org/data.html
# https://sourceforge.net/p/openflights/code/HEAD/tree/openflights/data/airports.dat?format=raw

set_time_limit(0);
ini_set("display_errors","on");
$dat=file_get_contents("../data/airports.dat");
$lines=explode("\n",$dat);
echo count($lines)."\n";
$codes=array();
$code_index_iata=4;
$code_index_icao=5;
$city_index=2;
$country_index=3;
for ($i=0;$i<count($lines);$i++)
{
  $line=trim($lines[$i]); # 1,"Goroka","Goroka","Papua New Guinea","GKA","AYGA",-6.081689,145.391881,5282,10,"U"
  if ($line=="")
  {
    continue;
  }
  $parts=explode(",",$line);
  if (count($parts)>4)
  {
    $location=trim(substr($parts[$city_index],1,strlen($parts[$city_index])-2)." ".substr($parts[$country_index],1,strlen($parts[$country_index])-2));
    if ($location=="")
    {
      continue;
    }
    $code_iata=trim(substr($parts[$code_index_iata],1,strlen($parts[$code_index_iata])-2));
    if ($code_iata<>"")
    {
      $codes[$code_iata]=$location;
      echo "CODE \"$code_iata\" ADDED FOR LOCATION \"$location\"\n";
    }
    $code_icao=trim(substr($parts[$code_index_icao],1,strlen($parts[$code_index_icao])-2));
    if ($code_icao<>"")
    {
      $codes[$code_icao]=$location;
      echo "CODE \"$code_icao\" ADDED FOR LOCATION \"$location\"\n";
    }
  }
}
file_put_contents("weather.codes",serialize($codes));

?>
