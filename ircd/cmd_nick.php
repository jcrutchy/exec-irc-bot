<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_nick(&$connections,&$nicks,&$channels,&$client,$items)
{
  # NICK crutchy
  # ERR_NONICKNAMEGIVEN,ERR_ERRONEUSNICKNAME,ERR_NICKNAMEINUSE,ERR_NICKCOLLISION
  $nick=$items["params"];
  echo "*** NICK MESSAGE RECEIVED FROM $addr: $nick\n";
  if (isset($nicks[$nick])==False)
  {
    # TODO: CHANGE NICK
    $nicks[$nick]=array();
    $key=connection_key($connections,$client);
    if ($key===False)
    {
      return;
    }
    $connection=$connections[$key];
    $nicks[$nick]["connection"]=$connection;
  }
  else
  {
    $err="ERROR: NICK ALREADY EXISTS (NUMERIC 433)";
    do_reply($client,$msg);
    echo "*** $addr: $err\n";
  }
}

#####################################################################################################

?>
