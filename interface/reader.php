<?php

#####################################################################################################

/*
exec:~reader|0|0|0|1|@||||php interface/reader.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%%
#startup:~reader start
*/

#####################################################################################################

# ip -f inet addr

#require_once(__DIR__."/../scripts/lib.php");

/*$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];*/

define("LISTEN_ADDRESS","192.168.1.58");
define("BUFFER_FILE",__DIR__."/../../data/exec_iface");
define("LISTEN_PORT",50000);
define("CLIENT_TIMEOUT",60); # seconds
define("MAX_DATA_LEN",10000);

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set("UTC");

if (file_exists(BUFFER_FILE)==False)
{
  echo "*** named pipe file not found\n";
  return;
}
$buffer=fopen(BUFFER_FILE,"r");

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
echo "exec output server\n";
echo "listening on ".LISTEN_ADDRESS."...\n";
while (True)
{
  # do extra stuff here as required
  $read=array($buffer);
  $write=NULL;
  $except=NULL;
  if (stream_select($read,$write,$except,0)>=1)
  {
    $data=fgets($buffer);
    #$array=json_decode($data,True);
    #echo $array["buf"];
    foreach ($clients as $send_client)
    {
      if ($send_client<>$server)
      {
        $written=@socket_write($send_client,$data.chr(0));
        if ($written===False)
        {
          $client_index=array_search($send_client,$clients);
          on_disconnect($client_index);
          socket_close($send_client);
          unset($clients[$client_index]);
          echo "client disconnected\n";
        }
      }
    }
  }
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
    on_msg($client_index,$data);
  }
}
socket_shutdown($server,2);
socket_close($server);

#####################################################################################################

function on_connect($client_index)
{
  global $clients;
}

#####################################################################################################

function on_disconnect($client_index)
{

}

#####################################################################################################

function on_msg($client_index,$data)
{
  global $clients;
  # "/READER_EXEC"
  # "/READER_BUCKETS"
  # "/READER_HANDLES"
  echo $data;
}

#####################################################################################################

?>
