<?php


#####################################################################################################

require_once("lib.php");

#####################################################################################################

function sn_login()
{
  $agent=ICEWEASEL_UA;
  $host="soylentnews.org";
  $uri="/my/login";
  $port=443;
  $params=array();
  $params["returnto"]="";
  $params["op"]="userlogin";
  $params["login_temp"]="yes";
  #$params["unickname"]="exec";
  $params["unickname"]="crutchy";
  #$params["upasswd"]=trim(file_get_contents("../pwd/exec"));
  $params["upasswd"]=trim(file_get_contents("../pwd/crutchy_sn"));
  $params["userlogin"]="Log in";
  $response=wpost($host,$uri,$port,$agent,$params);
  $cookies=exec_get_cookies($response);
  # user=4468::4pVxvvp70xtWLTfclHBpBp; path=/; expires=Fri, 20-Jun-2014 10:28:58 GMT
  $login_cookie="";
  $delim="user=";
  for ($i=0;$i<count($cookies);$i++)
  {
    if (substr($cookies[$i],0,strlen($delim))==$delim)
    {
      $login_cookie=$cookies[$i];
    }
  }
  if ($login_cookie=="")
  {
    term_echo("error: login failure");
    sn_logout();
  }
  $parts=explode(";",$login_cookie);
  $cookie_user=trim($parts[0]);
  # term_echo("*** SN USER COOKIE = \"$cookie_user\""); # << THERE SEEMS TO BE ABOUT A 30 MINUTE CYCLE TIME FOR COOKIE VALUES
  return $cookie_user;
}

#####################################################################################################

function sn_logout()
{
  $agent=ICEWEASEL_UA;
  $host="soylentnews.org";
  $uri="/my/logout";
  $port=443;
  $response=wget($host,$uri,$port,$agent);
  #var_dump($response);
}

#####################################################################################################

function sn_comment($subject,$comment_body,$sd_key_sid,$parent_cid="")
{
  $article_sid=sn_get_sid($sd_key_sid);
  #privmsg("article_sid = ".$article_sid);
  if ($article_sid===False)
  {
    privmsg("error: sn_get_sid returned false");
    return False;
  }
  return sn_comment_sid($subject,$comment_body,$article_sid,$parent_cid);
}

#####################################################################################################

function sn_comment_sid($subject,$comment_body,$article_sid,$parent_cid="")
{
  $host="dev.soylentnews.org";
  $port=443;
  $params=array();
  if ($parent_cid=="")
  {
    $params["pid"]="0";
    $uri="/comments.pl?sid=$article_sid&op=Reply";
  }
  else
  {
    $params["pid"]=$parent_cid;
    $uri="/comments.pl?sid=$article_sid&pid=$parent_cid&op=Reply";
  }
  $extra_headers=array();
  $extra_headers["Cookie"]=sn_login();
  if ($extra_headers["Cookie"]=="")
  {
    term_echo("error: login failure (2)");
    return False;
  }
  $response=wget($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
  $html=strip_headers($response);
  $delim1="<input type=\"hidden\" name=\"formkey\" value=\"";
  $delim2="\">";
  $formkey=extract_text($html,$delim1,$delim2);
  if ($formkey===False)
  {
    term_echo("error: unable to get formkey");
    sn_logout();
    return False;
  }
  var_dump($formkey);
  $uri="/comments.pl";
  $params["sid"]=$article_sid;
  $params["mode"]="improvedthreaded";
  $params["startat"]="";
  $params["threshold"]="-1";
  $params["commentsort"]="0";
  $params["formkey"]=$formkey;
  $params["postersubj"]=$subject;
  $params["postercomment"]=$comment_body;
  #$params["nobonus_present"]="1";
  #$params["nobonus"]="";
  $params["postanon_present"]="1";
  #$params["postanon"]="";
  $params["posttype"]="1"; # Plain Old Text
  $params["op"]="Submit";
  sleep(8);
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params,$extra_headers);
  $html=strip_headers($response);
  $delim="start template: ID 104";
  $result=False;
  if (strpos($html,$delim)!==False)
  {
    privmsg("SoylentNews requires you to wait between each successful posting of a comment to allow everyone a fair chance at posting.");
  }
  $delim="This exact comment has already been posted.";
  if (strpos($html,$delim)!==False)
  {
    privmsg("This exact comment has already been posted. Try to be more original.");
  }
  $delim="Comment Submitted. There will be a delay before the comment becomes part of the static page.";
  if (strpos($html,$delim)!==False)
  {
    $result=array();
    $delim1="<input type=\"hidden\" name=\"sid\" value=\"";
    $delim2="\">";
    $result["sid"]=extract_text($html,$delim1,$delim2);
    $delim1="<input type=\"hidden\" name=\"cid\" value=\"";
    $result["cid"]=extract_text($html,$delim1,$delim2);
    $delim1="<input type=\"hidden\" name=\"pid\" value=\"";
    $result["pid"]=extract_text($html,$delim1,$delim2); # if pid=cid, then comment is at root level
    $delim1="<div id=\"comment_body_".$result["cid"]."\">";
    $delim2="</div>";
    $result["body"]=extract_text($html,$delim1,$delim2);
    $delim1="<a name=\"".$result["cid"]."\">";
    $delim2="</a>";
    $result["subject"]=extract_text($html,$delim1,$delim2);
    privmsg("  comment submitted successfully => https://".$host."/comments.pl?sid=".$result["sid"]."&cid=".$result["cid"]);
  }
  #var_dump($html);
  sn_logout();
  return $result;
}

