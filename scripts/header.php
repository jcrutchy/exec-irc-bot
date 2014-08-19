<?php

# gpl2
# by crutchy
# 19-aug-2014

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);

$parts=explode(" ",$trailing);

if (count($parts)<>2)
{
  return;
}

$header=$parts[0];
$url=$parts[1];

$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  return;
}
$response=wget($host,$uri,$port);

$header_value=exec_get_header($response,$header);

if ($header_value<>"")
{
  privmsg("$header header for $url = $header_value");
}

#####################################################################################################

?>
