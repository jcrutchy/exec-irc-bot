<?php

# gpl2
# by crutchy
# 24-july-2014

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  return;
}

$utc_str=gmdate("H:i, j F Y",time());

$title="SoylentNews:Sandbox";
$section="Suggestions from IRC";

$text=trim(get_text($title,$section,True));
if (($text<>"") and ($text!==False))
{
  $lines=explode("\n",$text);
  $nlines=array();
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    if ($line=="")
    {
      continue;
    }
    $parts=explode("~",$line);
    if (count($parts)<2)
    {
      $nlines[]=$line;
      continue;
    }
    $sig=trim($parts[count($parts)-1]);
    unset($parts[count($parts)-1]);
    $sug=trim(implode("~",$parts));
    $parts=explode("@",$sig);
    if (count($parts)<>2)
    {
      $nlines[]=$line;
      continue;
    }
    $nic=trim($parts[0]);
    $utc=trim($parts[1]);
    $nlines[]="$sug ~ [[User:$nic|$nic]] @ $utc";
  }
  $text=implode("\n* ",$nlines);
  $text="* ".$text;
}
$text=$text."\n* $trailing ~ [[User:$nick|$nick]] @ $utc_str (UTC)";

var_dump($text);
return;

$msg_success="*** suggestion successfully added to wiki - http://wiki.soylentnews.org/wiki/SoylentNews:Sandbox";
$msg_error="*** error adding suggestion to wiki";

if (login(True)==False)
{
  privmsg($msg_error);
  return;
}
if (edit($title,$section,$text,True)==False)
{
  privmsg($msg_error);
}
else
{
  privmsg($msg_success);
}
logout(True);

#####################################################################################################

?>
