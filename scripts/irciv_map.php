<?php

# gpl2
# by crutchy
# 27-april-2014

# irciv_map.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

define("CMD_GENERATE","generate");
define("CMD_DUMP","dump");

irciv__term_echo("civ-map running...");

$bucket["civ"]["maps"]=array();
get_bucket();
$maps=&$bucket["civ"]["maps"];

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

$admin_nicks=array("crutchy");
if (in_array($nick,$admin_nicks)==False)
{
  return;
}

# civ-map generate
# civ-map dump

$parts=explode(" ",$trailing);

$cmd=$parts[0];

$cols=140;
$rows=53;

switch ($cmd)
{
  case CMD_GENERATE:
    $landmass_count=15;
    $landmass_size=150;
    $land_spread=200;
    $ocean_char="O";
    $land_char="L";
    $coords=map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char);
    $maps[$dest]["coords"]=$coords;
    irciv__term_echo("map coords generated for channel \"$dest\"");
    break;
  case CMD_DUMP:
    if (isset($maps[$dest]["coords"])==True)
    {
      $coords=$maps[$dest]["coords"];
      map_dump($coords,$cols,$rows);
    }
    else
    {
      irciv__term_echo("map coords for channel \"$dest\" not found in bucket");
    }
    return;
}

set_bucket();

#####################################################################################################

?>
