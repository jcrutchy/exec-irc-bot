<?php

#####################################################################################################

function translate($lang_from,$lang_to,$msg)
{
  $html=wget_ssl("translate.google.com","/?sl=".urlencode($lang_from)."&tl=".urlencode($lang_to)."&js=n&ie=UTF-8&text=".urlencode($msg));
  $html=strip_headers($html);
  if ($html===False)
  {
    return "";
  }
  strip_all_tag($html,"head");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html,"<div>");
  $delim1="TRANSLATED_TEXT='";
  $delim2="';";
  $i=strpos($html,$delim1)+strlen($delim1);
  if ($i===False)
  {
    return "";
  }
  $html=substr($html,$i);
  $i=strpos($html,$delim2);
  if ($i===False)
  {
    return "";
  }
  $result=trim(substr($html,0,$i));
  $result=str_replace("\\x26","&",$result);
  $result=html_decode($result);
  $result=html_decode($result);
  return $result;
}

#####################################################################################################

?>
