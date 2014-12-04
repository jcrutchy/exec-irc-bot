<?php

# gpl2
# by crutchy

#####################################################################################################

function cmd_user(&$connections,&$nicks,&$channels,&$client,$items)
{
  # USER crutchy crutchy 192.168.0.21 :crutchy
  # USER <username> <hostname> <servername> :<realname>
  $nick=client_nick($connections,$nicks,$client);
  if ($nick===False)
  {
    return;
  }
  if (isset($nicks[$nick]["username"])==True)
  {
    $err="ERROR: USER ALREADY REGISTERED (NUMERIC 462)";
    do_reply($client,$err);
    echo "*** $addr: $err\n";
    break;
  }
  $param_parts=explode(" ",$items["params"]);
  if (count($param_parts)<>3)
  {
    $err="ERROR: INCORRECT NUMBER OF PARAMS (NUMERIC 461)";
    do_reply($client,$err);
    echo "*** $addr: $err\n";
    break;
  }
  $nicks[$nick]["username"]=trim($param_parts[0]);
  $nicks[$nick]["hostname"]=trim($param_parts[1]);
  $nicks[$nick]["servername"]=trim($param_parts[2]);
  $nicks[$nick]["realname"]=trim($items["trailing"]);
  var_dump($nicks);
  echo "*** USER MESSAGE RECEIVED FROM $addr\n";
}

#####################################################################################################

?>
