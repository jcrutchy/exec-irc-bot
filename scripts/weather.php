<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

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

require_once("lib.php");
require_once("weather_lib.php");
require_once("time_lib.php");

$alias=$argv[1];
$trailing=$argv[2];
switch ($alias)
{
  case "~weather-add":
    set_location_alias($alias,$trailing);
    break;
  case "~weather":
    $location=trim($argv[2]);
    if ($location<>"")
    {
      $data=process_weather($location);
      if (is_array($data)==False)
      {
        switch ($data)
        {
          case 1:
            privmsg("weather for \"$location\" not found. check spelling or try another nearby location.");
            break;
          case 2:
            privmsg("all stations matching \"$location\" are either inactive or have no data. check spelling or try another nearby location.");
            break;
        }
      }
      else
      {
        $color="10";
        privmsg("weather for ".chr(2).chr(3).$color.$data["name"].chr(3).chr(2)." at ".$data["utc"]." (UTC)".$data["age"]);
        privmsg("temp: ".chr(2).chr(3).$color.$data["temp"].chr(3).chr(2).", dp: ".chr(2).chr(3).$color.$data["dewpoint"].chr(3).chr(2).", press: ".chr(2).chr(3).$color.$data["press"].chr(3).chr(2).", humid: ".chr(2).chr(3).$color.$data["humidity"].chr(3).chr(2).", wind: ".chr(2).chr(3).$color.$data["wind_speed"].chr(3).chr(2)." @ ".chr(2).chr(3).$color.$data["wind_direction"].chr(3).chr(2));
      }
    }
    else
    {
      privmsg("syntax: ~weather location");
      privmsg("weather data courtesy of the APRS Citizen Weather Observer Program (CWOP) @ http://weather.gladstonefamily.net/");
    }
    break;
}

#####################################################################################################

?>
