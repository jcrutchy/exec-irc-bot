<?php

# gpl2
# by crutchy
# 31-may-2014

ini_set("display_errors","on");

require_once("lib.php");

$host=trim($argv[1]);
$port=80;
$parts=explode(":",$host);
if (count($parts)==2)
{
  $host=trim($parts[0]);
  $port=trim($parts[1]);
}
$errno=0;
$errstr="";
if ($port==80)
{
  $fp=fsockopen($host,80,$errno,$errstr,5);
}
elseif ($port==443)
{
  $fp=fsockopen("ssl://$host",443,$errno,$errstr,5);
}
else
{
  $fp=fsockopen($host,$port,$errno,$errstr,5);
}
if ($fp===False)
{
  privmsg($argv[1].": error connecting");
  return;
}
if (($port==80) or ($port==443))
{
  fwrite($fp,"GET / HTTP/1.0\r\nHost: $host\r\nConnection: Close\r\n\r\n");
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  $lines=explode("\n",$response);
  $msg=trim($lines[0]);
  privmsg($argv[1].": $msg");
}
else
{
  privmsg($argv[1].": connected");
  fclose($fp);
}

?>
