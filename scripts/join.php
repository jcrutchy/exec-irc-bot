<?php

# gpl2
# by crutchy
# 23-aug-2014

require_once("lib.php");

$channel=trim($argv[1]);

if ($channel<>"")
{
  echo "/IRC JOIN $channel\n";
}
else
{
  privmsg("syntax: ~join <channel>");
}

?>
