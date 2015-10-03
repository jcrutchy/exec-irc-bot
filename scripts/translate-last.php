<?php

#####################################################################################################

require_once("lib.php");
require_once("translate_lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("syntax: .trans <nick>");
  return;
}

$lang_from="auto";
$lang_to="en";
$index="last_".strtolower($trailing)."_".strtolower($dest);

$msg=get_bucket($index);

if ($msg=="")
{
  privmsg("message by $trailing not found");
  return;
}

$def=translate($lang_from,$lang_to,$msg);

if ($def==$msg)
{
  privmsg("error translating");
  return;
}

if (strlen($def)>500)
{
  $def=substr($def,0,500)."...";
}
if ($def<>"")
{
  privmsg("[google] $msg: $def");
}

#####################################################################################################

?>
