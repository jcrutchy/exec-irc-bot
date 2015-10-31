<?php

#####################################################################################################

define("WIKI_USER_AGENT","exec-irc-bot (https://github.com/crutchy-/exec-irc-bot)");
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
  #var_dump($data);
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
  #var_dump($data);
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
  #var_dump($params);
  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  #var_dump($data);
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
    wiki_privmsg($return,"wiki: get_text=invalid title");
    return False;
  }
  $index=-1;
  $title=str_replace(" ","_",$title);
  if ($section<>"")
  {
    $uri="/w/api.php?action=parse&format=php&page=".urlencode($title)."&prop=sections";
    $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT);
    $data=unserialize(strip_headers($response));
    if (isset($data["parse"]["sections"])==False)
    {
      wiki_privmsg($return,"wiki: get_text=error getting sections for page \"".$title."\"");
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
  /*$url="http://".WIKI_HOST.$uri;
  $url=get_redirected_url($url);
  if (get_host_and_uri($url,&$host,&$uri,&$port)==False)
  {
    wiki_privmsg($return,"wiki: get_text=url parse failed");
    return False;
  }*/
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT);
  $data=unserialize(strip_headers($response));
  if (isset($data["parse"]["text"]["*"])==True)
  {
    $text=$data["parse"]["text"]["*"];
    if ($section<>"")
    {
      $id=str_replace(" ","_",$section);
      $id=str_replace("~",".7E",$id);
      $id=str_replace("(",".28",$id);
      $id=str_replace(")",".29",$id);
      $head="<span class=\"mw-headline\" id=\"$id\">$section</span>";
      if (strpos($text,$head)===False)
      {
        wiki_privmsg($return,"wiki: get_text=section span not found");
        return False;
      }
    }
  }
  else
  {
    wiki_privmsg($return,"wiki: get_text=section not found");
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
  $text=clean_text($text);
  $url="http://wiki.soylentnews.org/wiki/".urlencode($title);
  if ($section<>"")
  {
    $url=$url."#$id";
  }
  if ($return_lines_array==False)
  {
    $text=str_replace($br," ",$text);
    $text=clean_text($text);
    if (strlen($text)>400)
    {
      $text=trim(substr($text,0,400))."...";
    }
    bot_ignore_next();
    wiki_privmsg($return,$text);
    wiki_privmsg($return,$url);
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
    $result[]=$url;
  }
  return $result;
}

#####################################################################################################

function wiki_privmsg($return,$msg)
{
  if ($return==False)
  {
    privmsg(chr(3)."13".$msg);
  }
  else
  {
    term_echo(chr(3)."13".$msg);
  }
}

#####################################################################################################

function wiki_spamctl($nick,$trailing,$bypass_auth=False)
{
  if ($bypass_auth==False)
  {
    $account=users_get_account($nick);
    $allowed=array("crutchy","chromas","mrcoolbp");
    if (in_array($account,$allowed)==False)
    {
      privmsg("  error: not authorized");
      return;
    }
  }
  $title=trim(substr($trailing,strlen(".spamctl")));
  if ($title=="last")
  {
    $wikirc_last=get_bucket("last_wikirc_#wiki");
    $delim1=chr(3)."14[[".chr(3)."07";
    $delim2=chr(3)."14]]";
    $title=extract_text_nofalse($wikirc_last,$delim1,$delim2);
  }
  if ($title=="")
  {
    privmsg("  syntax: .spamctl <page title>");
    return;
  }
  if (strtolower(substr($title,0,4))=="http")
  {
    # TODO: ACCEPT WIKI URLS
  }
  $text="{{spam}}";
  if (login(True)==False)
  {
    privmsg("  login error");
    return;
  }
  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    privmsg("  not logged in");
    return;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));
  $uri="/w/api.php?action=tokens&format=php";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["tokens"]["edittoken"])==False)
  {
    privmsg("  error getting edittoken");
    logout(True);
    return;
  }
  $token=$data["tokens"]["edittoken"];
  $uri="/w/api.php?action=edit";
  $params=array(
    "format"=>"php",
    "title"=>$title,
    "text"=>$text,
    "contentformat"=>"text/x-wiki",
    "contentmodel"=>"wikitext",
    "bot"=>"",
    "token"=>$token);
  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["error"])==True)
  {
    privmsg("  error: ".$data["error"]["code"]);
  }
  else
  {
    $msg=$data["edit"]["result"];
    if ($data["edit"]["result"]=="Success")
    {
      if ((isset($data["edit"]["oldrevid"])==True) and (isset($data["edit"]["newrevid"])==True))
      {
        $msg=$msg.", oldrevid=".$data["edit"]["oldrevid"].", newrevid=".$data["edit"]["newrevid"];
      }
    }
    privmsg("  $msg");
    $title=str_replace(" ","_",$title);
    privmsg("  http://wiki.soylentnews.org/wiki/".urlencode($title));
  }
  logout(True);
}

#####################################################################################################

# 14[[07The Meaning Of Chopsticks In Chinese Food Culture14]]4 !N10 02http://wiki.soylentnews.org/w/index.php?oldid=9223&rcid=13180 5* 03Trey18553770 5* (+3741) 10Created page with "<br><br>I remember that, after i was little, there were lots of riddles for children. My grandma used to ask me time to time considered one of them in particular: 'There are t..."

function wiki_autospamctl($trailing)
{
  $spam_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
  delete_empty_elements($list,True);
  $test_title=trim(extract_text_nofalse($trailing,"14[[07","14]]"));
  $test_nick=trim(extract_text_nofalse($trailing," 5* 03"," 5*"));
  if (($test_title=="") or ($test_nick==""))
  {
    return;
  }
  if (in_array($test_nick,$spam_user_list)==False)
  {
    return;
  }
  wiki_spamctl("",".spamctl $test_title",True);
}

#####################################################################################################

function wiki_spamuser($nick,$trailing)
{
  $spam_user=trim(substr($trailing,strlen(".spamuser")));
  $spam_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
  delete_empty_elements($list,True);
  if (in_array($spam_user,$spam_user_list)==True)
  {
    privmsg("wiki user \"$spam_user\" already in spam user list file");
    return;
  }
  if (file_put_contents(DATA_PATH."wiki_spam_users",implode("\n",$spam_user_list))===False)
  {
    privmsg("error adding wiki user \"$spam_user\" to spam user list file");
  }
  else
  {
    privmsg("wiki user \"$spam_user\" added to spam user list file");
  }
}

#####################################################################################################

?>
