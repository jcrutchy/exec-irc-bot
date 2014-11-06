<?php

# gpl2
# by crutchy

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
    if ($target<>NICK_EXEC)
    {
      rawmsg("MODE $dest -o $target");
    }
    break;
  case "~voice":
    rawmsg("MODE $dest +v $target");
    break;
  case "~devoice":
    if ($target<>NICK_EXEC)
    {
      rawmsg("MODE $dest -v $target");
    }
    break;
  case "~kick":
    if (($target<>$nick) and ($target<>NICK_EXEC))
    {
      rawmsg("KICK $dest $target :commanded by $nick");
    }
    break;
}

#####################################################################################################

?>
