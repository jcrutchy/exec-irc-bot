<?php

#####################################################################################################

/*
exec:add ~spamctl
exec:edit ~spamctl timeout 20
exec:edit ~spamctl crutchy
exec:edit ~spamctl cmds PRIVMSG
exec:edit ~spamctl servers irc.sylnt.us
exec:edit ~spamctl dests #crutchy
exec:edit ~spamctl cmd php scripts/wiki/sn_wiki_spamctl.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%% %%server%%
exec:enable ~spamctl
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];
$server=$argv[6];

define("WIKI_HOST","wiki.soylentnews.org");

$response=wget(WIKI_HOST,"/w/api.php?action=query&meta=tokens&type=login&format=php",443);
$cookies=exec_get_cookies($response);

$data=unserialize(strip_headers($response));
$token=$data["query"]["tokens"]["logintoken"];
$user_params=explode("\n",file_get_contents("../pwd/wiki.bot.passwd"));
$params=array();
$params["lgname"]=$user_params[0];
$params["lgpassword"]=$user_params[1];
$params["lgtoken"]=$token;
$headers=array("Cookie"=>$cookies[0]);
$response=wpost(WIKI_HOST,"/w/api.php?action=login&format=php",443,WIKI_USER_AGENT,$params,$headers);
$data=unserialize(strip_headers($response));
#var_dump($data);
set_bucket("wiki_login_cookie",$data["login"]["cookieprefix"]."_session=".$data["login"]["sessionid"]."; path=/; secure; httponly");
set_bucket("wiki_lgtoken",$data["login"]["lgtoken"]);

#####################################################################################################

?>
