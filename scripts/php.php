<?php

# gpl2
# by crutchy
# 29-may-2014

# thanks to prospectacle for link to download doc files

ini_set("display_errors","on");
require_once("lib.php");
$msg=$argv[1];
$msg=str_replace("_","-",$msg);
$msg=filter($msg,VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC."-");
$filename="/var/include/vhosts/irciv.us.to/data/php_manual/php-chunked-xhtml/function.".$msg.".html";
if (file_exists($filename)==False)
{
  echo "IRC_MSG function not found\n";
  return;
}
$html=file_get_contents($filename);
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
$syntax=str_replace("  ","",$syntax);
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
