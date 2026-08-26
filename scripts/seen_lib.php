<?php

#####################################################################################################

require_once("lib.php");
require_once("lib_mysql.php");

$items=unserialize(base64_decode($argv[1]));
$trailing=trim($argv[2]);

$params=array("nick"=>$trailing,"dest"=>$items["destination"],"serv"=>$items["server"]);

$sql="SELECT * FROM ".BOT_SCHEMA.".".LOG_TABLE." WHERE ((`cmd`=\"PRIVMSG\") AND (`nick`=:nick) AND (`destination`=:dest) AND (`server`=:serv)) ORDER BY id DESC LIMIT 1";

$records=fetch_prepare($sql,$params);

if (count($records)==0)
{
  privmsg(chr(3).$items["nick"].", $trailing not seen in ".$items["destination"]);
}
else
{
  $delta=microtime(True)-$records[0]["microtime"];
  if ($delta<=0.5)
  {
    privmsg(chr(3).$items["nick"].", $trailing was last seen in ".$items["destination"]." just now with message: ".$records[0]["trailing"]);
  }
  else
  {
    $delta_d=floor($delta/(24*60*60));
    if ($delta_d>0)
    {
      if ($delta_d>1)
      {
        $delta_d="$delta_d days";
      }
      else
      {
        $delta_d="$delta_d day";
      }
    }
    else
    {
      $delta_d="";
    }
    $delta=$delta-$delta_d*24*60*60;
    $delta_h=floor($delta/(60*60));
    if ($delta_h>0)
    {
      if ($delta_d<>"")
      {
        $delta_d=$delta_d.", ";
      }
      if ($delta_h>1)
      {
        $delta_h="$delta_h hours";
      }
      else
      {
        $delta_h="$delta_h hour";
      }
    }
    else
    {
      $delta_h="";
    }
    $delta=$delta-$delta_h*60*60;
    $delta_m=floor($delta/60);
    if ($delta_m>0)
    {
      if ($delta_h<>"")
      {
        $delta_h=$delta_h.", ";
      }
      if ($delta_m>1)
      {
        $delta_m="$delta_m minutes";
      }
      else
      {
        $delta_m="$delta_m minute";
      }
    }
    else
    {
      $delta_m="";
    }
    $delta_s=round($delta-$delta_m*60,0);
    if ($delta_s>0)
    {
      if ($delta_m<>"")
      {
        $delta_m=$delta_m.", ";
      }
      if ($delta_s>1)
      {
        $delta_s="$delta_s seconds";
      }
      else
      {
        $delta_s="$delta_s second";
      }
    }
    else
    {
      $delta_s="";
    }
    if (strlen($records[0]["trailing"])>300)
    {
      $records[0]["trailing"]=trim(substr($records[0]["trailing"],0,300))."...";
    }
    privmsg(chr(3).$items["nick"].", $trailing was last seen in ".$items["destination"]." $delta_d$delta_h$delta_m$delta_s ago with message: ".$records[0]["trailing"]);
  }
}

#####################################################################################################

?>
