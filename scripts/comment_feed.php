<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~comments|1700|0|0|1|crutchy|||0|php scripts/comment_feed.php
exec:~comments-internal|1700|1800|0|1||INTERNAL||0|php scripts/comment_feed.php
*/

#####################################################################################################

# http://tmbvm.ddns.net/api.pl?m=comment&op=latest
# http://tmbvm.ddns.net/api.pl?m=comment&op=latest&since=27700
# limited to 50 comments (absolute)
# http://tmbvm.ddns.net/api.pl?m=user&op=get_nick&uid=18

/*
<crutchy> could make some funky settings out of this
<crutchy> instead of privmsging a channel, it could notice any nick that registered to receive them
<crutchy> and could even set a score threshold etc
<crutchy> personalized SN comment feeds :D
add comment topic setting
filter by story (reset when story drops off atom), filter by user, filter by content/subject keyword, etc
*/

# TODO: highlight quotes different color

ini_set("display_errors","on");
require_once("lib.php");
require_once("feeds_lib.php");
#require_once("copyright_lib.php");

$subscribers=get_array_bucket("<<comment_feed_subscribers>>"); # TODO

define("COMMENTS_FEED_FILE","../data/comments_feed.txt");
define("COMMENTS_CID_FILE","../data/comments_cid.txt");
define("COMMENTS_TOP_FILE","../data/comments_top.txt");

$host="soylentnews.org";
$feed_uri="/index.xml";
$port=80;

$msg=chr(3)."08"."********** ".chr(3)."03".chr(2)."SOYLENTNEWS COMMENT FEED".chr(2).chr(3)."08"." **********";
output($msg,True);

$last_cid=87400;
if (file_exists(COMMENTS_CID_FILE)==True)
{
  $last_cid=file_get_contents(COMMENTS_CID_FILE);
}

$msg="last cid = $last_cid";
output($msg,True);

#$response=wget($host,"/index.atom",$port,ICEWEASEL_UA,"",60);
#term_echo("*** comment_feed: downloaded atom feed");

$response=wget($host,$feed_uri,$port,ICEWEASEL_UA,"",60);
term_echo("*** comment_feed: downloaded story feed");

$html=strip_headers($response);

#$items=parse_atom($html);
$items=parse_xml($html);

$topcomments=array();
if (file_exists(COMMENTS_TOP_FILE)==True)
{
  $data=file_get_contents(COMMENTS_TOP_FILE);
  $topcomments=explode("\n",$data);
  delete_empty_elements($topcomments);
}

$cids=array();
#$item_count=count($items);
$item_count=20;

#term_echo("*** comment_feed: $item_count atom feed stories to check");
term_echo("*** comment_feed: $item_count feed stories to check");

$top_score_pub=0;

$count_new=0;
$count_top=0;

for ($i=0;$i<$item_count;$i++)
{
  if (isset($items[$i])==False)
  {
    continue;
  }
  sleep(5);
  $url=$items[$i]["url"]."&threshold=-1&highlightthresh=-1&mode=flat&commentsort=0";
  $title=$items[$i]["title"];
  $title_output=chr(3)."06".$title.chr(3);
  $host="";
  $uri="";
  $port="";
  if (get_host_and_uri($url,$host,$uri,$port)==True)
  {
    $k=$i+1;
    term_echo("[$k/$item_count] $url");
    $response=wget($host,$uri,$port,ICEWEASEL_UA,"",60);
    $html=strip_headers($response);
    /*$violated_url=check_copyright($html);
    if ($violated_url!==False)
    {
      output("*** possible copyright violation of $violated_url at $url");
    }*/
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
      $user=$details;
      $uid=0;
      $c1=strpos($details,"(");
      $c2=strpos($details,")");
      if (($c1!==False) and ($c2!==False))
      {
        if (($c1<$c2) and ($c2==(strlen($details)-1)))
        {
          $user=trim(substr($details,0,$c1-1));
          $uid=substr($details,$c1+1,$c2-$c1-1);
        }
      }
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
      $comment_body=replace_ctrl_chars($comment_body," ");
      $comment_body=str_replace("</p>"," ",$comment_body);
      $comment_body=str_replace("<p>"," ",$comment_body);
      $comment_body=str_replace("<br>"," ",$comment_body);
      $comment_body=trim(strip_tags($comment_body));
      $comment_body=str_replace("  "," ",$comment_body);
      $comment_body=html_entity_decode($comment_body,ENT_QUOTES,"UTF-8");
      $comment_body=html_entity_decode($comment_body,ENT_QUOTES,"UTF-8");
      $comment_body_len=strlen($comment_body);
      $max_comment_length=300;
      if (strlen($comment_body)>$max_comment_length)
      {
        $comment_body=trim(substr($comment_body,0,$max_comment_length))."...";
      }
      if ($cid>$last_cid)
      {
        $cids[]=$cid;
        $line="$cid\t$sid\t$user\t$uid\t$score\t$score_num\t$subject\t$title\t$url\t".time()."\t$pid\t$parent_url\t$comment_body_len\n";
        file_put_contents(COMMENTS_FEED_FILE,$line,FILE_APPEND);
      }
      $user_uid=chr(3)."03".$user.chr(3);
      if ($uid>0)
      {
        $user_uid=$user_uid." [$uid]";
      }
      if (($score_num==5) and (in_array($cid,$topcomments)==False))
      {
        $count_top++;
        $msg=chr(3)."08*** ";
        if ($cid>$last_cid)
        {
          $count_new++;
          $msg=$msg."new ";
        }
        $msg=$msg.chr(3)."score 5 comment: $user_uid ".chr(3)."02".$subject.chr(3)." - $title_output - $comment_body_len chars - ".chr(3)."04 $url";
        if ($parent_url<>"")
        {
          $msg=$msg." ".chr(3)."(parent: $parent_url)";
        }
        $msg=clean_text($msg);
        $msg=chr(2).$msg.chr(2);
        file_put_contents(COMMENTS_TOP_FILE,$cid."\n",FILE_APPEND);
        output($msg);
        if ($top_score_pub==0)
        {
          #pm("#soylent",chr(3)."score 5 comment: $user_uid ".chr(3)."02".$subject.chr(3)." - $title_output - ".chr(3)."04 $url");
          $top_score_pub=1;
        }
        /*for ($k=0;$k<count($subscribers);$k++)
        {
          pm($subscribers[$k],chr(3)."08└─ ".$comment_body);
        }*/
        output(chr(3)."08└─".$comment_body);
      }
      elseif ($cid>$last_cid)
      {
        $count_new++;
        $msg="*** new comment: $user_uid $score ".chr(3)."02".$subject.chr(3)." - $title_output - $comment_body_len chars -".chr(3)."04 $url";
        if ($parent_url<>"")
        {
          $msg=$msg." ".chr(3)."(parent: $parent_url)";
        }
        $msg=clean_text($msg);
        output($msg);
        /*for ($k=0;$k<count($subscribers);$k++)
        {
          pm($subscribers[$k],chr(3)."08^ ".$comment_body);
        }*/
        output(chr(3)."08└─".$comment_body);
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

output("count new = $count_new",True);
output("count top = $count_top",True);
$msg=chr(3)."08"."********** ".chr(3)."03"."END FEED".chr(3)."08"." **********";
output($msg,True);

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
