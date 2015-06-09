<?php

#####################################################################################################

/*
exec:~translate|10|0|0|0|||||php scripts/translate.php %%trailing%% %%alias%%
exec:~translate-sl|10|0|0|0|||||php scripts/translate.php %%trailing%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("translate_lib.php");

$trailing=$argv[1];
$alias=$argv[2];

$parts=explode(" ",$trailing);
if (count($parts)<2)
{
  privmsg("syntax: ~translate <larget-lang> <msg>");
  privmsg("eg (translate \"test\" from English to Spanish): ~translate es test");
  privmsg("see also: ~translate-sl <source-lang> <larget-lang> <msg>");
  privmsg("eg (translate \"test\" from Spanish to English): ~translate-sl es en prueba");
  return;
}
if ($alias=="~translate")
{
  $lang_from="auto";
  $lang_to=$parts[0];
}
else
{
  $lang_from=$parts[0];
  $lang_to=$parts[1];
  array_shift($parts);
}
array_shift($parts);
$msg=implode(" ",$parts);

$def=translate($lang_from,$lang_to,$msg);

if (strlen($def)>500)
{
  $def=substr($def,0,500)."...";
}
if ($def<>"")
{
  privmsg("[google translate] $msg ($lang_from -> $lang_to): $def");
}

#####################################################################################################

?>
