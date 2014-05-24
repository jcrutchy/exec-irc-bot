<?php

# gpl2
# by crutchy
# 24-may-2014

# wiki.php

# http://www.mediawiki.org/wiki/Manual:Bots
# http://en.wikipedia.org/wiki/Wikipedia:Creating_a_bot

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");
$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$user_agent="IRC-Executive/0.01 (https://github.com/crutchy-/test; jared.crutchfield@hotmail.com)";

if ($trailing=="logout")
{
  $response=wget("wiki.soylentnews.org","/w/api.php?action=logout&format=php",80);
  $lines=explode("\n",$response);
  for ($i=0;$i<count($lines);$i++)
  {
    if ((substr($lines[$i],0,strlen("Set-Cookie"))=="Set-Cookie") and (strpos($lines[$i],"LoggedOut")!==False))
    {
      privmsg("logged out of wiki");
    }
    else
    {
      privmsg("logout not detected");
    }
  }
  return;
}
$cookie=get_bucket("wiki_login_cookie");
if (($cookie=="") or ($trailing=="login"))
{
  $user_params=explode("\n",file_get_contents("../data/wiki.bot"));
  $params["lgname"]=$user_params[0];
  $params["lgpassword"]=$user_params[1];
  $response=wpost("wiki.soylentnews.org","/w/api.php?action=login&format=php",80,$user_agent,$params);
  $data=unserialize(strip_headers($response));
  $headers["Cookie"]=login_cookie($data["login"]["cookieprefix"],$data["login"]["sessionid"]);
  $params["lgtoken"]=$data["login"]["token"];
  $response=wpost("wiki.soylentnews.org","/w/api.php?action=login&format=php",80,$user_agent,$params,$headers);
  $data=unserialize(strip_headers($response));
  $msg="wiki login=".$data["login"]["result"];
  if ($data["login"]["result"]=="Success")
  {
    $msg=$msg.", username=".$data["login"]["lgusername"]." (userid=".$data["login"]["lguserid"].")";
  }
  privmsg($msg);
  set_bucket("wiki_login_cookieprefix",$headers["cookieprefix"]);
  set_bucket("wiki_login_sessionid",$headers["sessionid"]);
}
else
{
  privmsg($cookie);
}

#####################################################################################################

function login_cookie($cookieprefix,$sessionid)
{
  return $cookieprefix."_session=".$sessionid;
}

#####################################################################################################

?>
