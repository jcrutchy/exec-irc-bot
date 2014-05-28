<?php

# gpl2
# by crutchy
# 23-april-2014

# requested by kobach: weather request, Spirit Of Saint Louis, Missouri (38.7°N/90.7°W), Updated: 1:54 PM CST (December 23, 2013), Conditions: Mostly Cloudy, Temperature: 26°F (-3.3°C), Windchill: 16°F (-9°C), High/Low: 26/9°F (-3.3/-12.8°C), UV: 1/16, Humidity: 66%, Dew Point: 16°F (-8.9°C), Pressure: 30.51 in/1033 hPa, Wind: WNW at 10 MPH (17 KPH)

# http://wxqa.com/APRSWXNETStation.txt
# EW4841|E4841|EW4841 Murrumbena                    AU|45|  -37.90783|145.07217|GMT|||1||||
# http://www.wxqa.com/cgi-bin/search1.cgi?keyword=EW4841
# http://www.findu.com/cgi-bin/wx.cgi?call=EW4841&units=metric

# http://www.worldweather.org/
# http://www.wmo.int/pages/prog/www/index_en.html
# http://www.wmo.int/pages/prog/www/ois/ois-home.html

# TODO: registered nick personalised settings (units, default location, private msg, formatting, etc)
# TODO: delete codes

#####################################################################################################

define("CODES_FILE","../data/weather.codes");
define("SEDBOT_EXCLUDE_PREFIX","for ");
ini_set("display_errors","on");
require_once("lib.php");
if (file_exists(CODES_FILE)==False)
{
  term_echo("WEATHER: CODES FILE NOT FOUND");
  return;
}
$codes=unserialize(file_get_contents(CODES_FILE));
$parts=explode(" ",$argv[2]);
switch ($argv[1])
{
  case "weather-add":
    if (count($parts)>1)
    {
      $code=trim($parts[0]);
      array_shift($parts);
      $location=trim(implode(" ",$parts));
      $codes[$code]=$location;
      if (file_put_contents(CODES_FILE,serialize($codes))===False)
      {
        privmsg("code \"$code\" set for location \"".$codes[$code]."\" but there was an error writing the codes file");
      }
      else
      {
        privmsg("code \"$code\" set for location \"".$codes[$code]."\"");
      }
    }
    break;
  case "weather":
    $location=trim($argv[2]);
    if ($location<>"")
    {
      if (strtolower(substr($location,0,strlen(SEDBOT_EXCLUDE_PREFIX)))<>SEDBOT_EXCLUDE_PREFIX)
      {
        process_weather($location);
      }
    }
    else
    {
      privmsg("IRC WEATHER INFORMATION BOT");
      privmsg("  usage: \"weather location\" (visit http://wiki.soylentnews.org/wiki/IRC:exec#Weather_script for more info)");
      privmsg("  data courtesy of the APRS Citizen Weather Observer Program (CWOP) @ http://weather.gladstonefamily.net/");
      privmsg("  by crutchy: https://github.com/crutchy-/test/blob/master/scripts/weather.php");
    }
    break;
}

#####################################################################################################

