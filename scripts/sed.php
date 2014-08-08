<?php

# gpl2
# by crutchy
# 8-aug-2014

#####################################################################################################

require_once("lib.php");
require_once("switches.php");

$trailing=rtrim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_SED_CHANNELS>>","~sed","~sed-internal",$msg);
switch ($flag)
{
  case 0:
    break;
  case 1:
    privmsg("sed enabled for ".chr(3)."10$dest");
    break;
  case 2:
    privmsg("sed already enabled for ".chr(3)."10$dest");
    break;
  case 3:
    privmsg("sed disabled for ".chr(3)."10$dest");
    break;
  case 4:
    privmsg("sed already disabled for ".chr(3)."10$dest");
    break;
  case 5:
    # bot was kicked from channel
    break;
  case 6:
    # bot parted channel
    break;
  case 7:
    sed($msg,$nick,$dest);
    set_bucket("last_".strtolower($nick)."_".$dest,$msg);
    break;
  case 8:
    set_bucket("last_".strtolower($nick)."_".$dest,$msg);
    break;
}

#####################################################################################################

function sed($trailing,$nick,$dest)
{
  # [nick[:|,|>|.] ]s/old/new[/[g]]
  $replace_all=False;
  if (substr(strtolower($trailing),strlen($trailing)-2)=="/g")
  {
    $trailing=substr($trailing,0,strlen($trailing)-2);
    $replace_all=True;
  }
  if (substr($trailing,strlen($trailing)-1)=="/")
  {
    $trailing=substr($trailing,0,strlen($trailing)-1);
  }
  # [nick[:|,|>|.] ]s/old/new
  $slash=chr(0).chr(0);
  $trailing=str_replace("\/",$slash,$trailing);
  $parts=explode("/",$trailing);
  if (count($parts)==3)
  {
    $start=ltrim($parts[0]);
    if (trim($start)=="")
    {
      return;
    }
    $start_arr=explode(" ",$start);
    $sed_nick="";
    if (count($start_arr)==1)
    {
      if (strtolower($start_arr[0])<>"s")
      {
        return;
      }
    }
    elseif (count($start_arr)==2)
    {
      if (strtolower($start_arr[1])=="s")
      {
        $sed_nick=$start_arr[0];
        if (strpos(":,>.",substr($sed_nick,strlen($sed_nick)-1))!==False)
        {
          $sed_nick=substr($sed_nick,0,strlen($sed_nick)-1);
        }
      }
      else
      {
        return;
      }
    }
    else
    {
      return;
    }
    $old=$parts[1];
    if ($old=="")
    {
      sed_help();
      return;
    }
    $new=$parts[2];
    $old=str_replace($slash,"/",$old);
    $new=str_replace($slash,"/",$new);
    if ($sed_nick=="")
    {
      $sed_nick=$nick;
    }
    $index="last_".strtolower($sed_nick)."_".$dest;
    $last=get_bucket($index);
    if ($last=="")
    {
      privmsg("last message by \"$sed_nick\" not found");
    }
    $action_delim=chr(1)."ACTION ";
    if (strtoupper(substr($last,0,strlen($action_delim)))==$action_delim)
    {
      $last=trim(substr($last,strlen($action_delim)),chr(1));
    }
    if ($replace_all==True)
    {
      #$result=str_ireplace($old,$new,$last);
      $result=preg_replace("/".$old."/",$new,$last);
    }
    else
    {
      /*$result=replace_first($old,$new,$last);
      if ($result===False)
      {
        return;
      }*/
      $result=preg_replace("/".$old."/",$new,$last,1);
      if ($result==$last)
      {
        $result="";
      }
    }
    if ($result<>"")
    {
      if ($nick==$sed_nick)
      {
        privmsg("<$sed_nick> $result");
      }
      else
      {
        privmsg("<$nick> <$sed_nick> $result");
      }
    }
    else
    {
      sed_help();
    }
  }
}

#####################################################################################################

function sed_help()
{
  privmsg("syntax: ".chr(3)."8[nick[:|,|>|.] ]s/pattern/replace[/[g]]");
}

#####################################################################################################

?>
