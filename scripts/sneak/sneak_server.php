<?php

#####################################################################################################

/*
exec:add ~sneak-server
exec:edit ~sneak-server timeout 0
exec:edit ~sneak-server cmd php scripts/sneak/sneak_server.php %%trailing%% %%nick%% %%dest%% %%server%%
exec:enable ~sneak-server
startup:~join #sneak
*/

#####################################################################################################

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set("UTC");

require_once(__DIR__."/../lib.php");

$trailing=strtolower(trim($argv[1]));
$nick=$argv[2];
$dest=$argv[3];
$server=$argv[4];

$admin_accounts=array("crutchy");

if (is_admin($nick)==False)
{
  privmsg("not authorized");
  return;
}

$parts=explode(" ",$trailing);
$action=array_shift($parts);
switch ($action)
{
  case "start":
    if (count($parts)==2)
    {
      $channel=$parts[0];
      $port=$parts[1]; # >=50000
      start_server($server,$channel,$port);
    }
    else
    {
      privmsg("syntax: ~sneak-server start <channel> <tcp_port>");
      privmsg("syntax: ~sneak-server start #sneak 50000");
    }
    break;
  case "stop":
    if (count($parts)==2)
    {
      $channel=$parts[0];
      $port=$parts[1];
      $sneak_server_id=base64_encode($server." ".$channel." ".$port);
      set_bucket("sneak_server_command_$sneak_server_id","stop");
    }
    else
    {
      privmsg("syntax: ~sneak-server stop <channel> <tcp_port>");
      privmsg("syntax: ~sneak-server stop #sneak 50000");
    }
    break;
}

#####################################################################################################

function start_server($irc_server,$channel,$listen_port)
{
  $sneak_server_id=base64_encode($irc_server." ".$channel." ".$listen_port);
  $data_filename=DATA_PATH."sneak_data_".base64_encode($irc_server).".txt";
  $listen_address="127.0.0.1";
  $max_data_length=1024;
  $connections=array();
  privmsg("starting game server listening on $listen_address:$listen_port");
  $server=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
  if ($server===False)
  {
    privmsg("*** socket_create() failed: reason: ".socket_strerror(socket_last_error()));
    return;
  }
  if (socket_get_option($server,SOL_SOCKET,SO_REUSEADDR)===False)
  {
    privmsg("*** socket_get_option() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  if (@socket_bind($server,$listen_address,$listen_port)===False)
  {
    privmsg("*** socket_bind() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  if (socket_listen($server,5)===False)
  {
    privmsg("*** socket_listen() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  $clients=array($server);
  while (True)
  {
    $server_command=get_bucket("sneak_server_command_$sneak_server_id");
    switch ($server_command)
    {
      case "stop":
        broadcast($server,$clients,$connections,"*** SERVER SHUTTING DOWN NOW!");
        sleep(1);
        break 2;
    }
    loop_process($server,$clients,$connections);
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
      on_connect($server,$clients,$connections,$client_index);
      $n=count($clients)-1;
      socket_write($client,"successfully connected to server\nthere are $n clients connected\n");
      $key=array_search($server,$read);
      unset($read[$key]);
    }
    foreach ($read as $read_client)
    {
      usleep(10000);
      $client_index=array_search($read_client,$clients);
      $data=@socket_read($read_client,$max_data_length,PHP_NORMAL_READ);
      if ($data===False)
      {
        $addr="";
        if (@socket_getpeername($read_client,$addr)==True)
        {
          echo "disconnecting from remote address $addr\n";
        }
        on_disconnect($server,$clients,$connections,$client_index);
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
      log_msg($server,$clients,$connections,$addr,$client_index,$data);
      on_msg($server,$clients,$connections,$client_index,$data);
    }
  }
  socket_shutdown($server,2);
  socket_close($server);
  privmsg("stopping game server listening on $listen_address:$listen_port");
}

#####################################################################################################

function connection_index(&$connections,$client_index,$suppress_error=False)
{
  foreach ($connections as $index => $data)
  {
    if ($connections[$index]["client_index"]==$client_index)
    {
      return $index;
    }
  }
  if ($suppress_error==False)
  {
    do_reply($client_index,"ERROR: CONNECTION NOT FOUND");
  }
  return False;
}

#####################################################################################################

function loop_process(&$server,&$clients,&$connections)
{
  # do other stuff here if need be
}

#####################################################################################################

function broadcast(&$server,&$clients,&$connections,$msg)
{
  privmsg("BROADCAST: $msg");
  foreach ($clients as $send_client)
  {
    if ($send_client<>$server)
    {
      socket_write($send_client,"$msg\n");
    }
  }
}

#####################################################################################################

function do_reply(&$server,&$clients,&$connections,$client_index,$msg)
{
  $addr="";
  socket_getpeername($clients[$client_index],$addr);
  privmsg("REPLY TO $addr: $msg");
  socket_write($clients[$client_index],"$msg\n");
}

#####################################################################################################

function on_connect(&$server,&$clients,&$connections,$client_index)
{
  $connection_index=connection_index($connections,$client_index,True);
  if ($connection_index===False)
  {
    $addr="";
    socket_getpeername($clients[$client_index],$addr);
    $connection=array();
    $connection["client_index"]=$client_index;
    $connection["addr"]=$addr;
    $connection["connect_timestamp"]=microtime(True);
    $connection["ident_prefix"]="";
    $connection["authenticated"]=False;
    $connections[]=$connection;
    broadcast($server,$clients,$connections,"*** CLIENT CONNECTED: $addr");
  }
  else
  {
    do_reply($server,$clients,$connections,$client_index,"*** CLIENT CONNECT ERROR: CONNECTION EXISTS ALREADY");
  }
}

#####################################################################################################

function on_disconnect(&$server,&$clients,&$connections,$client_index)
{
  $connection_index=connection_index($connections,$client_index);
  if ($connection_index===False)
  {
    privmsg("*** CLIENT DISCONNECT ERROR: CONNECTION NOT FOUND");
  }
  else
  {
    $addr=$connections[$connection_index]["addr"];
    privmsg("*** CLIENT DISCONNECTED: $addr");
    unset($connections[$connection_index]);
  }
}

#####################################################################################################

function on_msg(&$server,&$clients,&$connections,$client_index,$data)
{
  do_reply($server,$clients,$connections,$client_index,"received text: $data");
}

#####################################################################################################

function log_msg(&$server,&$clients,&$connections,$addr,$client_index,$data)
{
  # TODO
}

#####################################################################################################

function is_admin($nick)
{
  global $admin_accounts;
  $account=users_get_account($nick);
  if ($account=="")
  {
    return False;
  }
  if (in_array($account,$admin_accounts)==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

?>
