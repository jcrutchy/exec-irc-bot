<?php

#####################################################################################################

/*
exec:~title|30|0|0|0|||||php scripts/title.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");
$trailing=trim($argv[1]);
$url=get_redirected_url($trailing);
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

$breakcode="return ((strpos(strtolower(\$response),\"</title>\")!==False) or (strlen(\$response)>=10000));";
$response=wget($host,$uri,$port,ICEWEASEL_UA,"",20,$breakcode,256);

#var_dump($response);
term_echo("*** TITLE => response bytes: ".strlen($response));

$html=strip_headers($response);

#var_dump($html);

$title=extract_raw_tag($html,"title");

$title=html_entity_decode($title,ENT_QUOTES,"UTF-8");
$title=html_entity_decode($title,ENT_QUOTES,"UTF-8");

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
    if (strpos($filtered_url,$filtered_title)!==False)
    {
      privmsg("portion of title left of \" | \" exists in url");
      return;
    }
  }
  if (strpos($title,"119.18.0.66")===False)
  {
    privmsg(chr(3)."13".$title);
  }
  else
  {
    term_echo("bot host ip address exists in url");
  }
}
else
{
  privmsg("title exists in url");
}

#####################################################################################################

?>
