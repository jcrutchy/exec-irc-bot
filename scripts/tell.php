<?php

#####################################################################################################

/*
exec:~tell|10|0|0|1|||||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
exec:~tell-internal|10|0|0|1||INTERNAL|||php scripts/tell.php %%trailing%% %%dest%% %%nick%% %%alias%% %%server%%
init:~tell-internal register-events
*/

#####################################################################################################

ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
date_default_timezone_set("UTC");

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=strtolower($argv[3]);
$alias=$argv[4];
$server=$argv[5];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~tell-internal %%trailing%%");
  return;
}

$fn=DATA_PATH."tell_data";

if ($alias=="~tell")
{
  if ($trailing=="")
  {
    privmsg("syntax: ~tell <nick> <message>");
    return;
  }
  if (file_exists($fn)==True)
  {
    $data=json_decode(file_get_contents($fn),True);
  }
  else
  {
    $data=array();
  }
  $parts=explode(" ",$trailing);
  $target=strtolower($parts[0]);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  $save_data=False;
  if (isset($data[$server])==False)
  {
    $data[$server]=array();
    $save_data=True;
  }
  if (isset($data[$server][$nick]["ignore"])==False)
  {
    $data[$server][$nick]["ignore"]=array();
    $save_data=True;
  }
  if (isset($data[$server][$nick]["messages"])==False)
  {
    $data[$server][$nick]["messages"]=array();
    $save_data=True;
  }
  if ($target==">ignore")
  {
    if (in_array($trailing,$data[$server][$nick]["ignore"])==False)
    {
      $data[$server][$nick]["ignore"][]=$trailing;
      $save_data=True;
      notice($nick,"added nick \"$trailing\" to ~tell ignore list for $nick");
    }
    else
    {
      notice($nick,"nick \"$trailing\" already in ~tell ignore list for $nick");
    }
  }
  elseif ($target=="<ignore")
  {
    $index=array_search($trailing,$data[$server][$nick]["ignore"],True);
    if ($index!==False)
    {
      unset($data[$server][$nick]["ignore"][$index]);
      $save_data=True;
      notice($nick,"deleted nick \"$trailing\" from ~tell ignore list for $nick");
    }
    else
    {
      notice($nick,"nick \"$trailing\" not found in ~tell ignore list for $nick");
    }
  }
  else
  {
    if (isset($data[$server][$target]["ignore"])==False)
    {
      $data[$server][$target]["ignore"]=array();
      $save_data=True;
    }
    if (isset($data[$server][$target]["messages"])==False)
    {
      $data[$server][$target]["messages"]=array();
      $save_data=True;
    }
    $index=array_search($nick,$data[$server][$target]["ignore"],True);
    if ($index===False)
    {
      $data[$server][$target]["messages"][]=$target.", at ".date("Y-m-d H:i:s",microtime(True))." (UTC), ".$nick." left message from ".$dest.": ".$trailing;
      notice($nick,"message saved. i'll pm $target next time they say something");
      $save_data=True;
    }
    else
    {
      notice($nick,"$target has chosen to ignore messages from you");
    }
  }
  if ($save_data==True)
  {
    if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
    {
      pm("crutchy","error writing ~tell data file");
      #pm("#debug","error writing ~tell data file");
    }
  }
  return;
}
if ($alias=="~tell-internal")
{
  if (file_exists($fn)==True)
  {
    $data=json_decode(file_get_contents($fn),True);
    if (isset($data[$server][$nick]["messages"])==True)
    {
      if (count($data[$server][$nick]["messages"])>0)
      {
        for ($i=0;$i<count($data[$server][$nick]["messages"]);$i++)
        {
          notice($nick,$data[$server][$nick]["messages"][$i]);
        }
        $data[$server][$nick]["messages"]=array();
        if (file_put_contents($fn,json_encode($data,JSON_PRETTY_PRINT))===False)
        {
          pm("crutchy","error writing ~tell data file");
          #pm("#debug","error writing ~tell data file");
        }
      }
    }
  }
}

#####################################################################################################

?>
