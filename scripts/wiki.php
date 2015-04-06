<?php

#####################################################################################################

/*
exec:~wiki|40|0|0|0|*||||php scripts/wiki.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~wiki-privmsg|40|0|0|0|crutchy,mrcoolbp||||php scripts/wiki.php %%trailing%% %%dest%% %%nick%% %%alias%%
init:~wiki register-events
*/

#####################################################################################################

# http://www.mediawiki.org/wiki/Manual:Bots
# http://en.wikipedia.org/wiki/Wikipedia:Creating_a_bot

# ~wiki edit title|section|text
# ~wiki edit title|section| (deletes section)

# instead of "~wiki login" & "~wiki get page|section" you just type [[page#section]] to get the page/section

#####################################################################################################

require_once("lib.php");
require_once("wiki_lib.php");

$trailing=trim(strip_tags($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~wiki-privmsg %%trailing%%");
  return;
}

if ($alias=="~wiki-privmsg")
{
  $spamctl=".spamctl";
  if (strtolower(substr($trailing,0,strlen($spamctl)))==$spamctl)
  {
    wiki_spamctl($nick,$trailing);
    return;
  }
  if ($dest=="#wiki")
  {
    return;
  }
  $delim1="[[";
  $delim2="]]";
  if (substr($trailing,0,strlen($delim1))==$delim1)
  {
    $parts=explode($delim2,substr($trailing,strlen($delim1)));
    if (count($parts)<>2)
    {
      return;
    }
    $section="";
    $params=explode("|",$parts[0]);
    $title=$params[0];
    if (count($params)==2)
    {
      $section=$params[1];
    }
    $params=explode("#",$parts[0]);
    if (count($params)==2)
    {
      $title=$params[0];
      $section=$params[1];
    }
    $result=get_text($title,$section,True,True);
    if ($result!==False)
    {
      if (count($result)>=2)
      {
        privmsg(chr(3)."13".$result[0]);
        privmsg(chr(3)."02".$result[count($result)-1]);
      }
    }
  }
  elseif ((strpos($trailing,$delim1)!==False) and (strpos($trailing,$delim2)!==False))
  {
    $i=strpos($trailing,$delim1);
    $j=strpos($trailing,$delim2);
    if ($i<$j)
    {
      $link=substr($trailing,$i+strlen($delim1),$j-$i-strlen($delim2));
      $section="";
      $params=explode("|",$link);
      $title=$params[0];
      if (count($params)==2)
      {
        $section=$params[1];
      }
      $params=explode("#",$link);
      if (count($params)==2)
      {
        $title=$params[0];
        $section=$params[1];
      }
      $title=str_replace(" ","_",$title);
      $url="http://wiki.soylentnews.org/wiki/".urlencode($title);
      if ($section<>"")
      {
        $section=str_replace(" ","_",$section);
        $section=str_replace("~",".7E",$section);
        $section=str_replace("(",".28",$section);
        $section=str_replace(")",".29",$section);
        $url=$url."#$section";
      }
      privmsg(chr(3)."13".$url);
    }
  }
  return;
}

$login=get_bucket("wiki_login_cookieprefix");

if (strtolower($trailing)=="login")
{
  login();
}
elseif ($login<>"")
{
  $parts=explode(" ",$trailing);
  $action=$parts[0];
  switch (strtolower($action))
  {
    case "edit":
      array_shift($parts);
      $trailing=implode(" ",$parts);
      $parts=explode("|",$trailing);
      if (count($parts)<>3)
      {
        privmsg("syntax: ~wiki title|section|text");
        return;
      }
      $title=$parts[0];
      $section=$parts[1];
      $text=$parts[2];
      edit($title,$section,$text);
      break;
    case "get":
      array_shift($parts);
      $trailing=implode(" ",$parts);
      $parts=explode("|",$trailing);
      if ((count($parts)<>1) and (count($parts)<>2))
      {
        privmsg("syntax: ~wiki get title[|section]");
        return;
      }
      $title=$parts[0];
      $section="";
      if (isset($parts[1])==True)
      {
        $section=$parts[1];
      }
      get_text($title,$section);
      break;
    case "logout":
      logout();
      break;
    default:
      privmsg("wiki: no action specified");
  }
}
else
{
  privmsg("wiki: not logged in");
}

#####################################################################################################

?>
