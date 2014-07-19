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
pm("#","ping test");
$t=time();
if ($trailing<>"")
{
  $ping_lag=get_bucket(BUCKET_PING_LAG);
  $d=date_parse_from_format("YMdHis",$ping_lag);
  $last=mktime($d["hour"],$d["minute"],$d["second"],$d["month"],$d["day"],$d["year"]);
  $delta=$t-$last;
  if ($delta>20)
  {
    term_echo("==================== PING TIMEOUT DETECTED ====================");
    echo "/INTERNAL ~restart\n";
  }
}
else
{
  $ping_lag=gmdate("YMdHis",$t);
  set_bucket(BUCKET_PING_LAG,$ping_lag);
  rawmsg("PING $ping_lag");
}

#####################################################################################################

?>
