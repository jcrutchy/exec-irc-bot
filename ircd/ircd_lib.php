<?php

#####################################################################################################

function connection_index($client_index,$suppress_error=False)
{
  global $connections;
  foreach ($connections as $index => $data)
  {
    if (in_array($client_index,$connections[$index]["client_index"])==True)
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
    if ($nicks[$nick]["connection"]===$connection)
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

function client_nick($client_index,$suppress_error=False)
{
  global $connections;
  global $nicks;
  $connection_index=connection_index($client_index,$suppress_error);
  if ($connection_index===False)
  {
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
    unset($connections[$connection_index]);
    if ($nick!==False)
    {
      unset($nicks[$nick]);
      echo "*** CLIENT DISCONNECTED: $addr <$nick>\n";
    }
    else
    {
      echo "*** CLIENT DISCONNECTED: $addr\n";
    }
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

function parse_data_basic($data)
{
  # :<prefix> <command> <params> :<trailing>
  # the only required part of the message is command
  if ($data=="")
  {
    return False;
  }
  $sub=trim($data,"\n\r\0\x0B");
  $result["microtime"]=microtime(True);
  $result["time"]=date("Y-m-d H:i:s",$result["microtime"]);
  $result["data"]=$sub;
  $result["prefix"]=""; # prefix is optional
  $result["params"]="";
  $result["trailing"]="";
  $result["nick"]="";
  $result["user"]="";
  $result["hostname"]="";
  if (substr($sub,0,1)==":") # prefix found
  {
    $i=strpos($sub," ");
    $result["prefix"]=substr($sub,1,$i-1);
    $sub=substr($sub,$i+1);
  }
  $i=strpos($sub," :");
  if ($i!==False) # trailing found
  {
    $result["trailing"]=substr($sub,$i+2);
    $sub=substr($sub,0,$i);
  }
  $i=strpos($sub," ");
  if ($i!==False) # params found
  {
    $result["params"]=substr($sub,$i+1);
    $sub=substr($sub,0,$i);
  }
  $result["cmd"]=strtoupper($sub);
  if ($result["cmd"]=="")
  {
    return False;
  }
  if ($result["prefix"]<>"")
  {
    # prefix format: nick!user@hostname
    $prefix=$result["prefix"];
    $i=strpos($prefix,"!");
    if ($i===False)
    {
      $result["nick"]=$prefix;
    }
    else
    {
      $result["nick"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $i=strpos($prefix,"@");
      $result["user"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $result["hostname"]=$prefix;
    }
  }
  return $result;
}

#####################################################################################################

?>
