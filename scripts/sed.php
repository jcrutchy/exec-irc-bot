<?php

# gpl2
# by crutchy
# 2-july-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$default_channels=array("#test","#");

$channels=get_bucket("<<EXEC_SED_CHANNELS>>");
term_echo("### <<EXEC_SED_CHANNELS>> = \"$channels\" ###");
if ($channels=="")
{
  $channels=$default_channels;
}
else
{
  $channels=unserialize($channels);
  if ($channels===False)
  {
    $channels=$default_channels;
  }
}
set_bucket("<<EXEC_SED_CHANNELS>>",serialize($channels));
if ($alias=="~sed")
{
  switch (strtolower($trailing))
  {
    case "on":
      if (in_array($dest,$channels)==False)
      {
        $channels[]=$dest;
        set_bucket("<<EXEC_SED_CHANNELS>>",serialize($channels));
        privmsg("exec sed enabled for \"$dest\"");
      }
      else
      {
        privmsg("exec sed already enabled for \"$dest\"");
        term_echo("\"$dest\" already in <<EXEC_SED_CHANNELS>> bucket");
      }
      return;
    case "off":
      $i=array_search($dest,$channels);
      if ($i!==False)
      {
        unset($channels[$i]);
        $channels=array_values($channels);
        set_bucket("<<EXEC_SED_CHANNELS>>",serialize($channels));
        privmsg("exec sed disabled for \"$dest\"");
      }
      else
      {
        privmsg("exec sed already disabled for \"$dest\"");
        term_echo("\"$dest\" not found in <<EXEC_SED_CHANNELS>> bucket");
      }
      return;
  }
}

if (($nick<>NICK_EXEC) and ($alias=="~sed-internal"))
{
  $sedbot_channels=get_bucket("SedBot_channel_list");
  if ((strpos($sedbot_channels,$dest)===False) and (in_array($dest,$channels)==True))
  {
    sed($trailing,$nick,$dest);
  }
  set_bucket("last_".strtolower($nick)."_".$dest,$trailing);
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
      privmsg("last message by \"$sed_nick\" not found");
    }
    # ACTION kicks chromas to the kerb
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
  privmsg("syntax: [nick[:] ]s/old/new[/[g]]");
}

#####################################################################################################

?>
