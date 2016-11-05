<?php

#####################################################################################################

$exec_cookie_jar=array();

#####################################################################################################

function sn_wiki_rewrite_page($title,$text,$summary)
{
  $data=sn_wiki_wget("/w/api.php?action=query&meta=tokens&type=login&format=php");
  if (isset($data["query"]["tokens"]["logintoken"])==False)
  {
    privmsg("*** wiki: login token error");
    return False;
  }
  $token=$data["query"]["tokens"]["logintoken"];
  $user_params=explode("\n",file_get_contents("../pwd/wiki.bot.passwd"));
  $params=array();
  $params["lgname"]=$user_params[0];
  $params["lgpassword"]=$user_params[1];
  $params["lgtoken"]=$token;
  $data=sn_wiki_wpost("/w/api.php?action=login&format=php",$params);
  if (isset($data["login"]["result"])==True)
  {
    if ($data["login"]["result"]<>"Success")
    {
      privmsg("*** wiki: ".$data["login"]["result"]);
      return False;
    }
  }
  else
  {
    privmsg("*** wiki: login error");
    return False;
  }
  $token=$data["login"]["lgtoken"];
  $data=sn_wiki_wget("/w/api.php?action=query&meta=tokens&type=csrf&format=php");
  if (isset($data["query"]["tokens"]["csrftoken"])==False)
  {
    privmsg("*** wiki: csrf token error");
    return False;
  }
  $token=$data["query"]["tokens"]["csrftoken"];
  $params=array(
    "format"=>"php",
    "title"=>$title,
    "summary"=>$summary,
    "text"=>$text,
    "contentformat"=>"text/x-wiki",
    "contentmodel"=>"wikitext",
    "bot"=>"1",
    "token"=>$token);
  $data=sn_wiki_wpost("/w/api.php?action=edit",$params);
  if (isset($data["edit"]["result"])==True)
  {
    if ($data["edit"]["result"]<>"Success")
    {
      privmsg("*** wiki: ".$data["edit"]["result"]);
    }
    else
    {
      $msg=array();
      foreach ($data["edit"] as $key => $val)
      {
        $msg[]="$key=$val";
      }
      $msg=implode(", ",$msg);
      if ($data["edit"]["result"]<>"Success")
      {
        privmsg("*** wiki: ".$msg);
      }
      sn_wiki_wget("/w/api.php?action=logout&format=php");
      if (isset($data["edit"]["spamblacklist"])==True)
      {
        return $data["edit"]["spamblacklist"];
      }
      return True;
    }
  }
  else
  {
    privmsg("*** wiki: edit error");
  }
  sn_wiki_wget("/w/api.php?action=logout&format=php");
  return False;
}

#####################################################################################################

function sn_wiki_wget($uri)
{
  global $exec_cookie_jar;
  if (count($exec_cookie_jar)>0)
  {
    $headers=array("Cookie"=>implode(";",$exec_cookie_jar));
  }
  else
  {
    $headers="";
  }
  $response=wget("wiki.soylentnews.org",$uri,443,ICEWEASEL_UA,$headers);
  $exec_cookie_jar=array_merge($exec_cookie_jar,exec_get_cookies($response));
  return unserialize(strip_headers($response));
}

#####################################################################################################

function sn_wiki_wpost($uri,$params)
{
  global $exec_cookie_jar;
  if (count($exec_cookie_jar)>0)
  {
    $headers=array("Cookie"=>implode(";",$exec_cookie_jar));
  }
  else
  {
    $headers="";
  }
  $response=wpost("wiki.soylentnews.org",$uri,443,ICEWEASEL_UA,$params,$headers);
  $exec_cookie_jar=array_merge($exec_cookie_jar,exec_get_cookies($response));
  return unserialize(strip_headers($response));
}

#####################################################################################################

?>
