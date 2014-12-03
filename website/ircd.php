<?php

# gpl2
# by crutchy

# halfassircd / kissircd

/*
message received: CAP LS
message received: NICK crutchy
message received: USER crutchy crutchy 192.168.0.21 :crutchy
*/

#####################################################################################################

define("LISTEN_ADDRESS","192.168.0.21");
define("LISTEN_PORT",6667);
define("CLIENT_TIMEOUT",60); # seconds

$nicks=array();
$channels=array();

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set("UTC");

$server=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if ($server===False)
{
  echo "socket_create() failed: reason: ".socket_strerror(socket_last_error())."\n";
}
if (socket_get_option($server,SOL_SOCKET,SO_REUSEADDR)===False)
{
  echo "socket_get_option() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
}
if (socket_bind($server,LISTEN_ADDRESS,LISTEN_PORT)===False)
{
  echo "socket_bind() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
}
if (socket_listen($server,5)===False)
{
  echo "socket_listen() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
}
$clients=array($server);
echo "listening...\n";
while (True)
{
  # do other stuff here if need be
  $read=$clients;
  $write=NULL;
  $except=NULL;
  if (socket_select($read,$write,$except,0)<1)
  {
    usleep(10000);
    continue;
  }
  if (in_array($server,$read)==True)
  {
    $client=socket_accept($server);
    $clients[]=$client;
    $addr="";
    if (socket_getpeername($client,$addr)==True)
    {
      echo "connected to remote address $addr\n";
      on_connect($client,$addr);
    }
    $n=count($clients)-1;
    socket_write($client,"successfully connected to server\nthere are $n clients connected\n");
    $key=array_search($server,$read);
    unset($read[$key]);
  }
  foreach ($read as $read_client)
  {
    usleep(10000);
    $data=@socket_read($read_client,1024,PHP_NORMAL_READ);
    if ($data===False)
    {
      $addr="";
      if (socket_getpeername($read_client,$addr)==True)
      {
        echo "disconnecting from remote address $addr\n";
        on_disconnect($read_client,$addr);
      }
      socket_close($read_client);
      $key=array_search($read_client,$clients);
      unset($clients[$key]);
      echo "client disconnected\n";
      continue;
    }
    $data=trim($data);
    if ($data=="")
    {
      continue;
    }
    echo "message received: $data\n";
    if (($data=="quit") or ($data=="shutdown"))
    {
      echo "$data received\n";
      socket_close($read_client);
      $key=array_search($read_client,$clients);
      unset($clients[$key]);
      if ($data=="quit")
      {
        break;
      }
      break 2;
    }
    $addr="";
    socket_getpeername($read_client,$addr);
    foreach ($clients as $send_client)
    {
      if (($send_client<>$read_client) and ($send_client<>$server))
      {
        socket_write($send_client,"broadcast from $addr: $data"."\n");
      }
    }
    on_msg($read_client,$addr,$data);
  }
}
socket_close($server);

#####################################################################################################

function on_connect($client,$addr)
{
  echo "*** CLIENT CONNECTED: $addr\n";
}

#####################################################################################################

function on_disconnect($client,$addr)
{
  echo "*** CLIENT DISCONNECTED: $addr\n";
}

#####################################################################################################

function on_msg($client,$addr,$data)
{
  echo "*** MESSAGE RECEIVED FROM CLIENT $addr: $data\n";
  $items=parse_data_basic($data);
  var_dump($items);
  if ($items===False)
  {
    return;
  }
  switch ($items["cmd"])
  {
    case "CAP":
      echo "*** CAP MESSAGE RECEIVED FROM $addr\n";
      break;
    case "NICK":
      echo "*** NICK MESSAGE RECEIVED FROM $addr\n";
      break;
    case "USER":
      echo "*** USER MESSAGE RECEIVED FROM $addr\n";
      break;
    case "JOIN":
      echo "*** JOIN MESSAGE RECEIVED FROM $addr\n";
      break;
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
