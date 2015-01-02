<?php

#####################################################################################################

/*
exec:~copyright|0|0|0|0|crutchy||||php scripts/copyright.php %%trailing%%
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");
require_once("copyright_lib.php");

$trailing=trim($argv[1]);
$url=get_redirected_url($trailing);
$host="";
$uri="";
$port="";
if (get_host_and_uri($url,$host,$uri,$port)==True)
{
  $response=wget($host,$uri,$port,ICEWEASEL_UA,"",60);
  $html=strip_headers($response);
  $violated_url=check_copyright($html);
  if ($violated_url!==False)
  {
    privmsg("*** possible copyright violation of $violated_url at $url");
  }
}

#####################################################################################################

?>
