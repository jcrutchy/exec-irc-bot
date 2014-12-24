<?php

# gpl2
# by crutchy

#####################################################################################################

#exec:~pong|10|0|0|0||INTERNAL|||php scripts/ping.php %%trailing%% %%alias%%
#exec:~ping|10|300|0|0||INTERNAL|||php scripts/ping.php %%trailing%% %%alias%%

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$alias=trim($argv[2]);

switch ($alias)
{
  case "~ping": # called every 5 mins (trailing is empty)
    $t=microtime(True);
    if (get_bucket(BUCKET_CONNECTION_ESTABLISHED)<>"1")
    {
      rawmsg("PING $t");
      return;
    }
    $ping=get_bucket("<<PING>>");
    $pong=get_bucket("<<PONG>>");
    if (($ping<>"") and ($ping<>$pong))
    {
      term_echo("==================== PING TIMEOUT DETECTED ====================");
      echo "/INTERNAL ~restart-internal\n";
      return;
    }
    set_bucket("<<PING>>",$t);
    rawmsg("PING $t");
    return;
  case "~pong": # called in response to PONG received (trailing contains timestamp)
    set_bucket("<<PONG>>",$trailing);
    return;
}

#####################################################################################################

?>
