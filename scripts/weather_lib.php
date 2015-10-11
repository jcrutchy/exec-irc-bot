<?php

# http://openweathermap.org/API

#####################################################################################################

require_once("lib.php");
define("LOCATIONS_FILE","../data/locations.txt");
define("WEATHER_PREFS_FILE","../data/weather_prefs.txt");

#####################################################################################################

function load_locations()
{
  return load_settings(LOCATIONS_FILE);
}

#####################################################################################################

function get_location($name,$nick="")
{
  $locations=load_locations();
  if ($locations===False)
  {
    return False;
  }
  $name=strtolower(trim($name));
  $nick=strtolower(trim($nick));
  if (isset($locations[$name])==True)
  {
    return $locations[$name];
  }
  else
  {
    if ((isset($locations[$nick])==True) and ($name==""))
    {
      return $locations[$nick];
    }
    else
    {
      return False;
    }
  }
}

#####################################################################################################

function del_location($name)
{
  $locations=load_locations();
  if ($locations===False)
  {
    return False;
  }
  $name=strtolower(trim($name));
  if (isset($locations[$name])==False)
  {
    return False;
  }
  unset($locations[$name]);
  return save_locations($locations);
}

#####################################################################################################

function set_location($name,$location)
{
  $locations=load_locations();
  if ($locations===False)
  {
    return False;
  }
  $name=strtolower(trim($name));
  $location=trim($location);
  $locations[$name]=$location;
  return save_locations($locations);
}

#####################################################################################################

function save_locations(&$locations)
{
  return save_settings($locations,LOCATIONS_FILE);
}

#####################################################################################################

function set_location_alias($alias,$trailing)
{
  $parts=explode(" ",$trailing);
  if (count($parts)>1)
  {
    $name=$parts[0];
    array_shift($parts);
    $location=implode(" ",$parts);
    if (set_location($name,$location)==False)
    {
      privmsg("error setting name \"$name\" for location \"$location\"");
    }
    else
    {
      privmsg("name \"$name\" set for location \"$location\"");
    }
  }
  else
  {
    privmsg("syntax: $alias name location (name cannot contain spaces but location can contain spaces)");
  }
}

#####################################################################################################

