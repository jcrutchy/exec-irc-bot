<?php

# gpl2
# by crutchy
# 24-july-2014

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  return;
}

$utc_str=gmdate("H:i, j F Y",time());

$title="SoylentNews:Sandbox";
$section="Suggestions from IRC";

$text=get_text($title,$section,True);

$text=$text."\n<br>* $trailing ~ [[User:$nick|$nick]] @ $utc_str (UTC)";

$msg_success="*** suggestion successfully added to wiki - http://wiki.soylentnews.org/wiki/SoylentNews:Sandbox";
$msg_error="*** error adding suggestion to wiki";

echo "/INTERNAL ~wiki-internal $title||$section||$text||$msg_success||$msg_error\n";

#####################################################################################################

?>
