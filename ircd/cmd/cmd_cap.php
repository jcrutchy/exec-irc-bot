<?php

#####################################################################################################

function cmd_cap($client_index,$items)
{
  global $connections;
  $connection_index=connection_index($client_index);
  if ($connection_index===False)
  {
    return;
  }
  $addr=$connections[$connection_index]["addr"];
  echo "*** CAP MESSAGE RECEIVED FROM $addr\n";
  $connections[$connection_index]["cap"]=$items["params"];
  $connections[$connection_index]["ident_prefix"]="~";
}

#####################################################################################################

?>
