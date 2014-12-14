<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~journals|1700|0|0|1|crutchy|||0|php scripts/journal_feed.php
#exec:~journals-internal|1700|3600|0|1||INTERNAL||0|php scripts/journal_feed.php
startup:~join #journals
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

define("JOURNALS_FEED_FILE","../data/journals_feed.txt");
define("JOURNALS_ID_FILE","../data/journals_id.txt");

$host="soylentnews.org";
$list_uri="/journal.pl?op=top";
$port=80;

$msg=chr(3)."08"."********** ".chr(3)."03".chr(2)."SOYLENTNEWS JOURNAL FEED".chr(2).chr(3)."08"." **********";
output($msg);

$last_id=878;
if (file_exists(JOURNALS_ID_FILE)==True)
{
  $last_id=file_get_contents(JOURNALS_ID_FILE);
}

$msg="last journal = $last_id";
output($msg);

$response=wget($host,$list_uri,$port,ICEWEASEL_UA,"",60);
$html=strip_headers($response);

$delim1="<!-- start template: ID 60, journaltop;journal;default -->";
$delim2="<!-- end template: ID 60, journaltop;journal;default -->";

$html=extract_text($html,$delim1,$delim2);
if ($html===False)
{
  output("error: journal list not found");
  return;
}

$rows=explode("<tr>",$html);
array_shift($rows);
array_shift($rows);

$item_count=20;

for ($i=0;$i<max($item_count,count($rows));$i++)
{
  $cells=explode("<td valign=\"top\">",$rows[$i]);
  if (count($cells)<>4)
  {
    term_echo("*** SN JOURNAL FEED: invalid number of cells for row $i");
    continue;
  }
  # TODO: DEBUG HERE
  $id=substr($cells[2],0,strpos($cells[2],"<"));
  term_echo("*** SN JOURNAL FEED: row $i id = $id => ".$cells[2]);
}

/*$ids=array();

term_echo("*** comment_feed: $item_count feed stories to check");

$count_new=0;

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
      if ($cid>$last_cid)
      {
        $count_new++;
        $msg="*** new comment: $user_uid $score ".chr(3)."02".$subject.chr(3)." - $title_output - $comment_body_len chars -".chr(3)."04 $url";
        if ($parent_url<>"")
        {
          $msg=$msg." ".chr(3)."(parent: $parent_url)";
        }
        $msg=clean_text($msg);
        output($msg);
        output(chr(3)."08└─".$comment_body);
      }
    }
  }
}
$new_last_id=$last_id;
for ($i=0;$i<count($ids);$i++)
{
  if (exec_is_integer($ids[$i])==True)
  {
    if ($ids[$i]>$new_last_id)
    {
      $new_last_id=$ids[$i];
    }
  }
}
file_put_contents(JOURNALS_ID_FILE,$new_last_id);

output("count new = $count_new");*/

$msg=chr(3)."08"."********** ".chr(3)."03"."END FEED".chr(3)."08"." **********";
output($msg);

#####################################################################################################

function output($msg)
{
  pm("#journals",$msg);
}

#####################################################################################################

?>
