<?php

# gpl2
# by crutchy

# http://php.net/manual/en/sockets.examples.php

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
$server=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if ($server===False)
{
  echo "socket_create() failed: reason: ".socket_strerror(socket_last_error())."\n";
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
  $read=$clients;
  $requests=get_requests();
  if ($requests=="")
  {
    continue;
  }
  foreach ($clients as $send_client)
  {
    if ($send_client==$server)
    {
      continue;
    }
    socket_write($send_client,$requests."\n");
  }
  $write=NULL;
  $except=NULL;
  if (socket_select($read,$write,$except,0)<1)
  {
    usleep(100);
    continue;
  }
  if (in_array($server,$read)==False)
  {
    usleep(100);
    continue;
  }
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
  foreach ($read as $read_client)
  {
    usleep(100);
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
  }
}
socket_close($server);

#####################################################################################################

function get_requests()
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
    $request_data=file_get_contents($filename);
    if ($request_data===False)
    {
      unlink($filename);
      continue;
    }
    $requests[]=$request_data;
  }
  if (count($requests)==0)
  {
    return "";
  }
  return implode("\n",$requests);
}

#####################################################################################################

?>
