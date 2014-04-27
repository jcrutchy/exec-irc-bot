<?php

# gpl2
# by crutchy
# 27-april-2014

# map.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

$cols=140;
$rows=53;
$landmass_count=15;
$landmass_size=150;
$land_spread=200;
$ocean_char="O";
$land_char="L";

$coords=map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char);

map_dump($coords,$cols,$rows);

#####################################################################################################

?>
