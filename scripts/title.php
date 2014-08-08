<?php

# gpl2
# by crutchy
# 8-aug-2014

#####################################################################################################

# http://www.smh.com.au/world/barack-obamas-military-action-in-iraq-speaks-to-core-audience-20140808-1021gd.html

require_once("lib.php");
$trailing=trim($argv[1]);
$url=get_redirected_url($trailing);
if ($url===False)
{
  return;
}
$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  return;
}
$response=wget($host,$uri,$port);
$html=strip_headers($response);

$title=extract_raw_tag($html,"title");

$filtered_url=strtolower(filter_non_alpha_num($url));
$filtered_title=strtolower(filter_non_alpha_num($title));

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
  privmsg($title);
}
else
{
  privmsg("title exists in url");
}

#####################################################################################################

?>
