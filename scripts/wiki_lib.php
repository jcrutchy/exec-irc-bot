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

function login($nick,$return=False)
{
  $account=users_get_account($nick);
  $allowed=array("wikirc","ncommander","funpika","mrcoolbp","paulej72","juggs","crutchy","chromas","themightybuzzard","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized (login)");
    return;
  }
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

function wiki_delpage($nick,$trailing,$return=False)
{
  $title=trim(substr($trailing,strlen(".delpage")+1));
  if ($title=="")
  {
    wiki_privmsg($return,"wiki: delpage=invalid title");
    return False;
  }
  $account=users_get_account($nick);
  $allowed=array("ncommander","funpika","mrcoolbp","paulej72"); # official wiki admins
  if (in_array($account,$allowed)==False)
  {
    $allowed=array("juggs","crutchy","chromas","themightybuzzard","martyb"); # trusted irc nickserv accounts
    if (in_array($account,$allowed)==True)
    {
      $del_pages_list=array();
      $auth=False;
      if (file_exists(DATA_PATH."wiki_del_pages")==True)
      {
        $del_pages_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_del_pages"));
        delete_empty_elements($del_pages_list,True);
        $title_exists=False;
        for ($i=0;$i<count($del_pages_list);$i++)
        {
          $parts=explode(" ",$del_pages_list[$i]);
          $del_page_account=trim($parts[0]);
          array_shift($parts);
          $del_page_title=implode(" ",$parts);
          if ($del_page_title===$title)
          {
            $title_exists=True;
            if ($del_page_account<>$account)
            {
              $auth=True;
              unset($del_pages_list[$i]);
              break;
            }
          }
        }
        if ($auth==False)
        {
          if ($title_exists==False)
          {
            $del_pages_list[]=$account." ".$title;
            privmsg("page with title \"$title\" added to wiki del pages file. need another authorized account to finalize page deletion [1]");
          }
          else
          {
            privmsg("page with title \"$title\" already added to wiki del pages file. need a different authorized account to finalize page deletion");
          }
        }
      }
      else
      {
        $del_pages_list[]=$account." ".$title;
        privmsg("page with title \"$title\" added to wiki del pages file. need another authorized account to finalize page deletion [2]");
      }
      if (file_put_contents(DATA_PATH."wiki_del_pages",implode(PHP_EOL,$del_pages_list))===False)
      {
        privmsg("error writing del pages list file");
      }
      if ($auth==False)
      {
        return False;
      }
    }
    else
    {
      privmsg("  error: not authorized");
      return False;
    }
  }
  # make sure title not in del pages file
  if (file_exists(DATA_PATH."wiki_del_pages")==True)
  {
    $del_pages_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_del_pages"));
    delete_empty_elements($del_pages_list,True);
    for ($i=0;$i<count($del_pages_list);$i++)
    {
      $parts=explode(" ",$del_pages_list[$i]);
      array_shift($parts);
      $del_page_title=implode(" ",$parts);
      if ($del_page_title===$title)
      {
        unset($del_pages_list[$i]);
      }
    }
    file_put_contents(DATA_PATH."wiki_del_pages",implode("\n",$del_pages_list));
  }
  if (login($nick,True)==False)
  {
    privmsg("  login error");
    return;
  }
  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    wiki_privmsg($return,"wiki: delpage=not logged in");
    return False;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));
  $uri="/w/api.php?action=query&meta=tokens&format=php&type=csrf";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["query"]["tokens"]["csrftoken"])==False)
  {
    wiki_privmsg($return,"wiki: delpage=error getting csrftoken");
    return False;
  }
  $token=$data["query"]["tokens"]["csrftoken"];
  $uri="/w/api.php?action=delete&title=".urlencode($title)."&format=php&reason=spamctl";
  $params=array("token"=>$token);
  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  logout(True);
  if (isset($data["error"])==True)
  {
    wiki_privmsg($return,"wiki: delpage=".$data["error"]["code"]);
    return False;
  }
  else
  {
    if (isset($data["delete"]["logid"])==True)
    {
      $msg="wiki: delpage=logid ".$data["delete"]["logid"];
      wiki_privmsg($return,$msg);
      return True;
    }
    else
    {
      $msg="wiki: delpage=logid not found";
      wiki_privmsg($return,$msg);
      return False;
    }
  }
}

#####################################################################################################

