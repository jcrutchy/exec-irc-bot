<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

require_once("lib.php");
define("CODES_FILE","../data/weather.codes");

#####################################################################################################

function load_codes()
{
  if (file_exists(CODES_FILE)==False)
  {
    term_echo("*** LOCATION CODES FILE NOT FOUND ***");
    return False;
  }
  $codes=file_get_contents(CODES_FILE);
  if ($codes===False)
  {
    return False;
  }
  $codes=unserialize($codes);
  if ($codes===False)
  {
    return False;
  }
  return $codes;
}

#####################################################################################################

function get_location($code)
{
  $codes=load_codes();
  if ($codes===False)
  {
    return False;
  }
  $code=strtolower(trim($code));
  if (isset($codes[$code])==True)
  {
    return $codes[$code];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function set_location($code,$location)
{
  $codes=load_codes();
  if ($codes===False)
  {
    return False;
  }
  $code=strtolower(trim($code));
  $location=trim($location);
  $codes[$code]=$location;
  if (file_put_contents(CODES_FILE,serialize($codes))===False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function set_location_alias($alias,$trailing)
{
  $parts=explode(" ",$trailing);
  if (count($parts)>1)
  {
    $code=$parts[0];
    array_shift($parts);
    $location=implode(" ",$parts);
    if (set_location($code,$location)==False)
    {
      privmsg("error setting code \"$code\" for location \"$location\"");
    }
    else
    {
      privmsg("code \"$code\" set for location \"$location\"");
    }
  }
  else
  {
    privmsg("syntax: $alias code location (code cannot contain spaces but location can contain spaces)");
  }
}

#####################################################################################################

function process_weather(&$location)
{
  $loc=get_location($location);
  if ($loc===False)
  {
    $loc=$location;
  }
  $location=$loc;
  $loc_query=filter($loc,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC.",");
  # http://weather.gladstonefamily.net/site/search?site=melbourne&search=Search
  $search=wget("weather.gladstonefamily.net","/site/search?site=".urlencode($loc_query)."&search=Search",80,ICEWEASEL_UA,"",8);
  if (strpos($search,"Pick one of the following")===False)
  {
    return 1;
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
          $ts1=convert_timestamp($data_first[0],"Y-m-d H:i:s");
          $ts2=convert_timestamp($data_last[0],"Y-m-d H:i:s");
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
            $delta_str=" ($d mb over $dt hrs)";
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
        $results["name"]=$name;
        $results["utc"]=$data_last[0];
        $results["age"]=$agestr;
        $results["temp"]=$temp;
        $results["dewpoint"]=$dewpoint;
        $results["press"]=$press;
        $results["humidity"]=$relhumidity;
        $results["wind_speed"]=$wind_speed;
        $results["wind_direction"]=$wind_direction;
        return $results;
      }
    }
  }
  return 2;
}

#####################################################################################################

?>
