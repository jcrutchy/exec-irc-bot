<?php

# gpl2
# by crutchy
# 26-june-2014

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

if ($trailing=="")
{
  privmsg("GET:   ~bucket <index>");
  privmsg("SET:   ~bucket <index> <data>");
  privmsg("UNSET: ~bucket <index> unset");
  return;
}

$parts=explode(" ",$trailing);

$index=$parts[0];

if (count($parts)==2)
{
  if ($parts[1]=="unset")
  {
    unset_bucket($index);
    if (get_bucket($index)=="")
    {
      privmsg("unset bucket");
    }
    else
    {
      privmsg("error unsetting bucket");
    }
    return;
  }
}

if (count($parts)>=2)
{
  array_shift($parts);
  $data=implode(" ",$parts);
  set_bucket($index,$data);
  if (get_bucket($index)=="")
  {
    privmsg("error setting bucket");
  }
  else
  {
    privmsg("set bucket");
  }
  return;
}

if (count($parts)==1)
{
  $data=get_bucket($index);
  if ($data=="")
  {
    privmsg("bucket not found");
  }
  else
  {
    privmsg($data);
  }
  return;
}

?>
