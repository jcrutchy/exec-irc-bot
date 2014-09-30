<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));

switch ($trailing)
{
  case "meeting":
    $response=wget("soylentnews.org","/");
    $delim1="<!-- begin site_news block -->";
    $delim2="<!-- end site_news block -->";
    $max_len=300;
    $text=extract_text($response,$delim1,$delim2);
    $parts=explode("<hr>",$text);
    $result="";
    for ($i=0;$i<count($parts);$i++)
    {
      if (strpos(strtolower($parts[$i]),"meeting")!==False)
      {
        $result=$parts[$i];
      }
    }
    if ($result<>"")
    {
      term_echo($result);
      $result=strip_tags($result);
      $result=replace_ctrl_chars($result," ");
      $result=str_replace("  "," ",$result);
      if (strlen($result)>$max_len)
      {
        $result=trim(substr($result,0,$max_len))."...";
      }
    }
    else
    {
      require_once("wiki_lib.php");
      $title="Issues to Be Raised at the Next Board Meeting";
      $section="Next meeting";
      $result=get_text($title,$section,True);
      var_dump($result);
      if (is_array($result)==True)
      {
        $result=trim(implode(" ",$text));
      }
      if ($result=="")
      {
        return;
      }
    }
    if (strlen($result)>200)
    {
      $result=trim(substr($result,0,200))."...";
    }
    privmsg(chr(3)."03"."********** ".chr(3)."05".chr(2)."SOYLENTNEWS BOARD MEETING".chr(2).chr(3)."03"." **********");
    privmsg(chr(3)."03".trim($result));
    break;
}

#####################################################################################################

?>
