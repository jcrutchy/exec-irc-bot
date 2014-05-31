<?php

# gpl2
# by crutchy
# 30-may-2014

/*

chromas, 30-may-2014

function sedSplode($buffer)
# Explodes a sed string into an array and
# accounts for escaped slashes.
{   $l     = strLen($buffer);
    $z     = 0;

    for ($z = 0; $z < $l; $z++)
        if ($buffer[$z] == "/" && $buffer[$z-1] !="\\")
            $buffer[$z] = "\0";
    return explode("\0", str_replace("\/", "/", $buffer));
}

*/

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
if ($sed_enabled=="yes")
{
  return;
}

# SETTING BUCKET VARIABLES IN MESSAGES
$parts=explode("=",$trailing);
if (count($parts)==2)
{
  $var_name="";
  $var_value="";
  $tmp=trim($parts[0]);
  if (substr($tmp,0,1)=="$")
  {
    $tmp=substr($tmp,1);
    $tmp_arr=explode(" ",$tmp);
    if (($tmp<>"") or (count($tmp_arr)>1))
    {
      $var_name=$tmp;
    }
  }
  if ($var_name<>"")
  {
    $tmp=trim($parts[1]);
    $quote=random_string(50);
    $tmp=str_replace("\\\"",$quote,$tmp);
    $tmp_arr=explode("\"",$tmp);
    if (count($tmp_arr)==3)
    {
      if (($tmp_arr[0]=="") and ($tmp_arr[2]==""))
      {
        $tmp=str_replace($quote,"\"",$tmp_arr[1]);
        if ($tmp<>"")
        {
          $var_value=$tmp;
        }
      }
    }
  }
  if (($var_name<>"") and ($var_value<>""))
  {
    $index="<<last.php>>_variable_".$dest."_$".$var_name;
    set_bucket($index,$var_value);
    $msg="variable \"$var_name\" set to value \"$var_value\"";
    term_echo($msg);
    privmsg($msg);
  }
}

# GETTING BUCKET VARIABLES IN MESSAGES
if (substr($trailing,0,2)=="$$")
{
  $tmp=substr($trailing,2);
  $dollar=random_string(50);
  $tmp=str_replace("\\$",$dollar,$tmp);
  $parts=explode("$",$tmp);
  $msg=$parts[0];
  for ($i=1;$i<count($parts);$i++)
  {
    $tmp_arr=explode(" ",$parts[$i]);
    $var_name=$tmp_arr[0]; # TODO: detect punctuation at end (use character whitelist filter for var_name - put in lib.php)
    $index="<<last.php>>_variable_".$dest."_$".$var_name;
    $var_value=get_bucket($index);
    if ($var_value<>"")
    {
      $msg=$msg.$var_value;
      array_shift($tmp_arr);
      $tmp=implode(" ",$tmp_arr);
      $msg=$msg." ".$tmp;
    }
    else
    {
      privmsg("variable \"$var_name\" not set");
      break;
    }
  }
  if ($msg<>$parts[0])
  {
    privmsg($msg);
  }
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