function wiki_undelpage($nick,$trailing)
{
  $title=trim(substr($trailing,strlen(".undelpage")+1));
  if ($title=="")
  {
    wiki_privmsg(False,"http://sylnt.us/wikispamctl");
    return;
  }
  $allowed1=array("ncommander","funpika","mrcoolbp","paulej72"); # official wiki admins
  $allowed2=array("juggs","crutchy","chromas","themightybuzzard","martyb"); # trusted irc nickserv accounts
  $account=users_get_account($nick);
  if ((in_array($account,$allowed1)==False) and (in_array($account,$allowed2)==False))
  {
    wiki_privmsg(False,"wiki: undelpage=not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_del_pages")==False)
  {
    wiki_privmsg(False,"wiki: undelpage=wiki_del_pages file not found");
    return;
  }
  $del_pages_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_del_pages"));
  delete_empty_elements($del_pages_list,True);
  $title_exists=False;
  for ($i=0;$i<count($del_pages_list);$i++)
  {
    $parts=explode(" ",$del_pages_list[$i]);
    array_shift($parts);
    $del_page_title=implode(" ",$parts);
    if ($del_page_title===$title)
    {
      $title_exists=True;
      unset($del_pages_list[$i]);
    }
  }
  if ($title_exists==False)
  {
    wiki_privmsg(False,"wiki: undelpage=title not found in wiki_del_pages file");
    return;
  }
  if (file_put_contents(DATA_PATH."wiki_del_pages",implode(PHP_EOL,$del_pages_list))===False)
  {
    wiki_privmsg(False,"wiki: undelpage=error writing wiki_del_pages file");
  }
  else
  {
    wiki_privmsg(False,"  deleted \"$title\" from wiki_del_pages file");
  }
}

#####################################################################################################

