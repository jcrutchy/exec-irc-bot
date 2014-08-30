<?php

# gpl2
# by crutchy
# 30-aug-2014

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
term_echo("******************* SOYLENTNEWS COMMENT FEED *******************");
$response=wget("soylentnews.org","/index.atom",80,ICEWEASEL_UA,"",60);
term_echo("*** comment_feed: downloaded atom feed");
$html=strip_headers($response);
$items=parse_atom($html);
$last_cid=get_bucket("<<SN_COMMENT_FEED_CID>>");
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
      $score_num=substr($score,7,strlen($score)-8);
      $details=extract_text($parts[$j],"<div class=\"details\">","<span class=\"otherdetails\"");
      $details=strip_tags($details);
      $url="http://soylentnews.org/comments.pl?sid=$sid&cid=$cid";
      if (($score_num==5) and (in_array($cid,$topcomments)==False))
      {
        $msg="*** score 5 comment $details for article \"$title\" - $url";
        $msg=clean_text($msg);
        $msg=chr(2).chr(3)."10".$msg.chr(3).chr(2);
        append_array_bucket("<<SN_COMMENT_FEED_TOP>>",$cid);
        pm("#comments",$msg);
      }
      if ($cid>$last_cid)
      {
        $cids[]=$cid;
        $line="$cid\t$sid\t$details\t$score\t$score_num\t$title\t$url\t".time();
        file_put_contents(COMMENTS_FEED_FILE,$line,FILE_APPEND);
        $msg="*** new comment $details $score for article \"$title\" - $url";
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
set_bucket("<<SN_COMMENT_FEED_CID>>",$new_last_cid);

#####################################################################################################

?>
