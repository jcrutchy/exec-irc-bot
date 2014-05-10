<?php

# gpl2
# by crutchy
# 10-may-2014

# ~bucket|5|0|1|php scripts/bucket.php %%trailing%% %%nick%% %%dest%%

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

$parts=explode(" ",$trailing);

$index=$parts[0];

if (count($parts)==2)
{
  $data=$parts[1];
  set_bucket($index,$data);
  return;
}

if (count($parts)==1)
{
  $data=get_bucket($index);
  if ($data=="")
  {
    privmsg("bucket data empty");
  }
  else
  {
    privmsg($data);
  }
  return;
}

?>
