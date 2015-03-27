<?php

# gpl2
# by crutchy
# 1-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];

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
    $quote=chr(0).chr(0);
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
    $index="<<variable>>_".$dest."_$".$var_name;
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
  $dollar=chr(0).chr(0);
  $tmp=str_replace("\\$",$dollar,$tmp);
  $parts=explode("$",$tmp);
  $msg=$parts[0];
  for ($i=1;$i<count($parts);$i++)
  {
    $tmp_arr=explode(" ",$parts[$i]);
    $var_name=$tmp_arr[0]; # TODO: detect punctuation at end (use character whitelist filter for var_name - put in lib.php)
    $index="<<variable>>_".$dest."_$".$var_name;
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

?>
