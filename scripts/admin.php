<?php

# gpl2
# by crutchy
# 30-aug-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$target=$nick;
if ($trailing<>"")
{
  $target=$trailing;
}

switch ($alias)
{
  case "~op":
    rawmsg("MODE $dest +o $target");
    break;
  case "~deop":
    rawmsg("MODE $dest -o $target");
    break;
  case "~voice":
    rawmsg("MODE $dest +v $target");
    break;
  case "~devoice":
    rawmsg("MODE $dest -v $target");
    break;
}

#####################################################################################################

?>
