<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_cap(&$connections,&$nicks,&$client,$items)
{
  $key=connection_key($connec,tions,$client);
  if ($key===False)
  {
    return;
  }
  $connection=&$connections[$key];
  $addr=$connection["addr"];
  echo "*** CAP MESSAGE RECEIVED FROM $addr\n";
  $cap=$items["params"];
  $connection["cap"]=$cap;
}

#####################################################################################################

?>