#####################################################################################################

function sn_get_sid($sd_key_sid)
{
  $host="dev.soylentnews.org";
  $port=443;
  $uri="/article.pl?sid=$sd_key_sid";
  $response=wget($host,$uri,$port);
  $delim1="<input type=\"hidden\" name=\"sid\" value=\"";
  $delim2="\">";
  return extract_text($response,$delim1,$delim2);
}

#####################################################################################################

function sn_submit($url)
{
  if ($url=="")
  {
    return False;
  }
  $url=get_redirected_url($url);
  if ($url===False)
  {
    privmsg("error: unable to download source (get_redirected_url)");
    return False;
  }
  $host="";
  $uri="";
  $port=80;
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    privmsg("error: unable to download source (get_host_and_uri)");
    return False;
  }
  $response=wget($host,$uri,$port);
  if (get_host_and_uri($url,$host,$uri,$port)==False)
  {
    privmsg("error: unable to download source (wget)");
    return False;
  }
  $source_html=strip_headers($response);
  $source_title=extract_raw_tag($source_html,"title");
  $delimiters=array("--","|"," - "," : "," — "," • ");
  for ($i=0;$i<count($delimiters);$i++)
  {
    $j=strpos($source_title,$delimiters[$i]);
    if ($j!==False)
    {
      $source_title=trim(substr($source_title,0,$j));
    }
  }
  if (($source_title===False) or ($source_title==""))
  {
    privmsg("error: title not found or empty");
    return False;
  }
  $source_title=html_decode($source_title);
  $source_title=html_decode($source_title);
  $source_body=extract_meta_content($source_html,"description");
  if (($source_body===False) or ($source_body==""))
  {
    $source_body=extract_meta_content($source_html,"og:description","property");
    if (($source_body===False) or ($source_body==""))
    {
      privmsg("error: description meta content not found or empty");
      return False;
    }
  }
  $html=$source_html;
  $article=extract_raw_tag($html,"article");
  if ($article!==False)
  {
    $html=$article;
  }
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  #strip_all_tag($html,"a");
  strip_all_tag($html,"strong");
  $html=strip_tags($html,"<p>");
  $html=lowercase_tags($html);
  $html=explode("<p",$html);
  $source_body=array();
  for ($i=0;$i<count($html);$i++)
  {
    $parts=explode(">",$html[$i]);
    if (count($parts)>=2)
    {
      array_shift($parts);
      $html[$i]=implode(">",$parts);
    }
    $html[$i]=strip_tags($html[$i]);
    $html[$i]=clean_text($html[$i]);
    $host_parts=explode(".",$host);
    for ($j=0;$j<count($host_parts);$j++)
    {
      if (strlen($host_parts[$j])>3)
      {
        if (strpos(strtolower($html[$i]),strtolower($host_parts[$j]))!==False)
        {
          continue 2;
        }
      }
    }
    if (filter($html[$i],"0123456789")<>"")
    {
      continue;
    }
    if (strlen($html[$i])>1)
    {
      if ($html[$i][strlen($html[$i])-1]<>".")
      {
        continue;
      }
      while (True)
      {
        $j=strlen($html[$i])-1;
        if ($j<0)
        {
          break;
        }
        $c=$html[$i][$j];
        if ($c==".")
        {
          break;
        }
        $html[$i]=substr($html[$i],0,$j);
      }
    }
    if (strlen($html[$i])>100)
    {
      $source_body[]=$html[$i];
    }
  }
  $source_body=implode("\n\n",$source_body);
  $source_body=html_decode($source_body);
  $source_body=html_decode($source_body);
  $host="dev.soylentnews.org";
  $port=443;
  $uri="/submit.pl";
  $response=wget($host,$uri,$port,ICEWEASEL_UA);
  $html=strip_headers($response);
  $reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
  if ($reskey===False)
  {
    privmsg("error: unable to extract reskey");
    return False;
  }
  sleep(25);
  $params=array();
  $params["reskey"]=$reskey;
  #$params["name"]=trim(substr($nick,0,50));
  $params["name"]=NICK_EXEC;
  $params["email"]="";
  $params["subj"]=trim(substr($source_title,0,100));
  $params["primaryskid"]="1";
  $params["tid"]="6";
  $params["sub_type"]="plain";
  $params["story"]=$source_body."\n\n".$url."\n\n-- submitted from IRC";
  $params["op"]="SubmitStory";
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
  $html=strip_headers($response);
  strip_all_tag($html,"head");
  strip_all_tag($html,"script");
  strip_all_tag($html,"style");
  strip_all_tag($html,"a");
  $html=strip_tags($html);
  $html=clean_text($html);
  if (strpos($html,"Perhaps you would like to enter an email address or a URL next time. Thanks for the submission.")!==False)
  {
    privmsg("submission successful - https://$host/submit.pl?op=list");
    return True;
  }
  else
  {
    privmsg("error: something went wrong with your submission");
    return False;
  }
}

#####################################################################################################

?>
