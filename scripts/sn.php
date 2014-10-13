<?php

# gpl2
# by crutchy

/*
- the bot could keeep track of irc comments and if you type something like
  "~comment Bytram, i think you're right" the bot could tack it to the end of Bytram's last comment posting
- if two people are having an irc discussion about tfa, and they are triggering comment posting,
  the bot would prolly just treat it like they were replying to each other's comments
- if they wanted to start a new thread it might need some kind of separate trigger
*/

#####################################################################################################

require_once("sn_lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

define("BUCKET_VERIFIER_NICK","<<verifier_nick>>");

switch ($alias)
{
  case "~uid":
    $host="www.soylentnews.org";
    $port=80;
    $uri="/zoo.pl?op=max";
    $response=wget($host,$uri,$port,ICEWEASEL_UA);
    $delim1="<p class='bender'>";
    $delim2="</p>";
    $uid=extract_text($response,$delim1,$delim2);
    if ($uid!==False)
    {
      privmsg($uid);
    }
    return;
  case "~comment":
    $host="soylentnews.org";
    $port=443;
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
    $response=wget($host,$uri,80,ICEWEASEL_UA);
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
    $extra_headers=array();
    $extra_headers["Cookie"]=sn_login();
    # http://soylentnews.org/article.pl?sid=14/04/01/032217
    # extract: <input type="hidden" name="sid" value="1007">
    $uri="/article.pl?sid=$sid";
    $response=wget($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
    $delim="<input type=\"hidden\" name=\"sid\" value=\"";
    $i=strpos($response,$delim);
    if ($i===False)
    {
      privmsg("\"sid\" field not found @ https://".$host.$uri);
      sn_logout();
    }
    $response=substr($response,$i+strlen($delim));
    $delim="\"";
    $i=strpos($response,$delim);
    if ($i===False)
    {
      privmsg("program borked (error code: 1)");
      sn_logout();
    }
    $sid=substr($response,0,$i);
    term_echo($sid);
    # http://soylentnews.org/comments.pl?threshold=-1&highlightthresh=-1&mode=improvedthreaded&commentsort=0&sid=1007&op=Reply
    # extract: <input type="hidden" name="formkey" value="cKh9Qyqsho">
    $uri="/comments.pl?threshold=-1&highlightthresh=-1&mode=improvedthreaded&commentsort=0&sid=$sid&op=Reply";
    $extra_headers["Cookie"]=$cookie_user;
    $response=wget($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
    $delim="<input type=\"hidden\" name=\"formkey\" value=\"";
    $i=strpos($response,$delim);
    if ($i===False)
    {
      privmsg("\"formkey\" field not found @ https://".$host.$uri);
      sn_logout();
    }
    $response=substr($response,$i+strlen($delim));
    $delim="\"";
    $i=strpos($response,$delim);
    if ($i===False)
    {
      privmsg("program borked (error code: 2)");
      sn_logout();
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
    $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params,$extra_headers);
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
    sn_logout();
    return;
  case "~queue":
    $host="www.soylentnews.org";
    $port=80;
    $extra_headers=array();
    $extra_headers["Cookie"]=sn_login();
    $uri="/";
    $response=wget($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
    $delim1="<a href=\"//soylentnews.org/submit.pl?op=list\"> Only <b>";
    $delim2="</b> submissions in the queue";
    $count=extract_text($response,$delim1,$delim2);
    if ($count!==False)
    {
      privmsg("*** SN submission queue: $count - http://sylnt.us/queue");
    }
    sn_logout();
    return;
  case "~funding":
    $verifier_nick=get_bucket(BUCKET_VERIFIER_NICK);
    $verifier_account="chromas";
    $verifier_msg="exec_test_sn_site_down";
    $host="www.soylentnews.org";
    $host_g="www.google.com";
    $uri="/";
    $port=80;
    $response=wtouch($host,$uri,$port,120);
    $response_g=wtouch($host_g,$uri,$port,120);
    if (($response===False) and ($response_g!==False))
    {
      pm("crutchy","ALERT: \"".strtoupper($host)."\" HOST IS UNAVAILABLE ON PORT $port");
      $account=users_get_account($verifier_nick);
      if ($account==$verifier_account)
      {
        pm($verifier_nick,$verifier_msg);
      }
      else
      {
        pm("#soylent",chr(3)."08".chr(2)."*** ALERT: \"".strtoupper($host)."\" HOST IS UNAVAILABLE ON PORT $port ***");
      }
      return;
    }
    $extra_headers=array();
    $extra_headers["Cookie"]=sn_login();
    $response=wget($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
    $delim1="<b>Progress So Far: $";
    $delim2="</b>";
    $amount=extract_text($response,$delim1,$delim2);
    if ($amount!==False)
    {
      $data=exec_file_read("previous_sn_funding");
      $previous="";
      if (count($data)>0)
      {
        $previous=trim($data[0]);
      }
      else
      {
        term_echo("funding: count(data) = 0");
      }
      if ($previous<>$amount)
      {
        pm("#soylent",chr(3)."05"."*** SN funding has changed from \$$previous to \$$amount");
        exec_file_write("previous_sn_funding",array($amount));
      }
      else
      {
        term_echo("funding: previous = amount ($amount)");
      }
    }
    else
    {
      term_echo("funding: amount not found in http response");
    }
    sn_logout();
    return;
  case "~verifier-nick-change":
    $parts=explode(" ",$trailing);
    if (count($parts)==2)
    {
      $old_nick=trim(strtolower($parts[0]));
      $new_nick=trim(strtolower($parts[1]));
      $registered_nick=get_bucket(BUCKET_VERIFIER_NICK);
      if ($old_nick==$registered_nick)
      {
        set_bucket(BUCKET_VERIFIER_NICK,$new_nick);
        pm("#","SN status verifier nick updated from \"$registered_nick\" to \"$new_nick\"");
      }
    }
    return;
  case "~verifier-nick":
    $registered_nick=get_bucket(BUCKET_VERIFIER_NICK);
    if ($trailing=="")
    {
      privmsg("registered verifier nick: ".$registered_nick);
    }
    else
    {
      $new_nick=trim(strtolower($trailing));
      set_bucket(BUCKET_VERIFIER_NICK,$new_nick);
      $msg="SN status verifier nick updated from \"$registered_nick\" to \"$new_nick\"";
      privmsg($msg);
    }
    return;
}

#####################################################################################################

?>
