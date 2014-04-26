<?php

# gpl2
# by crutchy
# 26-april-2014

# irciv_map.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

define("CMD_GENERATE","generate");

$buckets["civ"]["maps"]=array();
get_bucket();
$maps=&$buckets["civ"]["maps"];

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

$admin_nicks=array("crutchy");
if (in_array($nick,$admin_nicks)==False)
{
  return;
}

# chan command args
# chan generate stars
# civ generate 1000

$parts=explode(" ",$trailing);

if (count($parts)<2)
{
  irciv__privmsg("insufficient arguments");
  return;
}

$chan=$parts[0];
$cmd=$parts[1];

switch ($cmd)
{
  case CMD_GENERATE:
    if (isset($parts[2])==True)
    {
      $stars=$parts[2];
      if (is_numeric($stars)==True)
      {
        map_generate($chan,$stars);
      }
    }
    break;
}

set_bucket();

#####################################################################################################

function map_generate($chan,$stars)
{
  global $maps;
  $maps[$chan]=array();
  irciv__privmsg("test");
}

#####################################################################################################

?>
