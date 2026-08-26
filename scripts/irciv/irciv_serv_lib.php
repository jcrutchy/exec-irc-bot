<?php

#####################################################################################################

function connection_index($client_index,$suppress_error=False)
{
  global $connections;
  foreach ($connections as $index => $data)
  {
    if ($connections[$index]["client_index"]==$client_index)
    {
      return $index;
    }
  }
  if ($suppress_error==False)
  {
    do_reply($client_index,"ERROR: CONNECTION KEY NOT FOUND");
  }
  return False;
}

#####################################################################################################

function connection_nick($client_index,&$connection,$suppress_error=False)
{
  global $connections;
  global $nicks;
  foreach ($nicks as $nick => $data)
  {
    if (in_array($connection,$nicks[$nick]["connection"])==True)
    {
      return $nick;
    }
  }
  if ($suppress_error==False)
  {
    do_reply($client_index,"ERROR: NICK DATA NOT FOUND");
  }
  return False;
}

#####################################################################################################

function client_nick($client_index,$suppress_error=False,$authenticate=True)
{
  global $connections;
  global $nicks;
  $connection_index=connection_index($client_index,$suppress_error);
  if ($connection_index===False)
  {
    return False;
  }
  if (($connections[$connection_index]["authenticated"]==False) and ($authenticate==True))
  {
    if ($suppress_error==False)
    {
      do_reply($client_index,"*** MESSAGE REFUSED: CONNECTION NOT AUTHENTICATED");
    }
    return False;
  }
  $nick=connection_nick($client_index,$connections[$connection_index],$suppress_error);
  if ($nick===False)
  {
    return False;
  }
  else
  {
    return $nick;
  }
}

#####################################################################################################

function nick_connection($client_index,$nick)
{
  global $nicks;
  for ($i=0;$i<count($nicks[$nick]["connection"]);$i++)
  {
    if (($nicks[$nick]["connection"][$i]["client_index"]==$client_index) and ($nicks[$nick]["connection"][$i]["authenticated"]==True))
    {
      return $nicks[$nick]["connection"][$i];
    }
  }
  return False;
}

#####################################################################################################

function loop_process()
{
  # do other stuff here if need be
}

#####################################################################################################

function broadcast($msg)
{
  global $server;
  global $clients;
  echo "BROADCAST: $msg\n";
  foreach ($clients as $send_client)
  {
    if ($send_client<>$server)
    {
      socket_write($send_client,"$msg\n");
    }
  }
}

#####################################################################################################

function do_reply($client_index,$msg)
{
  global $clients;
  $addr="";
  socket_getpeername($clients[$client_index],$addr);
  echo "REPLY TO $addr: $msg\n";
  socket_write($clients[$client_index],"$msg\n");
}

#####################################################################################################

function do_reply_nick($nick,$msg,$client_index=False)
{
  global $nicks;
  $c=count($nicks[$nick]["connection"]);
  for ($i=0;$i<$c;$i++)
  {
    $conn=$nicks[$nick]["connection"][$i];
    if ($client_index!==False)
    {
      if ($conn["client_index"]==$client_index)
      {
        continue;
      }
    }
    do_reply($conn["client_index"],$msg);
  }
}

#####################################################################################################

function construct_message($nick,$cmd,$params,$trailing)
{
  global $nicks;
  if (isset($nicks[$nick])==False)
  {
    return False;
  }
  return ":".$nicks[$nick]["prefix"]." ".strtoupper($cmd)." $params :$trailing";
}

#####################################################################################################

function on_connect($client_index)
{
  global $clients;
  global $connections;
  $connection_index=connection_index($client_index,True);
  if ($connection_index===False)
  {
    $addr="";
    socket_getpeername($clients[$client_index],$addr);
    $connection=array();
    $connection["client_index"]=$client_index;
    $connection["addr"]=$addr;
    $connection["connect_timestamp"]=microtime(True);
    $connection["ident_prefix"]="";
    $connection["authenticated"]=False;
    $connections[]=$connection;
    broadcast("*** CLIENT CONNECTED: $addr");
  }
  else
  {
    do_reply($client_index,"*** CLIENT CONNECT ERROR: CONNECTION EXISTS ALREADY");
  }
}

#####################################################################################################

function on_disconnect($client_index)
{
  global $nicks;
  global $connections;
  $connection_index=connection_index($client_index);
  if ($connection_index===False)
  {
    echo "*** CLIENT DISCONNECT ERROR: CONNECTION NOT FOUND\n";
  }
  else
  {
    $addr=$connections[$connection_index]["addr"];
    $nick=connection_nick($client_index,$connections[$connection_index]);
    if ($nick!==False)
    {
      if (count($nicks[$nick]["connection"])==1)
      {
        unset($nicks[$nick]);
      }
      else
      {
        foreach ($nicks[$nick]["connection"] as $index => $conn)
        {
          if ($conn["client_index"]==$client_index)
          {
            unset($nicks[$nick]["connection"][$index]);
            $nicks[$nick]["connection"]=array_values($nicks[$nick]["connection"]);
            break;
          }
        }
      }
      echo "*** CLIENT DISCONNECTED: $addr <$nick>\n";
    }
    else
    {
      echo "*** CLIENT DISCONNECTED: $addr\n";
    }
    unset($connections[$connection_index]);
  }
}

#####################################################################################################

function on_msg($client_index,$data)
{
  global $clients;
  global $connections;
  global $nicks;
  global $channels;
  $items=parse_data_basic($data);
  if ($items===False)
  {
    do_reply($client_index,"ERROR: UNABLE TO PARSE DATA (NUMERIC 421)");
    return;
  }
  switch ($items["cmd"])
  {
    case "CAP":
      cmd_cap($client_index,$items);
      break;
    case "NICK":
      cmd_nick($client_index,$items);
      break;
    case "USER":
      cmd_user($client_index,$items);
      break;
    case "JOIN":
      cmd_join($client_index,$items);
      break;
    case "QUIT":
      cmd_quit($client_index,$items);
      break;
    case "MODE":
      cmd_mode($client_index,$items);
      break;
    case "WHO":
      cmd_who($client_index,$items);
      break;
    case "PRIVMSG":
      cmd_privmsg($client_index,$items);
      break;
    default:
      do_reply($client_index,"UNKNOWN COMMAND");
  }
}

#####################################################################################################

function log_msg($addr,$client_index,$data)
{
  # TODO
}

#####################################################################################################

?>
