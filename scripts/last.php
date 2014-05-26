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

if ($trailing=="exec.sed.enable")
{
  set_bucket("exec_sed_enabled","yes");
  privmsg("exec sed enabled");
  return;
}
if ($trailing=="exec.sed.disable")
{
  unset_bucket("exec_sed_enabled");
  privmsg("exec sed disabled");
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
  $parts=explode("/",$trailing);
  if ((count($parts)==3) or (count($parts)==4))
  {
    # [nick[:] ]s/old/new[/[g]]
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
    $new=$parts[2];
    $replace_all=False;
    if (count($parts)==4)
    {
      if (strtolower($parts[3])=="g")
      {
        $replace_all=True;
      }
    }
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
      # replace first occurrence only
      $result="";
      $llast=strtolower($last);
      $lold=strtolower($old);
      $n=count($old);
      $i=strpos($llast,$lold);
      if ($i===False)
      {
        term_echo("5");
        return;
      }
      $s1=substr($last,0,$i);
      $s2=substr($last,$i+strlen($old));
      $result=$s1.$new.$s2;
    }
    if ($result<>"")
    {
      privmsg("$sed_nick: $result");
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
