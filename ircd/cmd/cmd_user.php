<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_user($client_index,$items)
{
  global $nicks;
  # USER crutchy crutchy 192.168.0.21 :crutchy
  # USER <username> <hostname> <servername> :<realname>
  $nick=client_nick($client_index);
  if ($nick===False)
  {
    return;
  }
  if (isset($nicks[$nick]["username"])==True)
  {
    do_reply($client_index,"ERROR: USER ALREADY REGISTERED (NUMERIC 462)");
    return;
  }
  $param_parts=explode(" ",$items["params"]);
  if (count($param_parts)<>3)
  {
    do_reply($client,"ERROR: INCORRECT NUMBER OF PARAMS (NUMERIC 461)");
    return;
  }
  $nicks[$nick]["username"]=trim($param_parts[0]);
  $nicks[$nick]["hostname"]=trim($param_parts[1]);
  $nicks[$nick]["servername"]=trim($param_parts[2]);
  $nicks[$nick]["realname"]=trim($items["trailing"]);
  var_dump($nicks);
  $addr=$nicks[$nick]["connection"]["addr"];
  broadcast("*** USER MESSAGE RECEIVED FROM $addr");
}

#####################################################################################################

?>
