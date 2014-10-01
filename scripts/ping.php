<?php

# gpl2
# by crutchy

#####################################################################################################

require_once("lib.php");

if (get_bucket(BUCKET_CONNECTION_ESTABLISHED)<>"1")
{
  return;
}

$trailing=trim($argv[1]);
define("BUCKET_PING","<<PING>>");
define("BUCKET_PONG","<<PONG>>");
$ping=get_bucket(BUCKET_PING);
$pong=get_bucket(BUCKET_PONG);
if ($trailing<>"")
{
  set_bucket(BUCKET_PONG,$trailing);
}
else
{
  if (($ping<>"") and ($ping<>$pong))
  {
    term_echo("==================== PING TIMEOUT DETECTED ====================");
    echo "/INTERNAL ~restart-internal\n";
    return;
  }
  $t=microtime(True);
  set_bucket(BUCKET_PING,$t);
  $msg="PING $t";
  rawmsg($msg);
}

#####################################################################################################

?>
