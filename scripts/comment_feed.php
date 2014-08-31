<?php

# gpl2
# by crutchy
# 31-aug-2014

#####################################################################################################

/*
<crutchy> could make some funky settings out of this
<crutchy> instead of privmsging a channel, it could notice any nick that registered to receive them
<crutchy> and could even set a score threshold etc
<crutchy> personalized SN comment feeds :D
*/

ini_set("display_errors","on");
require_once("lib.php");
require_once("feeds_lib.php");
require_once("lib_buckets.php");
define("COMMENTS_FEED_FILE","../data/comments_feed.txt");
define("COMMENTS_CID_FILE","../data/comments_cid.txt");
term_echo("******************* SOYLENTNEWS COMMENT FEED *******************");
$response=wget("soylentnews.org","/index.atom",80,ICEWEASEL_UA,"",60);
term_echo("*** comment_feed: downloaded atom feed");
$html=strip_headers($response);
$items=parse_atom($html);
$last_cid=file_get_contents(COMMENTS_CID_FILE);
term_echo("*** comment_feed: last cid = $last_cid");
$topcomments=get_array_bucket("<<SN_COMMENT_FEED_TOP>>");
if ($last_cid=="")
{
  $last_cid=87300;
}
$cids=array();
$m=count($items);
term_echo("*** comment_feed: $m atom feed stories to check");
for ($i=0;$i<$m;$i++)
{
  sleep(5);
  $url=$items[$i]["url"]."&threshold=-1&highlightthresh=-1&mode=flat&commentsort=0";
  $title=$items[$i]["title"];
  $host="";
  $uri="";
  $port="";
  if (get_host_and_uri($url,$host,$uri,$port)==True)
  {
    $k=$i+1;
    term_echo("[$k/$m] $url");
    $response=wget($host,$uri,$port,ICEWEASEL_UA,"",60);
    $html=strip_headers($response);
    $sid=extract_text($html,"<input type=\"hidden\" name=\"sid\" value=\"","\">");
    if ($sid===False)
    {
      continue;
    }
    $parts=explode("<div id=\"comment_top_",$html);
    array_shift($parts);
    for ($j=0;$j<count($parts);$j++)
    {
      $n=strpos($parts[$j],"\"");
      if ($n===False)
      {
        continue;
      }
      $cid=substr($parts[$j],0,$n);
      $score=extract_text($parts[$j],"class=\"score\">","</span>");
      $c=strpos($score,",");
      if ($c===False)
      {
        $score_num=substr($score,7,strlen($score)-8);
      }
      else
      {
        $score_num=substr($score,7,$c-7);
      }
      $details=extract_text($parts[$j],"<div class=\"details\">","<span class=\"otherdetails\"");
      $details=strip_tags($details);
      $details=substr(clean_text($details),3);
      $url="http://soylentnews.org/comments.pl?sid=$sid&cid=$cid";
      $pid_html=strip_ctrl_chars($parts[$j]);
      $pid_html=str_replace(" ","",$pid_html);
      $pid_delim1="ReplytoThis</a></b></p></span><spanclass=\"nbutton\"><p><b><ahref=\"//soylentnews.org/comments.pl?sid=$sid&amp;threshold=-1&amp;commentsort=0&amp;mode=flat&amp;cid=";
      $pid_delim2="\">Parent";
      $pid_test=extract_text($pid_html,$pid_delim1,$pid_delim2);
      $pid="";
      $parent_url="";
      if ($pid_test!==False)
      {
        $pid=$pid_test;
        $parent_url="http://soylentnews.org/comments.pl?sid=$sid&cid=$pid";
      }
      if ($cid>$last_cid)
      {
        $cids[]=$cid;
        $line="$cid\t$sid\t$details\t$score\t$score_num\t$title\t$url\t".time()."\t$pid\t$parent_url\t";
        #$parent_url=shorten_url($parent_url);
        #sleep(5);
        #$url=shorten_url($url);
        #$line=$line."$url\t$parent_url\n";
        file_put_contents(COMMENTS_FEED_FILE,$line,FILE_APPEND);
      }
      if (($score_num==5) and (in_array($cid,$topcomments)==False))
      {
        $msg="*** ";
        if ($cid>$last_cid)
        {
          $msg=$msg."new ";
        }
        else
        {
          #$parent_url=shorten_url($parent_url);
          #sleep(5);
          #$url=shorten_url($url);
        }
        $msg=$msg."score 5 comment: $details for article \"$title\" - $url";
        $msg=clean_text($msg);
        $msg=chr(2).chr(3)."10".$msg.chr(3).chr(2);
        append_array_bucket("<<SN_COMMENT_FEED_TOP>>",$cid);
        pm("#comments",$msg);
      }
      elseif ($cid>$last_cid)
      {
        #$msg="* new comment: $details [$score_num] \"$title\" - $url";
        $msg="* new comment: $details $score \"$title\" - $url";
        if ($parent_url<>"")
        {
          $msg=$msg." ($parent_url)";
        }
        $msg=clean_text($msg);
        pm("#comments",$msg);
      }
    }
  }
}
$new_last_cid=$last_cid;
for ($i=0;$i<count($cids);$i++)
{
  if (exec_is_integer($cids[$i])==True)
  {
    if ($cids[$i]>$new_last_cid)
    {
      $new_last_cid=$cids[$i];
    }
  }
}
file_put_contents(COMMENTS_CID_FILE,$new_last_cid);

#####################################################################################################

?>
