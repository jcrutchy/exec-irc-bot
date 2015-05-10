<?php

#####################################################################################################

/*
exec:~title|30|0|0|0|||||php scripts/title.php %%trailing%% %%alias%%
exec:~sizeof|30|0|0|0|*||#journals,#test,#Soylent,#,#exec,#dev||php scripts/title.php %%trailing%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
$trailing=trim($argv[1]);
$alias=trim($argv[2]);
$url=$trailing;
$url=get_redirected_url($url);
if ($url===False)
{
  term_echo("get_redirected_url=false");
  return;
}
$host="";
$uri="";
$port=80;

if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  term_echo("get_host_and_uri=false");
  return;
}

if ($alias=="~sizeof")
{
  $headers=whead($host,$uri,$port);
  $content_length=exec_get_header($headers,"content-length",False);
  if ($content_length<>"")
  {
    if ($content_length>(1024*1024))
    {
      privmsg(chr(3)."13".(round($content_length/1024/1024,3))." Mb (header)");
    }
    elseif ($content_length>1024)
    {
      privmsg(chr(3)."13".(round($content_length/1024,3))." kb (header)");
    }
    else
    {
      privmsg(chr(3)."13".$content_length." bytes (header)");
    }
    return;
  }
}

$breakcode="return (strlen(\$response)>=2000000);";
if ($alias=="~title")
{
  $breakcode="return ((strpos(strtolower(\$response),\"</title>\")!==False) or (strlen(\$response)>=10000));";
}
$response=wget($host,$uri,$port,ICEWEASEL_UA,"",20,$breakcode,256);

var_dump($response);
term_echo("*** TITLE => response bytes: ".strlen($response));

$html=strip_headers($response);

if ($alias=="~sizeof")
{
  if ($content_length>(1024*1024))
  {
    privmsg(chr(3)."13".(round($content_length/1024/1024,3))." Mb (downloaded)");
  }
  elseif ($content_length>1024)
  {
    privmsg(chr(3)."13".(round($content_length/1024,3))." kb (downloaded)");
  }
  else
  {
    privmsg(chr(3)."13".$content_length." bytes (downloaded)");
  }
  return;
}

#var_dump($html);

$title=extract_raw_tag($html,"title");

$title=html_decode($title);
$title=html_decode($title);

$filtered_url=strtolower(filter_non_alpha_num($url));
$filtered_title=strtolower(filter_non_alpha_num($title));

if ($filtered_title=="")
{
  term_echo("filtered_title is empty");
  return;
}

term_echo("  filtered_url = $filtered_url");
term_echo("filtered_title = $filtered_title");

if (strpos($filtered_url,$filtered_title)===False)
{
  $i=strpos($title," - ");
  if ($i!==False)
  {
    $filtered_title=strtolower(filter_non_alpha_num(substr($title,0,$i)));
    if (strpos($filtered_url,$filtered_title)!==False)
    {
      privmsg("portion of title left of \" - \" exists in url");
      return;
    }
  }
  $i=strpos($title," | ");
  if ($i!==False)
  {
    $filtered_title=strtolower(filter_non_alpha_num(substr($title,0,$i)));
    term_echo("*** filtered_title = $filtered_title");
    term_echo("*** filtered_url   = $filtered_url");
    if (strpos($filtered_url,$filtered_title)!==False)
    {
      privmsg("portion of title left of \" | \" exists in url");
      return;
    }
  }
  privmsg(chr(3)."13".$title);
}
else
{
  privmsg("title exists in url");
}

#####################################################################################################

?>
