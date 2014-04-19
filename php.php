<?php

# gpl2
# by crutchy
# 18-april-2014

# 5|0|0|php|php php.php %%msg%%

# thanks to prospectacle for link to download doc files

$msg=$argv[1];
$msg=str_replace("_","-",$msg);
$html=file_get_contents("/var/www/slash/git/test/php_manual/php-chunked-xhtml/function.".$msg.".html");
$delim1="<div class=\"methodsynopsis dc-description\">";
$delim2="<p class=\"para rdfs-comment\">";
$i=strpos($html,$delim1);
if ($i===False)
{
  echo "delim1 not found\n";
  echo "privmsg script error\n";
  return;
}
$i=$i+strlen($delim1);
$html=substr($html,$i);
$i=strpos($html,$delim2);
if ($i===False)
{
  echo "delim2 not found\n";
  echo "privmsg script error\n";
  return;
}
$syntax=trim(strip_tags(substr($html,0,$i)));
$syntax=str_replace("\n","",$syntax);
$syntax=str_replace("  "," ",$syntax);
if (strlen($syntax)<500)
{
  if ($syntax=="")
  {
    echo "privmsg unable to find syntax\n";
  }
  else
  {
    echo "privmsg $syntax\n";
  }
}

?>
