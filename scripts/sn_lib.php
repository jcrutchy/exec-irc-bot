<?php

# gpl2
# by crutchy

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
  $params["unickname"]="exec";
  $params["upasswd"]=trim(file_get_contents("../pwd/exec"));
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

?>
