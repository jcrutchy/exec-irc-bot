<?php

# gpl2
# by crutchy
# 26-june-2014

/*
- the bot could keeep track of irc comments and if you type something like
  "~comment Bytram, i think you're right" the bot could tack it to the end of Bytram's last comment posting
- if two people are having an irc discussion about tfa, and they are triggering comment posting,
  the bot would prolly just treat it like they were replying to each other's comments
- if they wanted to start a new thread it might need some kind of separate trigger
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

$subject="comment from $dest @ irc.sylnt.us";
$comment="<b>$nick</b> says in <b>$dest</b> on irc.sylnt.us:<br><br><i>$trailing</i>";

$bender_msg=get_bucket("BENDER_LAST_FEED_MESSAGE_VERIFIED");
if ($bender_msg=="")
{
  privmsg("Last feed message posted by Bender not found.");
  return;
}

if (strtolower($trailing)=="tfa")
{
  privmsg($bender_msg);
  return;
}

if (strlen($trailing)<30)
{
  privmsg("Comment must be at least 30 characters.");
  return;
}

if (strtolower($dest)<>"#soylent")
{
  privmsg("Comments may only be posted from the #Soylent channel.");
  return;
}

# [SoylentNews] - What a Warp-Speed Spaceship Might Look Like - http://sylnt.us/yvt2q - its-nice-to-dream

$host="sylnt.us";
$i=strpos($bender_msg,$host);
if ($i===False)
{
  privmsg("http://sylnt.us/ not found in Bender's last feed message.");
  return;
}
$bender_msg=substr($bender_msg,$i+strlen($host));
$parts=explode(" ",$bender_msg);
$uri=$parts[0];

$agent=ICEWEASEL_UA;

$response=wget($host,$uri,80,$agent);

$redirect_url=exec_get_header($response,"Location");
if ($redirect_url=="")
{
  privmsg("Location header not found @ http://".$host.$uri);
  return;
}

term_echo($redirect_url);

# http://soylentnews.org/article.pl?sid=14/06/20/0834246&amp;from=rss

$delim="sid=";
$i=strpos($redirect_url,$delim);
if ($i===False)
{
  privmsg("\"sid\" parameter not found in Location header URL");
  return;
}
$sid=substr($redirect_url,$i+strlen($delim));
$parts=explode("&",$sid);
$sid=$parts[0];

#$sid="14/04/01/032217"; (for testing)

term_echo($sid);

$host="soylentnews.org";
$uri="/my/login";
$port=443;

$params=array();
$params["returnto"]="";
$params["op"]="userlogin";
$params["login_temp"]="yes";
$params["unickname"]="exec";
$params["upasswd"]=trim(file_get_contents("/var/include/vhosts/irciv.us.to/pwd/exec"));
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
  logout();
}

term_echo($login_cookie);

$parts=explode(";",$login_cookie);
$cookie_user=trim($parts[0]);

term_echo($cookie_user);

# http://soylentnews.org/article.pl?sid=14/04/01/032217
# extract: <input type="hidden" name="sid" value="1007">
$uri="/article.pl?sid=$sid";
$extra_headers["Cookie"]=$cookie_user;
$response=wget($host,$uri,$port,$agent,$extra_headers);
$delim="<input type=\"hidden\" name=\"sid\" value=\"";
$i=strpos($response,$delim);
if ($i===False)
{
  privmsg("\"sid\" field not found @ https://".$host.$uri);
  logout();
}
$response=substr($response,$i+strlen($delim));
$delim="\"";
$i=strpos($response,$delim);
if ($i===False)
{
  privmsg("program borked (error code: 1)");
  logout();
}
$sid=substr($response,0,$i);

term_echo($sid);

# http://soylentnews.org/comments.pl?threshold=-1&highlightthresh=-1&mode=improvedthreaded&commentsort=0&sid=1007&op=Reply
# extract: <input type="hidden" name="formkey" value="cKh9Qyqsho">
$uri="/comments.pl?threshold=-1&highlightthresh=-1&mode=improvedthreaded&commentsort=0&sid=$sid&op=Reply";
$extra_headers["Cookie"]=$cookie_user;
$response=wget($host,$uri,$port,$agent,$extra_headers);

$delim="<input type=\"hidden\" name=\"formkey\" value=\"";
$i=strpos($response,$delim);
if ($i===False)
{
  privmsg("\"formkey\" field not found @ https://".$host.$uri);
  logout();
}
$response=substr($response,$i+strlen($delim));
$delim="\"";
$i=strpos($response,$delim);
if ($i===False)
{
  privmsg("program borked (error code: 2)");
  logout();
}
$formkey=substr($response,0,$i);

term_echo($formkey);

# post comment
$uri="/comments.pl";
$extra_headers["Cookie"]=$cookie_user;
$params=array();
$params["sid"]=$sid;
$params["pid"]="0";
$params["mode"]="improvedthreaded";
$params["startat"]="";
$params["threshold"]="-1";
$params["commentsort"]="0";
$params["formkey"]=$formkey;
$params["postersubj"]=$subject;
$params["postercomment"]=$comment;
$params["nobonus_present"]="1";
#$params["nobonus"]="";
$params["postanon_present"]="1";
#$params["postanon"]="";
$params["posttype"]="1"; # Plain Old Text
$params["op"]="Submit";
sleep(8);
$response=wpost($host,$uri,$port,$agent,$params,$extra_headers);

$delim="start template: ID 104";
if (strpos($response,$delim)!==False)
{
  privmsg("SoylentNews requires you to wait between each successful posting of a comment to allow everyone a fair chance at posting.");
}

$delim="start template: ID 274";
if (strpos($response,$delim)!==False)
{
  privmsg("This exact comment has already been posted. Try to be more original.");
}

$delim="start template: ID 180";
if (strpos($response,$delim)!==False)
{
  privmsg("Comment submitted successfully. There will be a delay before the comment becomes part of the static page.");
}

#term_echo($response);

logout();

#####################################################################################################

function logout()
{
  global $agent;
  $host="soylentnews.org";
  $uri="/my/logout";
  $port=80;
  wget($host,$uri,$port,$agent);
  die();
}

#####################################################################################################

?>
