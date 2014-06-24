<?php

# gpl2
# by crutchy
# 29-may-2014

ini_set("display_errors","on");
require_once("lib.php");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

$ignore_channels=array("#*","#rss-bot");

if (in_array($dest,$ignore_channels)==False)
{
  $msg="$dest <$nick> $trailing";
  echo "IRC_RAW :".NICK_EXEC." PRIVMSG #* :$msg\n";
}

?>
