<?php

# gpl2
# by crutchy

#####################################################################################################

define("LISTEN_ADDRESS","192.168.0.21");
define("LISTEN_PORT",6667);
define("CLIENT_TIMEOUT",60); # seconds

define("SERVER_HOSTNAME","sylnt.us.to");
define("MAX_DATA_LEN",1024);

require_once("include.php");
require_once("lib.php");

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

?>
