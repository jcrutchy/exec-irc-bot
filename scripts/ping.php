<?php

# gpl2
# by crutchy
# 19-july-2014

#####################################################################################################

ini_set("display_errors","on");
date_default_timezone_set("UTC");
require_once("lib.php");
$trailing=trim($argv[1]);
define("BUCKET_PING_LAG","<<PING_LAG>>");
$ping_lag=get_bucket(BUCKET_PING_LAG);
$t=microtime(True);
if ($trailing<>"")
{
  if ($ping_lag=="")
  {
    return;
  }
  $delta=$t-$ping_lag;
  pm("#","lag = $delta sec");
  if ($delta>10)
  {
    term_echo("==================== PING TIMEOUT DETECTED ====================");
    echo "/INTERNAL ~restart\n";
  }
}
else
{
  if ($ping_lag<>"")
  {
    if ($ping_lag==$t)
    {
      term_echo("==================== PING TIMEOUT DETECTED ====================");
      echo "/INTERNAL ~restart\n";
    }
  }
  set_bucket(BUCKET_PING_LAG,$t);
  $msg="PING $t";
  pm("#",$msg);
  rawmsg($msg);
}

#####################################################################################################

?>
