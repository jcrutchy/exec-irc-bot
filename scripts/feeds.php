<?php

# gpl2
# by crutchy
# 3-june-2014

# /nas/server/git/data/atom.feeds contains a list of urls for scraping

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

return;

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];

$last_run=get_bucket("comments_last_run");

$html=wget("soylentnews.org","/index.atom",80);
$html=strip_headers($html);

$delim1="<id>http://soylentnews.org/article.pl?sid=";
$delim2="&amp;from=rss</id>";
$tag_title="title";
$tag_updated="updated";
$tag_dept="slash:department";

$links=array();
$titles=array();
$times=array();
$depts=array();
$parts=explode($delim1,$html);
$latest_article_update=0;
$latest_article_title="";
for ($i=1;$i<count($parts);$i++)
{
  $tmp=$parts[$i];
  $x2=strpos($tmp,$delim2);
  $x3=strpos($tmp,"<$tag_updated>");
  $x4=strpos($tmp,"</$tag_updated>");
  $x5=strpos($tmp,"<$tag_title>");
  $x6=strpos($tmp,"</$tag_title>");
  $x7=strpos($tmp,"<$tag_dept>");
  $x8=strpos($tmp,"</$tag_dept>");
  if (($x2===False) or ($x3===False) or ($x4===False) or ($x5===False) or ($x6===False) or ($x7===False) or ($x8===False))
  {
    continue;
  }
  $tmp_link=trim(substr($tmp,0,$x2));
  $j=$x3+strlen("<$tag_updated>");
  $tmp_updated=trim(substr($tmp,$j,$x4-$j));
  $j=$x5+strlen("<$tag_title>");
  $tmp_title=trim(substr($tmp,$j,$x6-$j));
  $j=$x7+strlen("<$tag_dept>");
  $tmp_dept=trim(substr($tmp,$j,$x8-$j));
  if (($tmp_link=="") or ($tmp_updated=="") or ($tmp_title=="") or ($tmp_dept==""))
  {
    continue;
  }
  # 2014-05-29T12:09:00+00:00
  $tmp_updated=str_replace("T"," ",$tmp_updated);
  $ts_arr=date_parse_from_format("Y-m-d H:i:sP",$tmp_updated);
  $ts=mktime($ts_arr["hour"],$ts_arr["minute"],$ts_arr["second"],$ts_arr["month"],$ts_arr["day"],$ts_arr["year"]);
  if ($ts>$last_run)
  {
    $links[]=$tmp_link;
    $titles[]=$tmp_title;
    $times[]=$ts;
    $depts[]=$tmp_dept;
  }
  if ($ts>$latest_article_update)
  {
    $latest_article_update=$ts;
    $latest_article_title=$tmp_title;
  }
}

term_echo("latest article title   = $latest_article_title");
term_echo("latest article updated = $latest_article_update");
term_echo("script last run        = ".round($last_run,0));
term_echo("new articles           = ".count($links));

for ($i=0;$i<count($links);$i++)
{
  term_echo($links[$i]);
  term_echo($titles[$i]);
  term_echo($times[$i]);
  term_echo($depts[$i]);
  echo "IRC_RAW :".NICK_EXEC." PRIVMSG #> :[SoylentNews] - ".$titles[$i]." - http://soylentnews.org/article.pl?sid=".$links[$i]." - ".$depts[$i]."\n";
}

set_bucket("comments_last_run",microtime(True));

#####################################################################################################

?>
