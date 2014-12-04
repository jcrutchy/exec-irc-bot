<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_quit(&$connections,&$nicks,&$channels,&$client,$items)
{
  $key=connection_key($connections,$client);
  if ($key===False)
  {
    return;
  }
  $connection=$connections[$key];
  $addr=$connection["addr"];
  echo "*** QUIT MESSAGE RECEIVED FROM $addr\n";
}

#####################################################################################################

?>
