<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~wiki|40|0|0|0|crutchy,mrcoolbp|||0|php scripts/wiki.php %%trailing%% %%dest%% %%nick%% %%alias%%
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

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($alias=="~wiki-internal")
{
  $parts=explode("||",$trailing);
  if (count($parts)<>5)
  {
    return;
  }
  $title=$parts[0];
  $section=$parts[1];
  $text=$parts[2];
  $msg_success=$parts[3];
  $msg_error=$parts[4];
  if (login()===True)
  {
    privmsg($msg_success);
  }
  else
  {
    privmsg($msg_error);
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
