<?php

# gpl2
# by crutchy
# 27-june-2014

# http://www.mediawiki.org/wiki/Manual:Bots
# http://en.wikipedia.org/wiki/Wikipedia:Creating_a_bot

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

define("WIKI_USER_AGENT","IRC-Executive/0.01 (https://github.com/crutchy-/test/blob/master/scripts/wiki.php; jared.crutchfield@hotmail.com)");
define("WIKI_HOST","wiki.soylentnews.org");

$cookie=get_bucket("wiki_login_cookieprefix");

if ($cookie=="")
{
  switch (strtolower($trailing))
  {
    case "login":
      login();
      break;
    case "logout":
      logout();
      break;
    default:
      privmsg("not logged in");
  }
}
else
{
  $parts=explode(" ",$trailing);
  $action=$parts[0];
  switch (strtolower($action))
  {
    case "edit":
      edit();
      break;
    default:
      privmsg($cookie);
  }
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
    privmsg("successfully logged out of wiki");
  }
  else
  {
    privmsg("logout confirmation not received");
  }
  return;
}

#####################################################################################################

function login()
{
  $user_params=explode("\n",file_get_contents("../pwd/wiki.bot"));
  $params["lgname"]=$user_params[0];
  $params["lgpassword"]=$user_params[1];
  $response=wpost(WIKI_HOST,"/w/api.php?action=login&format=php",80,WIKI_USER_AGENT,$params);
  $data=unserialize(strip_headers($response));
  $headers["Cookie"]=login_cookie($data["login"]["cookieprefix"],$data["login"]["sessionid"]);
  $params["lgtoken"]=$data["login"]["token"];
  $response=wpost(WIKI_HOST,"/w/api.php?action=login&format=php",80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  $msg="wiki login=".$data["login"]["result"];
  if ($data["login"]["result"]=="Success")
  {
    $msg=$msg.", username=".$data["login"]["lgusername"]." (userid=".$data["login"]["lguserid"].")";
  }
  set_bucket("wiki_login_cookieprefix",$data["login"]["cookieprefix"]);
  set_bucket("wiki_login_sessionid",$data["login"]["sessionid"]);
  privmsg($msg);
}

#####################################################################################################

function edit()
{

  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    privmsg("edit: not logged in");
    return;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));

  $uri="/w/api.php?action=tokens&format=php";

  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);

  $data=unserialize(strip_headers($response));

  if (isset($data["tokens"]["edittoken"])==False)
  {
    privmsg("edit: error getting edittoken");
    return;
  }

  $token=$data["tokens"]["edittoken"];

  $uri="/w/api.php?action=edit";

  # http://www.mediawiki.org/wiki/API:Edit#Parameters
  $params=array(
    "format"=>"php",
    "title"=>"Sandbox",
    #"section"=>"new",
    #"sectiontitle"=>"exec test",
    "section"=>"exec test",
    "summary"=>"exec test",
    "text"=>"trying something else",
    #"appendtext"=>"Hello world!",
    "bot"=>"",
    "token"=>$token);

  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);

  $data=unserialize(strip_headers($response));

  $msg="wiki edit=".$data["edit"]["result"];
  if ($data["edit"]["result"]=="Success")
  {
    $msg=$msg.", oldrevid=".$data["edit"]["oldrevid"].", newrevid=".$data["edit"]["newrevid"];
  }

  var_dump($data);
}

#####################################################################################################

?>