function wiki_spamctl($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("wikirc","crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
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
  if ($nick=="wikirc")
  {
    $text="{{spam}}<br>if this automated spam flag is wrong, please join #wiki @ http://chat.soylentnews.org/ and let us know";
  }
  else
  {
    $text="{{spam}}<br>if this spam flag is wrong, please join #wiki @ http://chat.soylentnews.org/ and let us know";
  }
  if (login($nick,True)==False)
  {
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

function wiki_unspamctl($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  $title=trim(substr($trailing,strlen(".unspamctl")));
  if ($title=="")
  {
    privmsg("  syntax: .unspamctl <page title>");
    return;
  }
  if (login($nick,True)==False)
  {
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
  $uri="/w/api.php?action=query&format=php&meta=tokens&type=rollback";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["query"]["tokens"]["rollbacktoken"])==False)
  {
    privmsg("  error getting rollbacktoken");
    logout(True);
    return;
  }
  $token=$data["query"]["tokens"]["rollbacktoken"];
  /*$uri="/w/api.php?action=edit";
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
  }*/
  logout(True);
}

#####################################################################################################

function wiki_autospamctl($nick,$trailing)
{
  $spam_user_list=array();
  $safe_user_list=array();
  $spam_rule_list=array();
  if (file_exists(DATA_PATH."wiki_spam_users")==True)
  {
    $spam_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
    delete_empty_elements($spam_user_list,True);
  }
  if (file_exists(DATA_PATH."wiki_safe_users")==True)
  {
    $safe_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_safe_users"));
    delete_empty_elements($safe_user_list,True);
  }
  if (file_exists(DATA_PATH."wiki_spam_rules")==True)
  {
    $spam_rule_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_rules"));
    delete_empty_elements($spam_rule_list,True);
  }
  $test_title=trim(extract_text_nofalse($trailing,"14[[07","14]]"));
  $test_nick=trim(extract_text_nofalse($trailing," 5* 03"," 5*"));
  if (($test_title=="") or ($test_nick==""))
  {
    return;
  }
  if (in_array($test_nick,$safe_user_list)==True)
  {
    return;
  }
  $prefix="Special:";
  if (substr($test_title,0,strlen($prefix))==$prefix)
  {
    return;
  }
  $rule_match=False;
  for ($i=0;$i<count($spam_rule_list);$i++)
  {
    if (preg_match($spam_rule_list[$i],$test_nick)==1)
    {
      $rule_match=True;
      break;
    }
  }
  if ($rule_match==False)
  {
    if (in_array($test_nick,$spam_user_list)==False)
    {
      return;
    }
  }
  privmsg("auto-spamctl for article \"$test_title\" by spam user \"$test_nick\"");
  wiki_spamctl($nick,".spamctl $test_title");
}

#####################################################################################################

function wiki_spamuser($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_spam_users")==False)
  {
    $spam_user_list=array();
  }
  else
  {
    $spam_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
  }
  $spam_user=trim(substr($trailing,strlen(".spamuser")+1));
  if ($spam_user=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($spam_user_list,True);
  if (in_array($spam_user,$spam_user_list)==True)
  {
    privmsg("wiki user \"$spam_user\" already in spam user list file");
    return;
  }
  $spam_user_list[]=$spam_user;
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

function wiki_delspamuser($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_spam_users")==False)
  {
    privmsg("spam user list file not found");
    return;
  }
  $spam_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
  $spam_user=trim(substr($trailing,strlen(".delspamuser")+1));
  if ($spam_user=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($spam_user_list,True);
  if (in_array($spam_user,$spam_user_list)==False)
  {
    privmsg("wiki user \"$spam_user\" not found in spam user list file");
    return;
  }
  $index=array_search($spam_user,$spam_user_list);
  unset($spam_user_list[$index]);
  if (file_put_contents(DATA_PATH."wiki_spam_users",implode("\n",$spam_user_list))===False)
  {
    privmsg("error deleting wiki user \"$spam_user\" from spam user list file");
  }
  else
  {
    privmsg("wiki user \"$spam_user\" deleted from spam user list file");
  }
}

#####################################################################################################

function wiki_testrules($nick,$trailing)
{
  $spam_rule_list=array();
  if (file_exists(DATA_PATH."wiki_spam_rules")==True)
  {
    $spam_rule_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_rules"));
    delete_empty_elements($spam_rule_list,True);
  }
  $test_nick=trim(substr($trailing,strlen(".testrules")));
  $rule_match=False;
  for ($i=0;$i<count($spam_rule_list);$i++)
  {
    if (preg_match($spam_rule_list[$i],$test_nick)==1)
    {
      $rule_match=True;
      break;
    }
  }
  if ($rule_match==False)
  {
    privmsg("no match");
  }
  else
  {
    privmsg("match");
  }
}

#####################################################################################################

function wiki_delspamrule($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_spam_rules")==False)
  {
    privmsg("spam rule list file not found");
    return;
  }
  $spam_rule_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_rules"));
  $spam_rule=trim(substr($trailing,strlen(".delspamrule")+1));
  if ($spam_rule=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($spam_rule_list,True);
  if (in_array($spam_rule,$spam_rule_list)==False)
  {
    privmsg("rule \"$spam_rule\" not found in wiki spam rule list file");
    return;
  }
  $index=array_search($spam_rule,$spam_rule_list);
  unset($spam_rule_list[$index]);
  if (file_put_contents(DATA_PATH."wiki_spam_rules",implode("\n",$spam_rule_list))===False)
  {
    privmsg("error deleting rule \"$spam_rule\" from wiki spam rule list file");
  }
  else
  {
    privmsg("rule \"$spam_rule\" deleted from wiki spam rule list file");
  }
}

#####################################################################################################

function wiki_spamrule($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_spam_rules")==False)
  {
    $spam_rule_list=array();
  }
  else
  {
    $spam_rule_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_rules"));
  }
  $spam_rule=trim(substr($trailing,strlen(".spamrule")+1));
  if ($spam_rule=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($spam_rule_list,True);
  if (in_array($spam_rule,$spam_rule_list)==True)
  {
    privmsg("rule \"$spam_rule\" already in wiki spam rule list file");
    return;
  }
  $spam_rule_list[]=$spam_rule;
  if (file_put_contents(DATA_PATH."wiki_spam_rules",implode("\n",$spam_rule_list))===False)
  {
    privmsg("error adding rule \"$spam_rule\" to wiki spam rule list file");
  }
  else
  {
    privmsg("rule \"$spam_rule\" added to wiki spam rule list file");
  }
}

#####################################################################################################

function wiki_safeuser($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_safe_users")==False)
  {
    $safe_user_list=array();
  }
  else
  {
    $safe_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_safe_users"));
  }
  $safe_user=trim(substr($trailing,strlen(".safeuser")+1));
  if ($safe_user=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($safe_user_list,True);
  if (in_array($safe_user,$safe_user_list)==True)
  {
    privmsg("wiki user \"$safe_user\" already in safe user list file");
    return;
  }
  $safe_user_list[]=$safe_user;
  if (file_put_contents(DATA_PATH."wiki_safe_users",implode("\n",$safe_user_list))===False)
  {
    privmsg("error adding wiki user \"$safe_user\" to safe user list file");
  }
  else
  {
    privmsg("wiki user \"$safe_user\" added to safe user list file");
  }
}

#####################################################################################################

function wiki_delsafeuser($nick,$trailing)
{
  $account=users_get_account($nick);
  $allowed=array("crutchy","chromas","mrcoolbp","paulej72","juggs","martyb");
  if (in_array($account,$allowed)==False)
  {
    privmsg("  error: not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_safe_users")==False)
  {
    privmsg("safe user list file not found");
    return;
  }
  $safe_user_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_safe_users"));
  $safe_user=trim(substr($trailing,strlen(".delsafeuser")+1));
  if ($safe_user=="")
  {
    privmsg("http://sylnt.us/wikispamctl");
    return;
  }
  delete_empty_elements($safe_user_list,True);
  if (in_array($safe_user,$safe_user_list)==False)
  {
    privmsg("wiki user \"$safe_user\" not found in safe user list file");
    return;
  }
  $index=array_search($safe_user,$safe_user_list);
  unset($safe_user_list[$index]);
  if (file_put_contents(DATA_PATH."wiki_safe_users",implode("\n",$safe_user_list))===False)
  {
    privmsg("error deleting wiki user \"$safe_user\" from safe user list file");
  }
  else
  {
    privmsg("wiki user \"$safe_user\" deleted from safe user list file");
  }
}

#####################################################################################################

function wiki_listspamrules()
{
  if (file_exists(DATA_PATH."wiki_spam_rules")==False)
  {
    privmsg("spam rules file not found");
  }
  else
  {
    $list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_rules"));
    delete_empty_elements($list,True);
    if (count($list)>0)
    {
      $max=4;
      for ($i=0;$i<min($max,count($list));$i++)
      {
        privmsg(chr(3)."07".$list[$i]);
      }
      if (count($list)>$max)
      {
        privmsg(chr(3)."07".(count($list)-$max)." more");
      }
    }
    else
    {
      privmsg("no spam rules");
    }
  }
}

#####################################################################################################

function wiki_listspamusers()
{
  if (file_exists(DATA_PATH."wiki_spam_users")==False)
  {
    privmsg("spam users file not found");
  }
  else
  {
    $list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_spam_users"));
    delete_empty_elements($list,True);
    if (count($list)>0)
    {
      privmsg("wiki spam users: ".implode(",",$list));
    }
    else
    {
      privmsg("no spam users");
    }
  }
}

#####################################################################################################

function wiki_listsafeusers()
{
  if (file_exists(DATA_PATH."wiki_safe_users")==False)
  {
    privmsg("safe users file not found");
  }
  else
  {
    $list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_safe_users"));
    delete_empty_elements($list,True);
    if (count($list)>0)
    {
      privmsg("wiki safe users: ".implode(",",$list));
    }
    else
    {
      privmsg("no safe users");
    }
  }
}

#####################################################################################################

function wiki_blockuser($nick,$trailing,$return=False)
{
  $title=trim(substr($trailing,strlen(".delpage")+1));
  if ($title=="")
  {
    wiki_privmsg($return,"wiki: delpage=invalid title");
    return False;
  }
  $account=users_get_account($nick);
  $allowed=array("ncommander","funpika","mrcoolbp","paulej72"); # official wiki admins
  if (in_array($account,$allowed)==False)
  {
    $allowed=array("juggs","crutchy","chromas","themightybuzzard","martyb"); # trusted irc nickserv accounts
    if (in_array($account,$allowed)==True)
    {
      $del_pages_list=array();
      $auth=False;
      if (file_exists(DATA_PATH."wiki_del_pages")==True)
      {
        $del_pages_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_del_pages"));
        delete_empty_elements($del_pages_list,True);
        $title_exists=False;
        for ($i=0;$i<count($del_pages_list);$i++)
        {
          $parts=explode(" ",$del_pages_list[$i]);
          $del_page_account=trim($parts[0]);
          array_shift($parts);
          $del_page_title=implode(" ",$parts);
          if ($del_page_title===$title)
          {
            $title_exists=True;
            if ($del_page_account<>$account)
            {
              $auth=True;
              unset($del_pages_list[$i]);
              break;
            }
          }
        }
        if ($auth==False)
        {
          if ($title_exists==False)
          {
            $del_pages_list[]=$account." ".$title;
            privmsg("page with title \"$title\" added to wiki del pages file. need another authorized account to finalize page deletion [1]");
          }
          else
          {
            privmsg("page with title \"$title\" already added to wiki del pages file. need a different authorized account to finalize page deletion");
          }
        }
      }
      else
      {
        $del_pages_list[]=$account." ".$title;
        privmsg("page with title \"$title\" added to wiki del pages file. need another authorized account to finalize page deletion [2]");
      }
      if (file_put_contents(DATA_PATH."wiki_del_pages",implode(PHP_EOL,$del_pages_list))===False)
      {
        privmsg("error writing del pages list file");
      }
      if ($auth==False)
      {
        return False;
      }
    }
    else
    {
      privmsg("  error: not authorized");
      return False;
    }
  }
  # make sure title not in del pages file
  if (file_exists(DATA_PATH."wiki_del_pages")==True)
  {
    $del_pages_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_del_pages"));
    delete_empty_elements($del_pages_list,True);
    for ($i=0;$i<count($del_pages_list);$i++)
    {
      $parts=explode(" ",$del_pages_list[$i]);
      array_shift($parts);
      $del_page_title=implode(" ",$parts);
      if ($del_page_title===$title)
      {
        unset($del_pages_list[$i]);
      }
    }
    file_put_contents(DATA_PATH."wiki_del_pages",implode("\n",$del_pages_list));
  }
  /*if (login($nick,True)==False)
  {
    privmsg("  login error");
    return;
  }
  $cookieprefix=get_bucket("wiki_login_cookieprefix");
  $sessionid=get_bucket("wiki_login_sessionid");
  if (($cookieprefix=="") or ($sessionid==""))
  {
    wiki_privmsg($return,"wiki: delpage=not logged in");
    return False;
  }
  $headers=array("Cookie"=>login_cookie($cookieprefix,$sessionid));
  $uri="/w/api.php?action=query&meta=tokens&format=php&type=csrf";
  $response=wget(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$headers);
  $data=unserialize(strip_headers($response));
  if (isset($data["query"]["tokens"]["csrftoken"])==False)
  {
    wiki_privmsg($return,"wiki: delpage=error getting csrftoken");
    return False;
  }
  $token=$data["query"]["tokens"]["csrftoken"];
  $uri="/w/api.php?action=delete&title=".urlencode($title)."&format=php&reason=spamctl";
  $params=array("token"=>$token);
  $response=wpost(WIKI_HOST,$uri,80,WIKI_USER_AGENT,$params,$headers);
  $data=unserialize(strip_headers($response));
  logout(True);
  if (isset($data["error"])==True)
  {
    wiki_privmsg($return,"wiki: delpage=".$data["error"]["code"]);
    return False;
  }
  else
  {
    if (isset($data["delete"]["logid"])==True)
    {
      $msg="wiki: delpage=logid ".$data["delete"]["logid"];
      wiki_privmsg($return,$msg);
      return True;
    }
    else
    {
      $msg="wiki: delpage=logid not found";
      wiki_privmsg($return,$msg);
      return False;
    }
  }*/
}

#####################################################################################################

function wiki_unblockuser($nick,$trailing,$return=False)
{
  $username=trim(substr($trailing,strlen(".unblockuser")+1));
  if ($username=="")
  {
    wiki_privmsg(False,"http://sylnt.us/wikispamctl");
    return;
  }
  $allowed1=array("ncommander","funpika","mrcoolbp","paulej72"); # official wiki admins
  $allowed2=array("juggs","crutchy","chromas","themightybuzzard","martyb"); # trusted irc nickserv accounts
  $account=users_get_account($nick);
  if ((in_array($account,$allowed1)==False) and (in_array($account,$allowed2)==False))
  {
    wiki_privmsg(False,"wiki: unblockuser=not authorized");
    return;
  }
  if (file_exists(DATA_PATH."wiki_block_users")==False)
  {
    wiki_privmsg(False,"wiki: unblockuser=wiki_block_users file not found");
    return;
  }
  $block_users_list=explode(PHP_EOL,file_get_contents(DATA_PATH."wiki_block_users"));
  delete_empty_elements($block_users_list,True);
  $username_exists=False;
  for ($i=0;$i<count($block_users_list);$i++)
  {
    $parts=explode(" ",$block_users_list[$i]);
    array_shift($parts);
    $block_username=implode(" ",$parts);
    if ($block_username===$username)
    {
      $username_exists=True;
      unset($block_users_list[$i]);
    }
  }
  if ($username_exists==False)
  {
    wiki_privmsg(False,"wiki: unblockuser=username not found in wiki_block_users file");
    return;
  }
  if (file_put_contents(DATA_PATH."wiki_block_users",implode(PHP_EOL,$block_users_list))===False)
  {
    wiki_privmsg(False,"wiki: unblockuser=error writing wiki_block_users file");
  }
  else
  {
    wiki_privmsg(False,"  deleted \"$username\" from wiki_block_users file");
  }
}

#####################################################################################################

?>