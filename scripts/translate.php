<?php

# gpl2
# by crutchy
# 5-june-2014

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$alias=$argv[2];

$parts=explode(" ",$trailing);
if (count($parts)<2)
{
  privmsg("syntax: ~translate <from> <to> <msg>");
  privmsg("eg (translate \"test\" from English to Spanish): ~translate en es test");
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

translate($lang_from,$lang_to,$msg);

#####################################################################################################

function translate($lang_from,$lang_to,$msg)
{
  # https://translate.google.com/?sl=en&tl=es&js=n&ie=UTF-8&text=test (thanks to TheMightyBuzzard for URL)
  $html=wget_ssl("translate.google.com","/?sl=".urlencode($lang_from)."&tl=".urlencode($lang_to)."&js=n&ie=UTF-8&text=".urlencode($msg));
  $html=strip_headers($html);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html,"<div>");
  $delim1="<div dir=\"ltr\" style=\"zoom:1\"><div id=\"tts_button\" style=\"display:none\" class=\"\"></div>";
  $delim2="</div></div>";
  $i=strpos($html,$delim1)+strlen($delim1);
  $html=substr($html,$i);
  $i=strpos($html,$delim2);
  $def=trim(substr($html,0,$i));
  if (strlen($def)>700)
  {
    $def=substr($def,0,700)."...";
  }
  if ($def<>"")
  {
    privmsg("[google translate] $msg ($lang_from -> $lang_to): $def");
  }
}

#####################################################################################################

?>
