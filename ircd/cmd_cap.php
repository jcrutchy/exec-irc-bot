<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_cap(&$connections,&$client,$items)
{
  $key=connection_key($connections,$client);
  if ($key===False)
  {
    return;
  }
  $connection=$connections[$key];
  $addr=$connection["addr"];
  echo "*** CAP MESSAGE RECEIVED FROM $addr\n";
}

#####################################################################################################

?>
