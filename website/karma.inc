<?php

#####################################################################################################

define("KARMA_FILE",__DIR__."../data/karma.txt");

$fn="../data/".$filename;
if (file_exists($fn)==False)
{
  die("karma file not found");
}
$data=file_get_contents($fn);
$lines=explode("\n",$data);

$body="";

$page=file_get_contents(__DIR__."/karma_template");
$page=str_replace("%%body%%",$body,$page);
echo $page;

#####################################################################################################

?>