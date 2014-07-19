<?php

# gpl2
# by crutchy
# 19-july-2014

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");
$trailing=trim($argv[1]);
define("BUCKET_PING","<<PING>>");
define("BUCKET_PONG","<<PONG>>");
$ping=get_bucket(BUCKET_PING);
$pong=get_bucket(BUCKET_PONG);
$t=microtime(True);
if ($trailing<>"")
{
  set_bucket(BUCKET_PONG,$trailing);
}
else
{
  if ($ping<>"")
  {
    if ($ping<>$pong)
    {
      term_echo("==================== PING TIMEOUT DETECTED ====================");
      echo "/INTERNAL ~restart-internal\n";
    }
  }
  set_bucket(BUCKET_PING,$t);
  $msg="PING $t";
  rawmsg($msg);
}

#####################################################################################################

?>
