<?php

#####################################################################################################

/*
exec:~translate|10|0|0|1|||||php scripts/translate.php %%trailing%% %%alias%%
exec:~translate-sl|10|0|0|1|||||php scripts/translate.php %%trailing%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("translate_lib.php");

$trailing=$argv[1];
$alias=$argv[2];

if ($trailing=="")
{
  privmsg("syntax: ~translate <msg>");
  privmsg("        ~translate-sl <source-lang> <larget-lang> <msg>");
  return;
}

if ($alias=="~translate")
{
  $lang_from="auto";
  $lang_to="en";
  $msg=$trailing;
}
else
{
  $parts=explode(" ",$trailing);
  if (count($parts)<3)
  {
    privmsg("  translate-sl error: insufficient parameters");
    return;
  }
  $lang_from=$parts[0];
  $lang_to=$parts[1];
  array_shift($parts);
  array_shift($parts);
  $msg=implode(" ",$parts);
}

$def=translate($lang_from,$lang_to,$msg);

if (strlen($def)>500)
{
  $def=substr($def,0,500)."...";
}
if ($def<>"")
{
  privmsg("[google] $msg ($lang_from -> $lang_to): $def");
}

#####################################################################################################

?>
