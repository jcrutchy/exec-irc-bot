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

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];

if ($trailing=="")
{
  privmsg("usage: ~submit <url>");
  return;
}

$url=get_redirected_url($trailing);
if ($url===False)
{
  privmsg("error: unable to download source (get_redirected_url)");
  return;
}
$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  privmsg("error: unable to download source (get_host_and_uri)");
  return;
}
$response=wget($host,$uri,$port);
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

$source_title=html_entity_decode($source_title,ENT_QUOTES,"UTF-8");
$source_title=html_entity_decode($source_title,ENT_QUOTES,"UTF-8");

/*$source_body=extract_meta_content($source_html,"description");

if (($source_body===False) or ($source_body==""))
{
  $source_body=extract_meta_content($source_html,"og:description","property");
  if (($source_body===False) or ($source_body==""))
  {
    privmsg("error: description meta content not found or empty");
    return;
  }
}*/

$html=$source_html;

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
  /*if (filter($html[$i],"0123456789")<>"")
  {
    continue;
  }*/
  if (strlen($html[$i])>1)
  {
    if ($html[$i][strlen($html[$i])-1]<>".")
    {
      continue;
    }
  }
  if (strlen($html[$i])>100)
  {
    $source_body[]=$html[$i];
  }
}

var_dump($source_body);
return;

$source_body=implode("\n\n",$source_body);

$source_body=$source_title."\n\n".$source_body."\n\n".$url;

#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($alias=="~filter")
{
  $host="paste.my.to";
  $port=80;
  $uri="/";
  $params=array();
  $params["content"]=$source_body;
  $response=wpost($host,$uri,$port,ICEWEASEL_UA,$params);
  privmsg("  ".exec_get_header($response,"location"));
  return;
}

#$source_body=html_entity_decode($source_body,ENT_QUOTES,"UTF-8");
#$source_body=html_entity_decode($source_body,ENT_QUOTES,"UTF-8");

#~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
# the following code posts a submission to SoylentNews

return;

if ($nick<>"crutchy")
{
  privmsg("exec's submit script is borken. blame crutchy");
  return;
}

$host="dev.soylentnews.org";
$port=80;
$uri="/submit.pl";
$response=wget($host,$uri,$port,ICEWEASEL_UA);
$html=strip_headers($response);
$reskey=extract_text($html,"<input type=\"hidden\" id=\"reskey\" name=\"reskey\" value=\"","\">");
if ($reskey===False)
{
  privmsg("error: unable to extract reskey");
  return;
}

sleep(25);

$params=array();
$params["reskey"]=$reskey;
#$params["name"]=trim(substr($nick,0,50));
$params["name"]=NICK_EXEC;
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

if (strpos($html,"Perhaps you would like to enter an email address or a URL next time. Thanks for the submission.")!==False)
{
  privmsg("submission successful - https://$host/submit.pl?op=list");
}
else
{
  privmsg("error: something went wrong with your submission");
}

# TODO: testing... much more testing

#####################################################################################################

?>
