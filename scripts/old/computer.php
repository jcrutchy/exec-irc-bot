<?php

# gpl2
# by crutchy
# 19-may-2014

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

if (mt_rand(0,4)==0)
{
  return;
}

$msg=strtolower($trailing);

$parts=explode(" ",$msg);

$cmd=$parts[0];
unset($parts[0]);
$arg=implode(" ",$parts);

switch ($cmd)
{
  case "execute":
    privmsg("$nick: executing $arg");
    break;
  default:
    privmsg("i'm sorry $nick but i'm afraid i can't do that");
}

?>