function process_weather($location)
{
  global $codes;
  if (isset($codes[$location])==True)
  {
    $loc=$codes[$location];
  }
  else
  {
    $loc=$location;
  }
  # http://weather.gladstonefamily.net/site/search?site=melbourne&search=Search
  $search=wget("weather.gladstonefamily.net","/site/search?site=".urlencode($loc)."&search=Search",80);
  if (strpos($search,"Pick one of the following")===False)
  {
    privmsg("Weather for \"$loc\" not found. Check spelling or try another nearby location.");
    return;
  }
  $parts=explode("<li>",$search);
  $delim1="/site/";
  $delim2="\">";
  $delim3="</a>";
  for ($i=0;$i<count($parts);$i++)
  {
    if ((strpos($parts[$i],"/site/")!==False) and (strpos($parts[$i],"[no data]")===False) and (strpos($parts[$i],"[inactive]")===False))
    {
      term_echo($parts[$i]);
      $j1=strpos($parts[$i],$delim1);
      $j2=strpos($parts[$i],$delim2);
      $j3=strpos($parts[$i],$delim3);
      if (($j1!==False) and ($j2!==False) and ($j3!==False))
      {
        $name=substr($parts[$i],$j2+strlen($delim2),$j3-$j2-strlen($delim2));
        $station=substr($parts[$i],$j1+strlen($delim1),$j2-$j1-strlen($delim1));
        # http://weather.gladstonefamily.net/cgi-bin/wxobservations.pl?site=94868&days=7
        $csv=trim(wget("weather.gladstonefamily.net","/cgi-bin/wxobservations.pl?site=".urlencode($station)."&days=3",80));
        $lines=explode("\n",$csv);
        # UTC baro-mb temp°F dewpoint°F rel-humidity-% wind-mph wind-deg
        # 2014-04-07 17:00:00,1020.01,54.1,53.6,98,0,0,,,,,,
        $first=$lines[count($lines)-2];
        $last=$lines[count($lines)-1];
        term_echo($last);
        $data_first=explode(",",$first);
        $data_last=explode(",",$last);
        if (($data_last[1]=="") or ($data_last[2]=="") or (count($data_first)<7) or (count($data_last)<7))
        {
          continue;
        }
        $dt=0;
        $age=-1;
        if (($data_first[0]<>"") and ($data_last[0]<>""))
        {
          # 2014-04-12 23:00:00
          $date_arr1=date_parse_from_format("Y-m-d H:i:s",$data_first[0]);
          $date_arr2=date_parse_from_format("Y-m-d H:i:s",$data_last[0]);
          $ts1=mktime($date_arr1["hour"],$date_arr1["minute"],$date_arr1["second"],$date_arr1["month"],$date_arr1["day"],$date_arr1["year"]);
          $ts2=mktime($date_arr2["hour"],$date_arr2["minute"],$date_arr2["second"],$date_arr2["month"],$date_arr2["day"],$date_arr2["year"]);
          $dt=round(($ts2-$ts1)/60/60,1);
          $utc_str=gmdate("M d Y H:i:s",time());
          $utc=strtotime($utc_str);
          $age=round(($utc-$ts2)/60/60,1);
        }
        if ($data_last[2]=="")
        {
          $temp="(no data)";
        }
        else
        {
          $tempF=round($data_last[2],1);
          $tempC=round(($tempF-32)*5/9,1);
          $temp=$tempF."°F (".$tempC."°C)";
        }
        if ($data_last[1]=="")
        {
          $press="(no data)";
        }
        else
        {
          $delta_str="";
          if (($dt>0) and ($data_first[1]<>""))
          {
            $d=round($data_last[1]-$data_first[1],1);
            $delta_str=" ~ change of $d mb over past $dt hrs"; # TODO: remove "past"
          }
          $pressmb=round($data_last[1],1);
          $press=$pressmb." mb".$delta_str;
        }
        if ($data_last[3]=="")
        {
          $dewpoint="(no data)";
        }
        else
        {
          $tempF=round($data_last[3],1);
          $tempC=round(($data_last[3]-32)*5/9,1);
          $dewpoint=$tempF."°F (".$tempC."°C)";
        }
        if ($data_last[3]=="")
        {
          $dewpoint="(no data)";
        }
        else
        {
          $tempF=round($data_last[3],1);
          $tempC=round(($tempF-32)*5/9,1);
          $dewpoint=$tempF."°F (".$tempC."°C)";
        }
        if ($data_last[4]=="")
        {
          $relhumidity="(no data)";
        }
        else
        {
          $relhumidity=round($data_last[4],1)."%";
        }
        if ($data_last[5]=="")
        {
          $wind_speed="(no data)";
        }
        else
        {
          $wind_speed_mph=round($data_last[5],1);
          $wind_speed_kph=round($data_last[5]*8/5,1);
          $wind_speed=$wind_speed_mph." mph (".$wind_speed_kph." km/h)";
        }
        if ($data_last[6]=="")
        {
          $wind_direction="(no data)";
        }
        else
        {
          $wind_direction=round($data_last[6],1)."°"; # include N/S/E/W/NE/SE/NW/SW/NNE/ENE/SSE/ESE/etc
        }
        $agestr=":";
        if ($age>=0)
        {
          $agestr=" ~ $age hrs ago:";
        }
        privmsg("Weather for $name at ".$data_last[0]." (UTC)$agestr");
        privmsg("    temperature = $temp    dewpoint = $dewpoint");
        privmsg("    barometric pressure = $press    relative humdity = $relhumidity");
        privmsg("    wind speed = $wind_speed    wind direction = $wind_direction");
        return;
      }
    }
  }
  privmsg("All stations matching \"$loc\" are either inactive or have no data. Check spelling or try another nearby location.");
}

#####################################################################################################

?>
