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
socket_set_nonblock($server);
echo "waiting for client...\n";
do
{
  $client=socket_accept($server);
  if ($client===False)
  {
    usleep(100);
  }
  elseif ($client>0)
  {
    $addr="";
    $port=0;
    if (socket_getpeername($client,$addr,$port)==True)
    {
      echo "connected to remote address $addr on port $port\n";
    }
    socket_set_nonblock($client);
    do
    {
      $buf="";
      do
      {
        $chunk=socket_read($client,2048,PHP_BINARY_READ);
        if ($chunk===False)
        {
          echo "client disconnected\n";
          break 2;
        }
        $buf=$buf.$chunk;
        usleep(100);
      }
      while ($chunk<>"");
      $buf=trim($buf);
      if ($buf=="")
      {
        continue;
      }
      echo "message received: $buf\n";
      switch ($buf)
      {
        case "quit":
          echo "quit received\n";
          break 2;
        case "shutdown":
          echo "shutdown received\n";
          socket_close($client);
          break 3;
      }
      $response=get_requests();
      if ($response<>"")
      {
        socket_write($client,$response,strlen($response));
        echo "$response\n";
      }
      usleep(300);
    }
    while (True);
    socket_close($client);
  }
  else
  {
    echo "socket_accept() failed: reason: ".socket_strerror(socket_last_error($server))."\n";
    break;
  }
}
while (True);
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
