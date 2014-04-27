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

$cols=50;
$rows=30;

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

switch ($cmd)
{
  case CMD_GENERATE:
    $maps[$dest]["coords"]="";
    map_generate($dest);
    $maps[$dest]["coords"]=gzcompress("LLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLLL");
    break;
  case CMD_DUMP:
    map_dump($dest);
    break;
}

set_bucket();

#####################################################################################################

?>
