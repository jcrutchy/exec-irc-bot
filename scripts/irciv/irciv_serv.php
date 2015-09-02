<?php

#####################################################################################################

define("LISTEN_ADDRESS","127.0.0.1");
define("LISTEN_PORT",50005);
define("CLIENT_TIMEOUT",60); # seconds

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
  echo "*** socket_create() failed: reason: ".socket_strerror(socket_last_error())."\n";
  return;
}
if (socket_get_option($server,SOL_SOCKET,SO_REUSEADDR)===False)
{
  echo "*** socket_get_option() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
  return;
}
if (@socket_bind($server,LISTEN_ADDRESS,LISTEN_PORT)===False)
{
  echo "*** socket_bind() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
  return;
}
if (socket_listen($server,5)===False)
{
  echo "*** socket_listen() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
  return;
}
$clients=array($server);
echo "CRUTCHY IRCD\n";
echo "listening...\n";
while (True)
{
  loop_process();
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
    $client_index=array_search($client,$clients);
    $addr="";
    socket_getpeername($client,$addr);
    echo "connected to remote address $addr\n";
    on_connect($client_index);
    $n=count($clients)-1;
    socket_write($client,"successfully connected to server\nthere are $n clients connected\n");
    $key=array_search($server,$read);
    unset($read[$key]);
  }
  foreach ($read as $read_client)
  {
    usleep(10000);
    $client_index=array_search($read_client,$clients);
    $data=@socket_read($read_client,MAX_DATA_LEN,PHP_NORMAL_READ);
    if ($data===False)
    {
      $addr="";
      if (@socket_getpeername($read_client,$addr)==True)
      {
        echo "disconnecting from remote address $addr\n";
      }
      on_disconnect($client_index);
      socket_close($read_client);
      unset($clients[$client_index]);
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
      if ($data=="quit")
      {
        socket_shutdown($read_client,2);
        socket_close($read_client);
        unset($clients[$client_index]);
        break;
      }
      foreach ($clients as $client_index => $socket)
      {
        if ($clients[$client_index]<>$server)
        {
          socket_shutdown($clients[$client_index],2);
          socket_close($clients[$client_index]);
          unset($clients[$client_index]);
        }
      }
      break 2;
    }
    $addr="";
    socket_getpeername($read_client,$addr);
    log_msg($addr,$client_index,$data);
    on_msg($client_index,$data);
  }
}
socket_shutdown($server,2);
socket_close($server);

#####################################################################################################

?>
