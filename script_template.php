<?php

# gpl2
# by crutchy
# 29-may-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

# alias|timeout|repeat|auto-privmsg|empty-trailing-allowed|php scripts/template.php %%trailing%% %%dest%% %%nick%% %%start%% %%alias%% %%cmd%% %%data%% %%exec%% %%params%%
$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$start=$argv[4];
$alias=$argv[5];
$cmd=$argv[6];
$data=$argv[7];
$exec=$argv[8];
$params=$argv[9];

privmsg("hello world");
term_echo("message appears in terminal only");
pm(NICK_EXEC,"message");
err("show this message in terminal and die");
$data=get_bucket("index");
set_bucket("index",$data);
wget($host,$uri,80);

#####################################################################################################

?>
