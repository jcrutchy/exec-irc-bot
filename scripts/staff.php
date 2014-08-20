<?php

# gpl2
# by crutchy
# 21-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));

switch ($trailing)
{
  case "meeting":
    /*require_once("wiki_lib.php");
    $title="Issues to Be Raised at the Next Board Meeting";
    $section="Proposed next meeting";
    $text=get_text($title,$section,True);
    if (is_array($text)==True)
    {
      privmsg(chr(3)."08".$text);
    }*/
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
    break;
}

#####################################################################################################

?>
