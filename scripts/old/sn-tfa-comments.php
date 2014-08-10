<?php

# gpl2
# by crutchy
# 29-june-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

$sid="14/06/28/0716216";

$agent=ICEWEASEL_UA;

$host="soylentnews.org";
$uri="/my/login";
$port=443;

$params=array();
$params["returnto"]="";
$params["op"]="userlogin";
$params["login_temp"]="yes";
$params["unickname"]="exec";

$params["upasswd"]=trim(file_get_contents("../pwd/".NICK_EXEC));
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

$uri="/comments.pl?threshold=-1&highlightthresh=-1&mode=improvedthreaded&commentsort=0&sid=$sid";
$extra_headers["Cookie"]=$cookie_user;
$response=wget($host,$uri,$port,$agent,$extra_headers);

/*$html=strip_headers($response);
strip_all_tag($html,"head");
strip_all_tag($html,"script");
strip_all_tag($html,"style");
strip_all_tag($html,"a");
$html=strip_tags($html,"<div>");*/

$delim1="<ul id=\"commentlisting\" >";
$delim2="</ul>";

$i=strpos(strtolower($response),strtolower($delim1));
if ($i===False)
{
  privmsg("commentlisting ul not found");
  logout();
}
$html=substr($response,$i+strlen($delim1));
$i=strpos($html,$delim2);
if ($i===False)
{
  privmsg("commentlisting </ul> not found");
  logout();
}
$html=substr($html,0,$i);

file_put_contents("sn-tfa-comments.txt",$html);

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
