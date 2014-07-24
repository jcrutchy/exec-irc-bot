<?php

# gpl2
# by crutchy
# 24-july-2014

# http://www.mediawiki.org/wiki/Manual:Bots
# http://en.wikipedia.org/wiki/Wikipedia:Creating_a_bot

# ~wiki edit title|section|text
# ~wiki edit title|section| (deletes section)

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

define("WIKI_USER_AGENT","IRC-Executive/0.01 (https://github.com/crutchy-/test/blob/master/scripts/wiki.php; jared.crutchfield@hotmail.com)");
define("WIKI_HOST","wiki.soylentnews.org");

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
        privmsg("syntax: ~wiki title[|section]");
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

function login_cookie($cookieprefix,$sessionid)
{
  return $cookieprefix."_session=".$sessionid;
}

#####################################################################################################

function logout()
{
  $response=wget(WIKI_HOST,"/w/api.php?action=logout&format=php",80);
  $lines=explode("\n",$response);
  $loggedout=False;
  for ($i=0;$i<count($lines);$i++)
  {
    if ((substr($lines[$i],0,strlen("Set-Cookie"))=="Set-Cookie") and (strpos($lines[$i],"LoggedOut")!==False))
    {
      $loggedout=True;
    }
  }
  unset_bucket("wiki_login_cookieprefix");
  unset_bucket("wiki_login_sessionid");
  if ($loggedout==True)
  {
    wiki_privmsg("wiki: successfully logged out");
  }
  else
  {
    wiki_privmsg("wiki: logout confirmation not received");
  }
}

#####################################################################################################

function login()
{
  global $alias;
  $user_params=explode("\n",file_get_contents("../pwd/wiki.bot"));
  $params["lgname"]=$user_params[0];
  $params["lgpassword"]=$user_params[1];
  $response=wpost(WIKI_HOST,"/w/api.php?action=login&format=php",80,WIKI_USER_AGENT,$params);
  $data=unserialize(strip_headers($response));
  $headers["Cookie"]=login_cookie($data["login"]["cookieprefix"],$data["login"]["sessionid"]);
  $params["lgtoken"]=$data["login"]["token"];
  $response=wpost(WIKI_HOST,"/w/api.php?action=login&format=php",80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  $msg="wiki: login=".$data["login"]["result"];
  if ($data["login"]["result"]=="Success")
  {
    $msg=$msg.", username=".$data["login"]["lgusername"]." (userid=".$data["login"]["lguserid"].")";
  }
  set_bucket("wiki_login_cookieprefix",$data["login"]["cookieprefix"]);
  set_bucket("wiki_login_sessionid",$data["login"]["sessionid"]);
  wiki_privmsg($msg);
  if ($alias=="~wiki-internal")
  {
    global $title;
    global $section;
    global $text;
    return edit($title,$section,$text);
  }
}

#####################################################################################################

function edit($title,$section,$text)
{
  if (($title=="") or ($section==""))
  {
    wiki_privmsg("wiki: edit=invalid title/section");
    return False;
  }
  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    wiki_privmsg("wiki: edit=not logged in");
    return False;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));
  $uri="/w/api.php?action=tokens&format=php";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  var_dump($data);
  if (isset($data["tokens"]["edittoken"])==False)
  {
    wiki_privmsg("wiki: edit=error getting edittoken");
    return False;
  }
  $token=$data["tokens"]["edittoken"];
  $uri="/w/api.php?action=parse&format=php&page=".urlencode($title)."&prop=sections";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["parse"]["sections"])==False)
  {
    wiki_privmsg("wiki: edit=error getting sections for page \"".$title."\"");
    return False;
  }
  var_dump($data);
  $sections=$data["parse"]["sections"];
  $index=-1;
  for ($i=0;$i<count($sections);$i++)
  {
    $line=$sections[$i]["line"];
    if ($section==$line)
    {
      $index=$i;
      break;
    }
  }
  if ($index<0)
  {
    $index="new";
  }
  else
  {
    if (isset($sections[$index]["index"])==False)
    {
      wiki_privmsg("wiki: edit=section not found");
      return False;
    }
    $index=$sections[$index]["index"];
    if ($text<>"")
    {
      $text="==$section==\n$text";
    }
  }
  $uri="/w/api.php?action=edit";
  # http://www.mediawiki.org/wiki/API:Edit#Parameters
  $params=array(
    "format"=>"php",
    "title"=>$title,
    "section"=>$index,
    "summary"=>$section,
    "text"=>$text,
    "contentformat"=>"text/x-wiki",
    "contentmodel"=>"wikitext",
    "bot"=>"",
    "token"=>$token);
  var_dump($params);
  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  var_dump($data);
  if (isset($data["error"])==True)
  {
    wiki_privmsg("wiki: edit=".$data["error"]["code"]);
    return False;
  }
  else
  {
    $msg="wiki: edit=".$data["edit"]["result"];
    if ($data["edit"]["result"]=="Success")
    {
      $msg=$msg.", oldrevid=".$data["edit"]["oldrevid"].", newrevid=".$data["edit"]["newrevid"];
    }
    wiki_privmsg($msg);
    logout();
    return True;
  }
}

#####################################################################################################

function get_text($title,$section,$return=False)
{
  if ($title=="")
  {
    if ($return==False)
    {
      privmsg("wiki: edit=invalid title");
    }
    return False;
  }
  $index=-1;
  if ($section<>"")
  {
    $uri="/w/api.php?action=parse&format=php&page=".urlencode($title)."&prop=sections";
    $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT);
    $data=unserialize(strip_headers($response));
    if (isset($data["parse"]["sections"])==False)
    {
      if ($return==False)
      {
        privmsg("wiki: edit=error getting sections for page \"".$title."\"");
      }
      return False;
    }
    $sections=$data["parse"]["sections"];
    for ($i=0;$i<count($sections);$i++)
    {
      $line=$sections[$i]["line"];
      if (strtolower($section)==strtolower($line))
      {
        $index=$sections[$i]["index"];
        break;
      }
    }
  }
  $uri="/w/api.php?action=parse&format=php&page=".urlencode($title)."&prop=text";
  if ($index>0)
  {
    $uri=$uri."&section=$index";
  }
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT);
  $data=unserialize(strip_headers($response));
  if (isset($data["parse"]["text"]["*"])==True)
  {
    $text=$data["parse"]["text"]["*"];
  }
  else
  {
    if ($return==False)
    {
      privmsg("wiki: edit=section not found");
    }
    return False;
  }
  strip_comments($text);
  strip_all_tag($text,"h2");
  $text=strip_tags($text);
  $text=trim($text," \t\n\r\0\x0B\"");
  if ($return==False)
  {
    privmsg($text);
  }
  return $text;
}

#####################################################################################################

function wiki_privmsg($msg)
{
  global $alias;
  if ($alias<>"~wiki-internal")
  {
    privmsg($msg);
  }
}

#####################################################################################################

?>
