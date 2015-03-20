<?php

#####################################################################################################

/*
exec:~comments|1700|0|0|1|||||php scripts/comment_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~comments-internal|1700|3600|0|1||INTERNAL|||php scripts/comment_feed.php %%trailing%% %%dest%% %%nick%% %%alias%%
startup:~join #comments
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

define("COMMENTS_FEED_FILE","../data/comments_feed.txt");
define("COMMENTS_CID_FILE","../data/comments_cid.txt");
define("COMMENTS_TOP_FILE","../data/comments_top.txt");
define("COMMENTS_FILTERS_FILE","../data/comments_filters.txt");

define("MAIN_FEED_CHANNEL","#comments");

$trailing=trim($argv[1]);
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));
$alias=strtolower(trim($argv[4]));

$filters=load_settings(COMMENTS_FILTERS_FILE," ");

if ($alias=="~comments")
{
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  $action=strtolower($parts[0]);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  switch ($action)
  {
    case "feed":
      if (users_get_account($nick)=="crutchy")
      {
        break;
      }
      return;
    case "filter-add":
      # ~comments filter-add %id% %target% %field% %pattern%
      # %id% = unique name to identify filter
      # %target% = channel or nick to send filtered comments to
      # %field% = user|uid|score|score_num|subject|title|comment_body
      # %pattern% = regexp pattern for use with preg_match
      $parts=explode(" ",$trailing);
      delete_empty_elements($parts);
      if (count($parts)<4)
      {
        return;
      }
      $id=$parts[0];
      $target=$parts[1];
      $field=$parts[2];
      array_shift($parts);
      array_shift($parts);
      array_shift($parts);
      $pattern=trim(implode(" ",$parts));
      $filter=array();
      $filter["id"]=$id;
      $filter["target"]=$target;
      $filter["field"]=$field;
      $filter["pattern"]=$pattern;
      $filters[$id]=base64_encode(serialize($filter));
      save_settings($filters,COMMENTS_FILTERS_FILE," ");
      privmsg("  comments feed filter \"$id\" added [target = \"$target\", field=\"$field\", pattern=\"$pattern\"]");
      return;
    case "filter-delete":
      # ~comments filter-delete %id%
      $id=trim($trailing);
      if (isset($filters[$id])==True)
      {
        unset($filters[$id]);
        save_settings($filters,COMMENTS_FILTERS_FILE," ");
        privmsg("  comments feed filter \"$id\" deleted");
      }
      else
      {
        privmsg("  comments feed filter \"$id\" not found");
      }
      return;
    default:
      return;
  }
}

foreach ($filters as $id => $filter)
{
  $filters[$id]=unserialize(base64_decode($filter));
}

$host="soylentnews.org";
$feed_uri="/index.xml";
$port=80;

$msg=chr(3)."08"."********** ".chr(3)."03".chr(2)."SOYLENTNEWS COMMENT FEED".chr(2).chr(3)."08"." **********";
pm(MAIN_FEED_CHANNEL,$msg);

$last_cid=87400;
if (file_exists(COMMENTS_CID_FILE)==True)
{
  $last_cid=file_get_contents(COMMENTS_CID_FILE);
}

$msg="last cid = $last_cid";
pm(MAIN_FEED_CHANNEL,$msg);

$response=wget($host,$feed_uri,$port,ICEWEASEL_UA,"",60);

$html=strip_headers($response);

$items=parse_xml($html);

$topcomments=array();
if (file_exists(COMMENTS_TOP_FILE)==True)
{
  $data=file_get_contents(COMMENTS_TOP_FILE);
  $topcomments=explode("\n",$data);
  delete_empty_elements($topcomments);
}

$cids=array();
$item_count=20;

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
    $sid=extract_text($html,"<input type=\"hidden\" name=\"sid\" value=\"","\">");
    if ($sid===False)
    {
      continue;
    }
    $parts=explode("<div id=\"comment_top_",$html);
    array_shift($parts);
    for ($j=0;$j<count($parts);$j++)
    {
      sleep(1);
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
      $subject=html_decode($subject);
      $subject=html_decode($subject);
      $comment_body=extract_text($parts[$j],"<div id=\"comment_body_$cid\">","</div>");
      $comment_body=replace_ctrl_chars($comment_body," ");
      $comment_body=str_replace("</p>"," ",$comment_body);
      $comment_body=str_replace("<p>"," ",$comment_body);
      $comment_body=str_replace("<br>"," ",$comment_body);
      $comment_body=trim(strip_tags($comment_body));
      $comment_body=str_replace("  "," ",$comment_body);
      $comment_body=html_decode($comment_body);
      $comment_body=html_decode($comment_body);

      $record=array();
      $record["user"]=$user;
      $record["uid"]=$uid;
      $record["score"]=$score;
      $record["score_num"]=$score_num;
      $record["subject"]=$subject;
      $record["title"]=$title;
      $record["comment_body"]=$comment_body;

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
        output(False,$msg);
        if ($top_score_pub==0)
        {
          $top_score_pub=1;
        }
        output(False,chr(3)."08└─".$comment_body);
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
        output($record,$msg);
        output($record,chr(3)."08└─".$comment_body);
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

$msg="count new = $count_new";
pm(MAIN_FEED_CHANNEL,$msg);
$msg="count top = $count_top";
pm(MAIN_FEED_CHANNEL,$msg);
$msg=chr(3)."08"."********** ".chr(3)."03"."END FEED".chr(3)."08"." **********";
pm(MAIN_FEED_CHANNEL,$msg);

#####################################################################################################

function output($record,$msg)
{
  global $filters;
  pm(MAIN_FEED_CHANNEL,$msg);
  if ($record===False)
  {
    return;
  }
  foreach ($filters as $id => $filter)
  {
    # $filter["id"]
    # $filter["target"]
    # $filter["field"]
    # $filter["pattern"]
    # %id% = unique name to identify filter
    # %target% = channel or nick to send filtered comments to
    # %field% = user|uid|score|score_num|subject|title|comment_body
    # %pattern% = regexp pattern for use with preg_match
    # $record["user"]
    # $record["uid"]
    # $record["score"]
    # $record["score_num"]
    # $record["subject"]
    # $record["title"]
    # $record["comment_body"]
    if (isset($record[$filter["field"]])==False)
    {
      return;
    }
    if ($record[$filter["field"]]==$filter["pattern"])
    {
      pm($filter["target"],$msg);
    }
    elseif (preg_match("~".$filter["pattern"]."~",$record[$filter["field"]])==1)
    {
      pm($filter["target"],$msg);
    }
  }
}

#####################################################################################################

?>
