<?php

# gpl2
# by crutchy
# 9-july-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

define("SED_CHANNELS_BUCKET","<<EXEC_SED_CHANNELS>>");

$channels=get_bucket(SED_CHANNELS_BUCKET);
if ($channels<>"")
{
  $channels=unserialize($channels);
  if ($channels===False)
  {
    $channels=array();
    save_channels($channels);
  }
}
else
{
  $channels=array();
  save_channels($channels);
}
if ($alias=="~sed")
{
  switch (strtolower($trailing))
  {
    case "on":
      if (in_array($dest,$channels)==False)
      {
        $channels[]=$dest;
        save_channels($channels);
        privmsg("exec sed enabled for ".chr(3)."8$dest");
      }
      else
      {
        privmsg("exec sed already enabled for ".chr(3)."8$dest");
      }
      break;
    case "off":
      if (channel_off($channels,$dest)==True)
      {
        privmsg("exec sed disabled for ".chr(3)."8$dest");
      }
      else
      {
        privmsg("exec sed already disabled for ".chr(3)."8$dest");
      }
      break;
  }
}
elseif ($alias=="~sed-internal")
{
  $parts=explode(" ",$trailing);
  $command=strtolower($parts[0]);
  array_shift($parts);
  $msg=implode(" ",$parts);
  switch ($command)
  {
    case "kick":
      if (count($parts)==2)
      {
        if ($parts[1]==NICK_EXEC)
        {
          channel_off($channels,$parts[0]);
          term_echo("channel \"".$parts[0]."\" deleted from ".SED_CHANNELS_BUCKET." because exec was kicked from channel");
        }
      }
      break;
    case "part":
      if ($nick==NICK_EXEC)
      {
        channel_off($channels,$msg);
        term_echo("channel \"".$parts[0]."\" deleted from ".SED_CHANNELS_BUCKET." because exec parted channel");
      }
      break;
    case "privmsg":
      if ($nick<>NICK_EXEC)
      {
        if (in_array($dest,$channels)==True)
        {
          sed($msg,$nick,$dest);
        }
        set_bucket("last_".strtolower($nick)."_".$dest,$msg);
      }
      break;
  }
}
return;

#####################################################################################################

function channel_off(&$channels,$chan)
{
  $i=array_search($chan,$channels);
  if ($i!==False)
  {
    unset($channels[$i]);
    $channels=array_values($channels);
    save_channels($channels);
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function save_channels($channels)
{
  $channels=serialize($channels);
  set_bucket(SED_CHANNELS_BUCKET,$channels);
}

#####################################################################################################

function sed($trailing,$nick,$dest)
{
  # [nick[:] ]s/old/new[/[g]]
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
  # [nick[:] ]s/old/new
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
        if (substr($sed_nick,strlen($sed_nick)-1)==":")
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
    $last=get_bucket($index); # dunno why this is needed but on 2-july-14 it just stopped working (kept showing last message not found) and this was the workaround that seemed to fix
    $last=get_bucket($index);
    if ($last=="")
    {
      privmsg("last message by ".chr(3)."8$sed_nick".chr(3)." not found");
    }
    $action_delim=chr(1)."ACTION ";
    if (strtoupper(substr($last,0,strlen($action_delim)))==$action_delim)
    {
      $last=trim(substr($last,strlen($action_delim)),chr(1));
    }
    if ($replace_all==True)
    {
      $result=str_ireplace($old,$new,$last);
    }
    else
    {
      $result=replace_first($old,$new,$last);
      if ($result===False)
      {
        return;
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
  privmsg("syntax: ".chr(3)."8[nick[:] ]s/old/new[/[g]]");
}

#####################################################################################################

?>
