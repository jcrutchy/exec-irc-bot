<?php

# gpl2
# by crutchy

#####################################################################################################

function connection_key(&$connections,&$client,$suppress_error=False)
{
  foreach ($connections as $key => $data)
  {
    if ($connections[$key]["client"]===$client)
    {
      return $key;
    }
  }
  if ($suppress_error==False)
  {
    $err="ERROR: CONNECTION KEY NOT FOUND";
    do_reply($client,$err);
    echo "*** $addr: $err\n";
  }
  return False;
}

#####################################################################################################

function connection_nick(&$nicks,&$connection,$suppress_error=False)
{
  foreach ($nicks as $nick => $data)
  {
    if ($nicks[$nick]["connection"]===$connection)
    {
      return $nick;
    }
  }
  if ($suppress_error==False)
  {
    $err="ERROR: NICK DATA NOT FOUND";
    do_reply($client,$err);
    echo "*** $addr: $err\n";
  }
  return False;
}

#####################################################################################################

function client_nick(&$connections,&$nicks,&$client,$suppress_error=False)
{
  $key=connection_key($connections,$client,$suppress_error);
  if ($key===False)
  {
    return False;
  }
  $nick=connection_nick($nicks,$connections[$key],$suppress_error);
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

function send_to_all(&$server,&$client_list,$data)
{
  foreach ($client_list as $send_client)
  {
    if ($send_client<>$server)
    {
      socket_write($send_client,"$data\n");
    }
  }
}

#####################################################################################################

function do_reply(&$client,$data)
{
  socket_write($client,"$data\n");
}

#####################################################################################################

function on_connect(&$connections,&$client,$addr)
{
  echo "*** CLIENT CONNECTED: $addr\n";
  $key=connection_key($connections,$client,True);
  if ($key===False)
  {
    $connection=array();
    $connection["client"]=$client;
    $connection["addr"]=$addr;
    $connection["connect_timestamp"]=microtime(True);
    $connections[]=&$connection;
  }
  else
  {
    echo "*** CLIENT CONNECT ERROR: CONNECTION EXISTS ALREADY\n";
  }
}

#####################################################################################################

function on_disconnect(&$nicks,&$connections,&$client)
{
  $key=connection_key($connections,$client);
  if ($key===False)
  {
    echo "*** CLIENT DISCONNECT ERROR: CONNECTION NOT FOUND\n";
  }
  else
  {
    $addr=$connections[$key]["addr"];
    $nick=connection_nick($nicks,$connections[$key]);
    if ($nick!==False)
    {
      unset($nicks[$nick]);
    }
    unset($connections[$key]);
    echo "*** CLIENT DISCONNECTED: $addr\n";
  }
}

#####################################################################################################

function on_msg(&$connections,&$nicks,&$channels,&$client,$addr,$data)
{
  $items=parse_data_basic($data);
  if ($items===False)
  {
    $err="ERROR: UNABLE TO PARSE DATA (NUMERIC 421)";
    do_reply($client,$err);
    echo "*** $addr: $err\n";
    return;
  }
  switch ($items["cmd"])
  {
    case "CAP":
      cmd_cap($connections,$client,$items);
      break;
    case "NICK":
      cmd_nick($connections,$nicks,$channels,$client,$items);
      break;
    case "USER":
      cmd_user($connections,$nicks,$channels,$client,$items);
      break;
    case "JOIN":
      cmd_join($connections,$nicks,$channels,$client,$items);
      break;
    case "QUIT":
      cmd_quit($connections,$nicks,$channels,$client,$items);
      break;
    default:
      $err="UNKNOWN COMMAND";
      do_reply($client,$err);
      echo "*** $addr: $err\n";
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
