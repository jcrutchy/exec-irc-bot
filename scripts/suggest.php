<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

$trailing=trim(strip_tags($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$alias=trim(strtolower($argv[4]));

$wiki_url="http://sylnt.us/suggest";
if ($alias=="~suggest-api")
{
  $wiki_url="http://sylnt.us/suggest-api";
}

if ($trailing=="")
{
  privmsg("syntax: ~suggest <suggestion>");
  privmsg($wiki_url);
  return;
}

$utc_str=gmdate("H:i, j F Y",time());

$title="Suggestions";
if ($alias=="~suggest-api")
{
  $title="SN API ideas";
}

$section="Suggestions from IRC";

$lines=get_text($title,$section,True,True);
if (is_array($lines)==True)
{
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

$msg_success="*** suggestion successfully added to wiki - $wiki_url";
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
