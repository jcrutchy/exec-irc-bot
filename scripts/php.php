<?php

# gpl2
# by crutchy
# 15-may-2014

# thanks to prospectacle for link to download doc files

$msg=$argv[1];
$msg=str_replace("_","-",$msg);
$html=file_get_contents("/nas/server/git/data/php_manual/php-chunked-xhtml/function.".$msg.".html");
$delim1="<div class=\"methodsynopsis dc-description\">";
$delim2="<p class=\"para rdfs-comment\">";
$i=strpos($html,$delim1);
if ($i===False)
{
  echo "delim1 not found\n";
  echo "IRC_MSG script error\n";
  return;
}
$i=$i+strlen($delim1);
$html=substr($html,$i);
$i=strpos($html,$delim2);
if ($i===False)
{
  echo "delim2 not found\n";
  echo "IRC_MSG script error\n";
  return;
}
$syntax=trim(strip_tags(substr($html,0,$i)));
$syntax=str_replace("\n","",$syntax);
$syntax=str_replace("  "," ",$syntax);
if (strlen($syntax)<500)
{
  if ($syntax=="")
  {
    echo "IRC_MSG unable to find syntax\n";
  }
  else
  {
    echo "IRC_MSG $syntax\n";
  }
}

?>
