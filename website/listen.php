<?php

# gpl2
# by crutchy

#####################################################################################################

define("LISTEN_ADDRESS","192.168.0.21");
define("LISTEN_PORT",50000);
define("FILENAME_PREFIX_REQUEST","request__");
define("FILENAME_PREFIX_RESPONSE","response__");
define("FILE_PATH_REQUESTS","/var/include/vhosts/irciv.us.to/relay/requests/");
define("FILE_PATH_RESPONSES","/var/include/vhosts/irciv.us.to/relay/responses/");
define("TOKENS_FILE","/var/include/vhosts/irciv.us.to/relay/tokens");
define("CLIENT_TIMEOUT",60); # seconds

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
get_requests(True);
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
echo "waiting for client...\n";
while (True)
{
  $requests=get_requests();
  if ($requests<>"")
  {
    echo "request(s) received: $requests\n";
    foreach ($clients as $send_client)
    {
      if ($send_client==$server)
      {
        continue;
      }
      socket_write($send_client,$requests."\n");
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
    $addr="";
    if (socket_getpeername($client,$addr)==True)
    {
      echo "connected to remote address $addr\n";
    }
    $n=count($clients)-1;
    socket_write($client,"successfully connected to notification server\nthere are $n clients connected\n");
    $key=array_search($server,$read);
    unset($read[$key]);
  }
  foreach ($read as $read_client)
  {
    usleep(10000);
    $data=@socket_read($read_client,1024,PHP_NORMAL_READ);
    if ($data===False)
    {
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
  }
}
socket_close($server);

#####################################################################################################

function get_requests($purge=False)
{
  $files=scandir(FILE_PATH_REQUESTS);
  $requests=array();
  for ($i=0;$i<count($files);$i++)
  {
    if (substr($files[$i],0,strlen(FILENAME_PREFIX_REQUEST))<>FILENAME_PREFIX_REQUEST)
    {
      continue;
    }
    $filename=FILE_PATH_REQUESTS."/".$files[$i];
    if (file_exists($filename)==False)
    {
      continue;
    }
    if ($purge==False)
    {
      $request_data=file_get_contents($filename);
      if ($request_data!==False)
      {
        $requests[]=$request_data;
      }
    }
    unlink($filename);
  }
  if (count($requests)==0)
  {
    return "";
  }
  return implode("\n",$requests);
}

#####################################################################################################

?>