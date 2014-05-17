<?php

# gpl2
# by crutchy
# 17-may-2014

ini_set("display_errors","on");

require_once("lib.php");

$host=$argv[1];

$result=wget($host,"/",80);

$lines=explode("\n",$result);

privmsg("$host: ".trim($lines[0]));

?>
