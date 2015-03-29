<?php

#####################################################################################################

function cmd_nick($client_index,$items)
{
  global $clients;
  global $connections;
  global $nicks;
  $nick=$items["params"];
  if (isset($nicks[strtolower($nick)])==False)
  {
    $connection_index=connection_index($client_index);
    if ($connection_index===False)
    {
      return;
    }
    # TODO: CHANGE NICK
    $nicks[strtolower($nick)]=array();
    $nicks[strtolower($nick)]["connection"][]=&$connections[$connection_index];
    $nicks[strtolower($nick)]["connection_index"][]=$connection_index;
    $addr=$connections[$connection_index]["addr"];
    do_reply($client_index,"*** NICK MESSAGE RECEIVED FROM $addr: $nick");
    # output connection_hash
  }
  else
  {
    # check for nick+connection_hash
    do_reply($client_index,"ERROR: NICK ALREADY EXISTS (NUMERIC 433)");
  }
}

#####################################################################################################

?>
