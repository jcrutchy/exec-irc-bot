<?php

#####################################################################################################

function cmd_quit($client_index,$items)
{
  global $connections;
  $connection_index=connection_index($client_index);
  if ($connection_index===False)
  {
    return;
  }
  $addr=$connections[$connection_index]["addr"];
  echo "*** QUIT MESSAGE RECEIVED FROM $addr\n";
}

#####################################################################################################

?>
