<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_join(&$connections,&$nicks,&$channels,&$client,$items)
{
  $nick=client_nick($connections,$nicks,$client);
  if ($nick===False)
  {
    return;
  }
  $addr=$connection["addr"];
  echo "*** JOIN MESSAGE RECEIVED FROM $addr\n";
  $chan=$items["params"];
  if (isset($channels[$chan])==False)
  {
    $channels[$chan]=array();
    $channels[$chan]["nicks"]=array();
  }
  $channels[$chan]["nicks"][]=$nick;
}

#####################################################################################################

?>
