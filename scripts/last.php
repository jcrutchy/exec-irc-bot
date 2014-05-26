<?php

# gpl2
# by crutchy
# 26-may-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$cmd=$argv[4];

if ($trailing=="exec.sed.enable")
{
  set_bucket("exec_sed_enabled","yes");
  if ($cmd=="PRIVMSG")
  {
    privmsg("exec sed enabled");
  }
  return;
}
if ($trailing=="exec.sed.disable")
{
  unset_bucket("exec_sed_enabled");
  if ($cmd=="PRIVMSG")
  {
    privmsg("exec sed disabled");
  }
  return;
}
$sed_enabled=get_bucket("exec_sed_enabled");
if ($sed_enabled=="yes")
{
  sed($trailing,$nick,$dest);
}
$index="last_".strtolower($nick)."_".$dest;
set_bucket($index,$trailing);

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
  $slash=random_string(50);
  $trailing=str_replace("\/",$slash,$trailing);
  $parts=explode("/",$trailing);
  if (count($parts)==3)
  {
    $start=ltrim($parts[0]);
    if (trim($start)=="")
    {
      term_echo("1");
      return;
    }
    $start_arr=explode(" ",$start);
    $sed_nick="";
    if (count($start_arr)==1)
    {
      if (strtolower($start_arr[0])<>"s")
      {
        term_echo("2");
        return;
      }
    }
    elseif (count($start_arr)==2)
    {
      $sed_nick=$start_arr[0];
      if (substr($sed_nick,strlen($sed_nick)-1)==":")
      {
        $sed_nick=substr($sed_nick,0,strlen($sed_nick)-1);
      }
    }
    else
    {
      term_echo("3");
      return;
    }
    $old=$parts[1];
    if ($old=="")
    {
      sed_help();
      term_echo("4");
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
    if ($replace_all==True)
    {
      $result=str_ireplace($old,$new,$last);
    }
    else
    {
      $result=replace_first($old,$new,$last);
      if ($result===False)
      {
        term_echo("5");
        return;
      }
    }
    if ($result<>"")
    {
      privmsg("$sed_nick: $result");
    }
  }
  else
  {
    term_echo("6");
  }
}

#####################################################################################################

function sed_help()
{
  privmsg("syntax: [nick[:] ]s/old/new[/[g]]");
}

#####################################################################################################

?>
