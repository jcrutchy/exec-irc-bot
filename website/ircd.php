<?php

# gpl2
# by crutchy

/*
message received: CAP LS
message received: NICK crutchy
message received: USER crutchy crutchy 192.168.0.21 :crutchy
*/

#####################################################################################################

define("LISTEN_ADDRESS","192.168.0.21");
define("LISTEN_PORT",6667);
define("CLIENT_TIMEOUT",60); # seconds

define("SERVER_HOSTNAME","sylnt.us.to");
define("MAX_DATA_LEN",1024);

$connections=array();
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
$client_list=array($server);
echo "listening...\n";
while (True)
{
  loop_process();
  $read=$client_list;
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
    $client_list[]=$client;
    $addr="";
    if (socket_getpeername($client,$addr)==True)
    {
      echo "connected to remote address $addr\n";
      on_connect($connections,$client,$addr);
    }
    $n=count($client_list)-1;
    socket_write($client,"successfully connected to server\nthere are $n clients connected\n");
    $key=array_search($server,$read);
    unset($read[$key]);
  }
  foreach ($read as $read_client)
  {
    usleep(10000);
    $data=@socket_read($read_client,MAX_DATA_LEN,PHP_NORMAL_READ);
    if ($data===False)
    {
      $addr="";
      if (@socket_getpeername($read_client,$addr)==True)
      {
        echo "disconnecting from remote address $addr\n";
      }
      on_disconnect($nicks,$connections,$read_client);
      socket_close($read_client);
      $key=array_search($read_client,$client_list);
      unset($client_list[$key]);
      echo "client disconnected\n";
      var_dump($nicks);
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
      $key=array_search($read_client,$client_list);
      unset($client_list[$key]);
      if ($data=="quit")
      {
        break;
      }
      break 2;
    }
    $addr="";
    socket_getpeername($read_client,$addr);
    send_to_all($server,$client_list,"$addr: $data");
    on_msg($connections,$nicks,$channels,$read_client,$addr,$data);
  }
}
socket_close($server);

#####################################################################################################

function connection_key(&$connections,&$client)
{
  foreach ($connections as $key => $data)
  {
    if ($connections[$key]["client"]===$client)
    {
      return $key;
    }
  }
  return False;
}

#####################################################################################################

function connection_nick(&$nicks,&$connection)
{
  foreach ($nicks as $nick => $data)
  {
    if ($nicks[$nick]["connection"]===$connection)
    {
      return $nick;
    }
  }
  return False;
}

#####################################################################################################

function client_nick(&$connections,&$nicks,&$client)
{
  $key=connection_key($connections,$client);
  if ($key===False)
  {
    return False;
  }
  $nick=connection_nick($nicks,$connections[$key]);
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
  $key=connection_key($connections,$client);
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
    case "CAP": # CAP LS
      echo "*** CAP MESSAGE RECEIVED FROM $addr\n";
      break;
    case "NICK":
      # NICK crutchy
      # ERR_NONICKNAMEGIVEN,ERR_ERRONEUSNICKNAME,ERR_NICKNAMEINUSE,ERR_NICKCOLLISION
      $nick=$items["params"];
      echo "*** NICK MESSAGE RECEIVED FROM $addr: $nick\n";
      if (isset($nicks[$nick])==False)
      {
        # TODO: CHANGE NICK
        $nicks[$nick]=array();
        $key=connection_key($connections,$client);
        if ($key===False)
        {
          $err="ERROR: CONNECTION NOT FOUND (INTERNAL)";
          do_reply($client,$err);
          echo "*** $addr: $err\n";
          break;
        }
        $connection=$connections[$key];
        $nicks[$nick]["connection"]=$connection;
      }
      else
      {
        $err="ERROR: NICK ALREADY EXISTS (NUMERIC 433)";
        do_reply($client,$msg);
        echo "*** $addr: $err\n";
      }
      break;
    case "USER":
      # USER crutchy crutchy 192.168.0.21 :crutchy
      # USER <username> <hostname> <servername> :<realname>
      $nick=client_nick($connections,$nicks,$client);
      if ($nick===False)
      {
        $err="ERROR: NICK DATA NOT FOUND";
        do_reply($client,$err);
        echo "*** $addr: $err\n";
        break;
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
      break;
    case "JOIN":
      echo "*** JOIN MESSAGE RECEIVED FROM $addr\n";
      $nick=client_nick($connections,$nicks,$client);
      if ($nick===False)
      {
        $err="ERROR: NICK DATA NOT FOUND";
        do_reply($client,$err);
        echo "*** $addr: $err\n";
        break;
      }
      $chan=$items["params"];
      if (isset($channels[$chan])==False)
      {
        $channels[$chan]=array();
        $channels[$chan]["nicks"]=array();
      }
      $channels[$chan]["nicks"][]=$nick;

      break;
    case "QUIT":
      echo "*** QUIT MESSAGE RECEIVED FROM $addr\n";
      break;
    default:
      echo "*** UNKNOWN MESSAGE RECEIVED FROM $addr\n";
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
