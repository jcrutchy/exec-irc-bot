<?php

#####################################################################################################

/*
exec:~confucius|15|0|0|1|||||php scripts/confucius.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

$agent=ICEWEASEL_UA;
$host="www.just-one-liners.com";
$port=80;
if (mt_rand(0,4)==0)
{
  $uri="/";
}
else
{
  $uri="/category/confucius-say-wordplay";
}
$response=wget($host,$uri,$port,$agent);
$delim1="<h2 class=\"title\" id=\"post-";
$delim2="</h2>";
$text=extract_text($response,$delim1,$delim2);
if ($text===False)
{
  return;
}
$i=strpos($text,"<");
if ($i===False)
{
  return;
}
$text=substr($text,$i);
$text=replace_ctrl_chars($text," ");
$text=trim(strip_tags($text));
$text=str_replace("  "," ",$text);
$text=html_entity_decode($text,ENT_QUOTES,"UTF-8");
$text=html_entity_decode($text,ENT_QUOTES,"UTF-8");
$text_len=strlen($text);
$max_text_length=300;
if (strlen($text)>$max_text_length)
{
  $text=trim(substr($text,0,$max_text_length))."...";
}
privmsg($text);

#####################################################################################################

?>
