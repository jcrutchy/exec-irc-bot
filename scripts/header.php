<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~header|20|0|0|0|||||php scripts/header.php %%trailing%% %%alias%%
exec:~header-login|20|0|0|0|||||php scripts/header.php %%trailing%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");
require_once("sn_lib.php");

$trailing=trim($argv[1]);
$alias=strtolower(trim($argv[2]));

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

switch ($alias)
{
  case "~header":
    get_header($host,$uri,$port,$header,$url);
    break;
  case "~header-login":
    if (strtolower($host)=="soylentnews.org")
    {
      get_header_login($host,$uri,$port,$header,$url);
    }
    break;
}

#####################################################################################################

function get_header($host,$uri,$port,$header,$url)
{
  $response=whead($host,$uri,$port);
  $header_value=exec_get_header($response,$header,False);
  if ($header_value<>"")
  {
    privmsg("$header header for $url = $header_value");
  }
}

#####################################################################################################

function get_header_login($host,$uri,$port,$header,$url)
{
  $extra_headers=array();
  $extra_headers["Cookie"]=sn_login();
  $response=whead($host,$uri,$port,ICEWEASEL_UA,$extra_headers);
  sn_logout();
  $header_value=exec_get_header($response,$header,False);
  if ($header_value<>"")
  {
    privmsg("$header header for $url = $header_value");
  }
}

#####################################################################################################

?>
