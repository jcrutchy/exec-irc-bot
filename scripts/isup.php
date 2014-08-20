<?php

# gpl2
# by crutchy
# 21-aug-2014

require_once("lib.php");
$host=trim($argv[1]);

/*
$host="";
$uri="";
$port=80;
if (get_host_and_uri($url,$host,$uri,$port)==False)
{
  return;
}
*/

$port=80;
$delim443="https://";
$delim80="http://";
if (substr($host,0,strlen($delim443))==$delim443)
{
  $host=substr($host,strlen($delim443));
  $port=443;
}
elseif (substr($host,0,strlen($delim80))==$delim80)
{
  $host=substr($host,strlen($delim80));
  $port=80;
}
else
{
  $parts=explode(":",$host);
  if (count($parts)==2)
  {
    $host=trim($parts[0]);
    $port=trim($parts[1]);
  }
}
$response=wtouch($host,"/",$port);
if ($response===False)
{
  privmsg($argv[1].": error connecting");
  return;
}
if (($port==80) or ($port==443))
{
  privmsg($argv[1].": $response");
}
else
{
  privmsg($argv[1].": connected");
}

?>
