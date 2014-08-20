<?php

# gpl2
# by crutchy
# 20-aug-2014

#####################################################################################################

require_once("lib.php");

/*require_once("wiki_lib.php");
$title="Issues to Be Raised at the Next Board Meeting";
$section="Proposed next meeting";
if (login(True)==False)
{
  return;
}
$text=get_text($title,$section,True);
if ($text!==False)
{
  privmsg(chr(3)."08".$text);
}
logout(True);*/

$response=wget("soylentnews.org","/");

$delim1="<!-- begin site_news block -->";
$delim2="<!-- end site_news block -->";

$max_len=300;

$text=extract_text($response,$delim1,$delim2);

$parts=explode("<hr>",$text);

for ($i=0;$i<count($parts);$i++)
{
  if (strpos(strtolower($parts[$i]),"meeting")!==False)
  {
    $text=$parts[$i];
  }
}

term_echo($text);

$text=strip_tags($text);
$text=replace_ctrl_chars($text," ");
$text=str_replace("  "," ",$text);

if (strlen($text)>$max_len)
{
  $text=trim(substr($text,0,$max_len))."...";
}

privmsg(chr(3)."08".trim($text));


#####################################################################################################

?>
