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
