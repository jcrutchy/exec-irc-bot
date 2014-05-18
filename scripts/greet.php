<?php

# gpl2
# by crutchy
# 18-may-2014

ini_set("display_errors","on");
require_once("lib.php");

# !greet|5|0|0|0|php scripts/greet.php %%trailing%% %%nick%% %%alias%%

$trailing=$argv[1];
$nick=$argv[2];
$alias=$argv[3];

privmsg("$nick: hello $trailing");

?>