function get_prefs($nick)
{
  $prefs=load_settings(WEATHER_PREFS_FILE);
  if (isset($prefs[$nick])==True)
  {
    return unserialize($prefs[$nick]);
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function process_weather(&$location,$nick,$getdata=False)
{
  $loc=get_location($location,$nick);
  term_echo("*** WEATHER LOCATION LOOKUP: $loc");
  if ($loc===False)
  {
    if ($location=="")
    {
      return False;
    }
    $loc=$location;
  }
  $location=$loc;
  $loc_query=filter($loc,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC." ");
  $prefs=get_prefs($nick);
  $fheit="1";
  $use_unit_pref=False;
  if ((isset($prefs["unit"])==True) and ($getdata==False))
  {
    if ($prefs["unit"]=="metric")
    {
      $use_unit_pref=True;
      $fheit="0";
    }
    if ($prefs["unit"]=="imperial")
    {
      $use_unit_pref=True;
    }
  }
  # https://www.google.com/search?gbv=1&q=weather+traralgon
  $url="http://www.google.com.au/search?gbv=1&fheit=$fheit&q=weather+".urlencode($loc_query);
  term_echo($url);
  $response=wget("www.google.com.au","/search?gbv=1&fheit=$fheit&q=weather+".urlencode($loc_query),80,ICEWEASEL_UA,"",60);
  $html=strip_headers($response);
  $delim1="<div class=\"e\">";
  $delim2="</table>";
  $html=extract_text($html,$delim1,$delim2);
  if ($html===False)
  {
    return False;
  }
  $html=replace_ctrl_chars($html," ");
  $html=str_replace("  "," ",$html);
  $html=html_decode($html);
  $html=html_decode($html);
  $location=trim(strip_tags(extract_raw_tag($html,"h3")));
  if (substr($location,0,12)=="Weather for ")
  {
    $location=substr($location,12);
  }
  $wind=trim(strip_tags(extract_text_nofalse($html,"style=\"white-space:nowrap;padding-right:15px;color:#666\">Wind: ","</span>")));
  $humidity=extract_text($html,"style=\"white-space:nowrap;padding-right:0px;vertical-align:top;color:#666\">Humidity: ","</td>");
  $parts=explode("<td",$html);
  $temps=array();
  $tempsC=array();
  $conds=array();
  $days=array();
  for ($i=1;$i<count($parts);$i++)
  {
    $cond=extract_text($parts[$i],"alt=\"","\"");
    $temp=extract_text($parts[$i],"<span class=\"wob_t\" style=\"display:inline\">","</span>");
    $day=extract_text($parts[$i],"colspan=\"2\" style=\"vertical-align:top;text-align:center\">","</td>");
    if ($cond!==False)
    {
      $conds[]=strtolower($cond);
    }
    if ($temp!==False)
    {
      $temps[]=$temp;
      $tempsC[]=sprintf("%.0f",(substr($temp,0,strlen($temp)-2)-32)*5/9)."°C";
    }
    if ($day!==False)
    {
      $days[]=$day;
    }
  }
  $offset=0;
  $wind_caption=", wind ".$wind;
  if ($wind=="")
  {
    $offset=1;
    $wind_caption="";
  }
  if ((count($conds)<>5) or (count($temps)<>(10-$offset)) or (count($tempsC)<>(10-$offset)) or (count($days)<>4))
  {
    return False;
  }
  if ($use_unit_pref==False)
  {
    $result=$location." - currently ".$temps[0]." / ".$tempsC[0].", ".$conds[0].$wind_caption.", humidity ".$humidity." - ";
  }
  else
  {
    $result=$location." - currently ".$temps[0].", ".$conds[0].$wind_caption.", humidity ".$humidity." - ";
  }
  $fulldays=array("Sun"=>"Sunday","Mon"=>"Monday","Tue"=>"Tuesday","Wed"=>"Wednesday","Thu"=>"Thursday","Fri"=>"Friday","Sat"=>"Saturday");
  for ($i=1;$i<=4;$i++)
  {
    $day=$days[$i-1];
    $day=$fulldays[$day];
    if ($use_unit_pref==False)
    {
      $result=$result.$day." ".$conds[$i]." (".$temps[$i*2+1-$offset].":".$temps[$i*2-$offset]." / ".$tempsC[$i*2+1-$offset].":".$tempsC[$i*2-$offset].")";
    }
    else
    {
      $result=$result.$day." ".$conds[$i]." (".$temps[$i*2+1-$offset].":".$temps[$i*2-$offset].")";
    }
    if ($i<4)
    {
      $result=$result.", ";
    }
  }
  $result=chr(3)."03".$result;
  if ($getdata<>False)
  {
    $data=array();
    $data["tempF"]=$temps[0];
    $data["tempC"]=$tempsC[0];
    $data["cond"]=$conds[0];
    $data["wind"]=$wind_caption;
    $data["humidity"]=$humidity;
    $data["location"]=$location;
    return $data;
  }
  return $result;
}

#####################################################################################################

function process_weather_old(&$location,$nick)
{
  $loc=get_location($location,$nick);
  if ($loc===False)
  {
    if ($location=="")
    {
      return 0;
    }
    $loc=$location;
  }
  $location=$loc;
  $loc_query=filter($loc,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC.",");
  # http://weather.gladstonefamily.net/site/search?site=melbourne&search=Search
  $search=wget("weather.gladstonefamily.net","/site/search?site=".urlencode($loc_query)."&search=Search",80,ICEWEASEL_UA,"",300);
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
        $csv=trim(wget("weather.gladstonefamily.net","/cgi-bin/wxobservations.pl?site=".urlencode($station)."&days=3",80,ICEWEASEL_UA,"",300));
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
        $results=array();
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
        else
        {
          continue;
        }
        $results["temp_C"]=False;
        $results["temp_F"]=False;
        if ($data_last[2]=="")
        {
          $temp="(no data)";
        }
        else
        {
          $tempF=round($data_last[2],1);
          $tempC=round(($tempF-32)*5/9,1);
          $results["temp_C"]=$tempC;
          $results["temp_F"]=$tempF;
          $temp=$tempC."°C (".$tempF."°F)";
        }
        if ($data_last[1]=="")
        {
          $press="(no data)";
          $delta_str="";
          $pressmb=False;
        }
        else
        {
          $delta_str="";
          if (($dt>0) and ($data_first[1]<>""))
          {
            $d=round($data_last[1]-$data_first[1],1);
            #$delta_str=" ($d mb over ".round($dt*60,0)." mins)";
            $dt=$dt*60;
            if ($d>0)
            {
              $delta_str=" (+$d mb/$dt mins)";
            }
            else
            {
              $delta_str=" ($d mb/$dt mins)";
            }
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
          $dewpoint=$tempC."°C (".$tempF."°F)";
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
          $wind_speed=$wind_speed_kph." km/h (".$wind_speed_mph." mph)";
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
          $age=round($age*60,0);
          $agestr=" ~ $age mins ago:";
        }
        $results["name"]=$name;
        $results["utc"]=$data_last[0];
        $results["utc_num"]=$ts2;
        $results["age"]=$agestr;
        $results["age_num"]=$age;
        $results["temp"]=$temp;
        $results["dewpoint"]=$dewpoint;
        $results["press"]=$press;
        $results["delta_str"]=$delta_str;
        $results["pressmb"]=$pressmb;
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
