<?php

#####################################################################################################

/*
exec:~submit|120|0|0|1|*||||php scripts/submit.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~filter|120|0|0|1|*||||php scripts/submit.php %%trailing%% %%dest%% %%nick%% %%alias%%
*/

#####################################################################################################

# TODO: INCORPORATE HYPERLINK IN SUMMARY TEXT
# TODO: USE #rss-bot FOR MASS TEST INPUTS BUT DON'T ACTUALLY SUBMIT TO SITE

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("usage: ~submit <url>");
  return;
}

$debug_mode=False;
$parts=explode(" ",$trailing);
if ($parts[0]=="debug")
{
  $debug_mode=True;
  array_shift($parts);
  $trailing=implode(" ",$parts);
}

$url=$trailing;
$response=wget_proper($url);
$source_html=strip_headers($response);
$source_title=extract_raw_tag($source_html,"title");

term_echo($source_title);

$i=strpos($source_title,"--");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

$i=strpos($source_title,"|");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

$i=strpos($source_title," - ");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

$i=strpos($source_title," : ");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

$i=strpos($source_title," — ");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

$i=strpos($source_title," • ");
if ($i!==False)
{
  $source_title=trim(substr($source_title,0,$i));
}

if (($source_title===False) or ($source_title==""))
{
  privmsg("error: title not found or empty");
  return;
}

$source_title=html_decode($source_title);
$source_title=html_decode($source_title);

$source_body=extract_meta_content($source_html,"description");

if (($source_body===False) or ($source_body==""))
{
  $source_body=extract_meta_content($source_html,"og:description","property");
  if (($source_body===False) or ($source_body==""))
  {
    privmsg("error: description meta content not found or empty");
    return;
  }
}

/*$html=$source_html;

$article=extract_raw_tag($html,"article");
if ($article!==False)
{
  $html=$article;
}

strip_all_tag($html,"head");
strip_all_tag($html,"script");
strip_all_tag($html,"style");
#strip_all_tag($html,"a");
strip_all_tag($html,"strong");
$html=strip_tags($html,"<p>");

$html=lowercase_tags($html);

$html=explode("<p",$html);

$source_body=array();

for ($i=0;$i<count($html);$i++)
{
  $parts=explode(">",$html[$i]);
  if (count($parts)>=2)
  {
    array_shift($parts);
    $html[$i]=implode(">",$parts);
  }
  $html[$i]=strip_tags($html[$i]);
  $html[$i]=clean_text($html[$i]);
  $host_parts=explode(".",$host);
  for ($j=0;$j<count($host_parts);$j++)
  {
    if (strlen($host_parts[$j])>3)
    {
      if (strpos(strtolower($html[$i]),strtolower($host_parts[$j]))!==False)
      {
        continue 2;
      }
    }
  }
  if (filter($html[$i],"0123456789")<>"")
  {
    continue;
  }
  if (strlen($html[$i])>1)
  {
    if ($html[$i][strlen($html[$i])-1]<>".")
    {
      continue;
    }
    while (True)
    {
      $j=strlen($html[$i])-1;
      if ($j<0)
      {
        break;
      }
      $c=$html[$i][$j];
      if ($c==".")
      {
        break;
      }
      $html[$i]=substr($html[$i],0,$j);
    }
  }
  if (strlen($html[$i])>100)
  {
    $source_body[]=$html[$i];
  }
}

#var_dump($source_body);
#return;

$source_body=implode("\n\n",$source_body);

#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($alias=="~filter")
{
  $source_body=$source_title."\n\n".$source_body."\n\n".$url;
  $host="paste.my.to";
  $port=80;
  $uri="/";
  $params=array();
  $params["content"]=$source_body;
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
  privmsg("  ".exec_get_header($response,"location"));
  return;
}

$source_body=html_decode($source_body);
$source_body=html_decode($source_body);

#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# the following code posts a submission to SoylentNews

#return;

if ($nick<>"crutchy")
{
  privmsg("exec's submit script is borken. blame crutchy");
  return;
} */

$host="soylentnews.org";
if ($debug_mode==True)
{
  $host="dev.soylentnews.org";
}
$port=443;
$uri="/submit.pl";
$response=wget($host,$uri,$port,ICEWEASEL_UA);
$html=strip_headers($response);
$reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
if ($reskey===False)
{
  privmsg("error: unable to extract reskey");
  return;
}

sleep(30);

$params=array();
$params["reskey"]=$reskey;
#$params["name"]=trim(substr($nick,0,50));
$params["name"]=get_bot_nick();
$params["email"]="";
$params["subj"]=trim(substr($source_title,0,100));
$params["primaryskid"]="1";
$params["tid"]="6";
$params["sub_type"]="plain";
$params["story"]=$source_body."\n\n".$url."\n\n-- submitted from IRC";
$params["op"]="SubmitStory";

$response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
$html=strip_headers($response);

strip_all_tag($html,"head");
strip_all_tag($html,"script");
strip_all_tag($html,"style");
strip_all_tag($html,"a");
$html=strip_tags($html);
$html=clean_text($html);

var_dump($html); # TODO: extract success/error message and output to IRC

if (strpos($html,"Thanks for the submission.")!==False)
{
  privmsg("submission successful - https://$host/submit.pl?op=list");
}
else
{
  privmsg("error: something went wrong with your submission");
}

#####################################################################################################

?>
