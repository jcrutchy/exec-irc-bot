<?php

# gpl2
# by crutchy
# 18-may-2014

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

$index="last_".$nick."_".$dest;

$last_trailing=get_bucket($index);

if ($last_trailing<>"")
{
  # call any other scripts that require last quote
  #echo ":$nick NOTICE $dest :mackey $last_trailing\n";
}

set_bucket($index,$trailing);

?>
