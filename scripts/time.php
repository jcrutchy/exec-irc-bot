<?php

#####################################################################################################

/*
exec:~time|60|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
exec:~time-add|10|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
exec:~time-del|10|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
exec:~time-prefs|10|0|0|1|||||php scripts/time.php %%alias%% %%trailing%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");
require_once("time_lib.php");
require_once("weather_lib.php");
date_default_timezone_set("UTC");

$alias=$argv[1];
$trailing=trim($argv[2]);
$nick=strtolower(trim($argv[3]));

switch ($alias)
{
  case "~time-prefs":
    if ($trailing=="")
    {
      privmsg("syntax: ~time-prefs pref value");
      privmsg("format => format string per http://php.net/manual/en/function.date.php");
      return;
    }
    $parts=explode(" ",$trailing);
    delete_empty_elements($parts);
    $pref=$parts[0];
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    $prefs=load_settings(TIME_PREFS_FILE);
    if ($prefs===False)
    {
      $prefs=array();
    }
    if (isset($prefs[$nick])==True)
    {
      $nick_prefs=unserialize($prefs[$nick]);
    }
    switch ($pref)
    {
      case "format":
        break;
      default:
        privmsg("  error: unknown pref");
        return;
    }
    $nick_prefs[$pref]=$trailing;
    $prefs[$nick]=serialize($nick_prefs);
    if (save_settings($prefs,TIME_PREFS_FILE)==True)
    {
      privmsg("  successfully saved prefs");
    }
    break;
  case "~time-add":
    set_location_alias($alias,$trailing);
    break;
  case "~time-del":
    if (del_location($trailing)==True)
    {
      privmsg("location \"$trailing\" deleted");
    }
    else
    {
      if (trim($trailing)<>"")
      {
        privmsg("location for \"$trailing\" not found");
      }
      else
      {
        privmsg("syntax: ~time-del <name>");
      }
    }
    break;
  case "~time":
    $loc=get_location($trailing,$nick);
    if ($loc===False)
    {
      if ($trailing=="")
      {
        privmsg("syntax: ~time location");
        privmsg("time data courtesy of Google");
        return;
      }
      $loc=$trailing;
    }
    term_echo("*** TIME LOCATION: $loc");
    $result=get_time($loc);
    if ($result<>"")
    {
      $prefs=get_prefs($nick,TIME_PREFS_FILE);
      $arr=convert_google_location_time($result);
      $format="l, j F Y @ g:i a";
      if (isset($prefs["format"])==True)
      {
        if (trim($prefs["format"])<>"")
        {
          $format=$prefs["format"];
        }
      }
      privmsg(date($format,$arr["timestamp"])." ".$arr["timezone"]." - ".$arr["location"]);
    }
    else
    {
      privmsg("location not found - UTC timestamp: ".date("l, j F Y, g:i a"));
    }
    break;
}

#####################################################################################################

?>
