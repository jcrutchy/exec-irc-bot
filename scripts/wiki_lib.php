<?php

# gpl2
# by crutchy
# 6-sep-2014

define("WIKI_USER_AGENT","IRC-Executive/0.01 (https://github.com/crutchy-/test/blob/master/scripts/wiki.php; jared.crutchfield@hotmail.com)");
define("WIKI_HOST","wiki.soylentnews.org");

#####################################################################################################

function login_cookie($cookieprefix,$sessionid)
{
  return $cookieprefix."_session=".$sessionid;
}

#####################################################################################################

function logout($return=False)
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
    wiki_privmsg($return,"wiki: successfully logged out");
  }
  else
  {
    wiki_privmsg($return,"wiki: logout confirmation not received");
  }
}

#####################################################################################################

function login($return=False)
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
  $msg="wiki: login=".$data["login"]["result"];
  if ($data["login"]["result"]=="Success")
  {
    set_bucket("wiki_login_cookieprefix",$data["login"]["cookieprefix"]);
    set_bucket("wiki_login_sessionid",$data["login"]["sessionid"]);
    $msg=$msg.", username=".$data["login"]["lgusername"]." (userid=".$data["login"]["lguserid"].")";
    wiki_privmsg($return,$msg);
    return True;
  }
  else
  {
    wiki_privmsg($return,$msg);
    return False;
  }
}

#####################################################################################################

function edit($title,$section,$text,$return=False)
{
  if (($title=="") or ($section==""))
  {
    wiki_privmsg($return,"wiki: edit=invalid title/section");
    return False;
  }
  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    wiki_privmsg($return,"wiki: edit=not logged in");
    return False;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));
  $uri="/w/api.php?action=tokens&format=php";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  var_dump($data);
  if (isset($data["tokens"]["edittoken"])==False)
  {
    wiki_privmsg($return,"wiki: edit=error getting edittoken");
    return False;
  }
  $token=$data["tokens"]["edittoken"];
  $uri="/w/api.php?action=parse&format=php&page=".urlencode($title)."&prop=sections";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["parse"]["sections"])==False)
  {
    wiki_privmsg($return,"wiki: edit=error getting sections for page \"".$title."\"");
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
      wiki_privmsg($return,"wiki: edit=section not found");
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
    wiki_privmsg($return,"wiki: edit=".$data["error"]["code"]);
    return False;
  }
  else
  {
    $msg="wiki: edit=".$data["edit"]["result"];
    if ($data["edit"]["result"]=="Success")
    {
      $msg=$msg.", oldrevid=".$data["edit"]["oldrevid"].", newrevid=".$data["edit"]["newrevid"];
    }
    wiki_privmsg($return,$msg);
    return True;
  }
}

#####################################################################################################

function get_text($title,$section,$return=False,$return_lines_array=False)
{
  if ($title=="")
  {
    wiki_privmsg($return,"wiki: edit=invalid title");
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
      wiki_privmsg($return,"wiki: edit=error getting sections for page \"".$title."\"");
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
    if ($section<>"")
    {
      $id=str_replace(" ","_",$section);
      if ($section[0]=="~")
      {
        $id=".7E".substr($section,1);
      }
      $head="<span class=\"mw-headline\" id=\"$id\">$section</span>";
      if (strpos($text,$head)===False)
      {
        wiki_privmsg($return,"wiki: edit=section span not found");
        return False;
      }
    }
  }
  else
  {
    wiki_privmsg($return,"wiki: edit=section not found");
    return False;
  }
  strip_comments($text);
  strip_all_tag($text,"h2");
  strip_all_tag($text,"h3");
  $text=strip_tags($text);
  $text=trim($text," \t\n\r\0\x0B\"");
  $br=random_string(30);
  $text=str_replace("\n",$br,$text);
  $text=replace_ctrl_chars($text," ");
  $text=html_decode($text);
  if ($return_lines_array==False)
  {
    $text=str_replace($br," ",$text);
    if (strlen($text)>400)
    {
      $text=trim(substr($text,0,400))."...";
    }
    bot_ignore_next();
    wiki_privmsg($return,$text);
    $result=$text;
  }
  else
  {
    $result=explode($br,$text);
    for ($i=0;$i<count($result);$i++)
    {
      $result[$i]=trim($result[$i]);
      if (strlen($result[$i])>300)
      {
        $result[$i]=trim(substr($result[$i],0,300))."...";
      }
    }
    delete_empty_elements($result);
  }
  return $result;
}

#####################################################################################################

function wiki_privmsg($return,$msg)
{
  if ($return==False)
  {
    privmsg($msg);
  }
  else
  {
    term_echo($msg);
  }
}

#####################################################################################################

?>
