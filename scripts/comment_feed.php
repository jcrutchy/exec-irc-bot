<?php

# gpl2
# by crutchy
# 3-sep-2014

#####################################################################################################

/*
<crutchy> could make some funky settings out of this
<crutchy> instead of privmsging a channel, it could notice any nick that registered to receive them
<crutchy> and could even set a score threshold etc
<crutchy> personalized SN comment feeds :D
add comment topic setting
filter by story (reset when story drops off atom), filter by user, filter by content/subject keyword, etc
*/

ini_set("display_errors","on");
require_once("lib.php");
require_once("feeds_lib.php");
require_once("lib_buckets.php");

$subscribers=array("crutchy");

define("COMMENTS_FEED_FILE","../data/comments_feed.txt");
define("COMMENTS_CID_FILE","../data/comments_cid.txt");
define("COMMENTS_TOP_FILE","../data/comments_top.txt");

$msg="********** SOYLENTNEWS COMMENT FEED **********";
output($msg,True);

$last_cid=87400;
if (file_exists(COMMENTS_CID_FILE)==True)
{
  $last_cid=file_get_contents(COMMENTS_CID_FILE);
}

$msg="last cid = $last_cid";
output($msg,True);

$response=wget("soylentnews.org","/index.atom",80,ICEWEASEL_UA,"",60);
term_echo("*** comment_feed: downloaded atom feed");
$html=strip_headers($response);
$items=parse_atom($html);

#$topcomments=get_array_bucket("<<SN_COMMENT_FEED_TOP>>");

$topcomments=array();
if (file_exists(COMMENTS_TOP_FILE)==True)
{
  $data=file_get_contents(COMMENTS_TOP_FILE);
  $topcomments=explode("\n",$data);
  delete_empty_elements($topcomments);
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
      $subject_delim1="<h4><a name=\"$cid\">";
      $subject_delim2="</a>";
      $subject=extract_text($parts[$j],$subject_delim1,$subject_delim2);
      $subject=trim(strip_tags($subject));
      $subject=str_replace("  "," ",$subject);
      $subject=html_entity_decode($subject,ENT_QUOTES,"UTF-8");
      $subject=html_entity_decode($subject,ENT_QUOTES,"UTF-8");
      $comment_body=extract_text($parts[$j],"<div id=\"comment_body_$cid\">","</div>");
      $comment_body=trim(strip_tags($comment_body));
      $comment_body=str_replace("  "," ",$comment_body);
      $comment_body=html_entity_decode($comment_body,ENT_QUOTES,"UTF-8");
      $max_comment_length=300;
      if (strlen($comment_body)>$max_comment_length)
      {
        $comment_body=trim(substr($comment_body,0,$max_comment_length))."...";
      }
      if ($cid>$last_cid)
      {
        $cids[]=$cid;
        $line="$cid\t$sid\t$details\t$score\t$score_num\t$subject\n$title\t$url\t".time()."\t$pid\t$parent_url\t";
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
        $msg=$msg."score 5 comment: $details \"$subject\" - \"$title\" - $url";
        if ($parent_url<>"")
        {
          $msg=$msg." (parent: $parent_url)";
        }
        $msg=clean_text($msg);
        $msg=chr(2).chr(3)."10".$msg.chr(3).chr(2);
        #append_array_bucket("<<SN_COMMENT_FEED_TOP>>",$cid);
        file_put_contents(COMMENTS_TOP_FILE,$cid."\n",FILE_APPEND);
        output($msg);
        for ($k=0;$k<count($subscribers);$k++)
        {
          pm($subscribers[$k],"^ ".$comment_body);
        }
      }
      elseif ($cid>$last_cid)
      {
        $msg="*** new comment: $details $score \"$subject\" - \"$title\" - $url";
        if ($parent_url<>"")
        {
          $msg=$msg." (parent: $parent_url)";
        }
        $msg=clean_text($msg);
        output($msg);
        for ($k=0;$k<count($subscribers);$k++)
        {
          pm($subscribers[$k],"^ ".$comment_body);
        }
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

/*$data="";
for ($i=0;$i<count($topcomments);$i++)
{
  $data=$data.$topcomments[$i]."\n";
}
file_put_contents(COMMENTS_TOP_FILE,$data);*/

#####################################################################################################

function output($msg,$term=False)
{
  global $subscribers;
  if ($term==True)
  {
    term_echo($msg);
  }
  pm("#comments",$msg);
  for ($i=0;$i<count($subscribers);$i++)
  {
    pm($subscribers[$i],$msg);
  }
}

#####################################################################################################

?>
