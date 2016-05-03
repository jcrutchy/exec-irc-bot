<?php

#####################################################################################################

# required command line parameters: %%trailing%% %%nick%% %%dest%% %%server%% %%hostname%% %%alias%%

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
$hostname=$argv[5];
$alias=$argv[6];

$user="$hostname $server";

if (file_exists(DATA_PATH.DATA_PREFIX."_admins.txt")==False)
{
  privmsg("server admins file \"".DATA_PATH.DATA_PREFIX."_admins.txt"."\" not found");
  return;
}
$admin_users=file_get_contents(DATA_PATH.DATA_PREFIX."_admins.txt");
if ($admin_users===False)
{
  privmsg("error reading server admins file");
  return;
}
$admin_users=json_decode($admin_users,True);
if ($admin_users===Null)
{
  privmsg("error decoding server admins file");
  return;
}

if (in_array($user,$admin_users)==False)
{
  privmsg("not authorized");
  return;
}

$parts=explode(" ",$trailing);
$action=array_shift($parts);
switch ($action)
{
  case "purge":
    $file_list=scandir(DATA_PATH);
    $port_filename_suffix=".txt";
    $found=0;
    for ($i=0;$i<count($file_list);$i++)
    {
      $test_filename=$file_list[$i];
      if (substr($test_filename,0,strlen(DATA_PREFIX."_port_"))<>(DATA_PREFIX."_port_"))
      {
        continue;
      }
      if (substr($test_filename,strlen($test_filename)-strlen($port_filename_suffix))<>$port_filename_suffix)
      {
        continue;
      }
      $found++;
      if (unlink(DATA_PATH.$test_filename)===False)
      {
        privmsg("error deleting port file \"$test_filename\"");
      }
      else
      {
        privmsg("deleted port file \"$test_filename\"");
      }
    }
    if ($found==0)
    {
      privmsg("no port files found");
    }
    $list=bucket_list();
    $list=explode(" ",$list);
    $prefix=DATA_PREFIX."_server_";
    $found=0;
    for ($i=0;$i<count($list);$i++)
    {
      if (substr($list[$i],0,strlen($prefix))===$prefix)
      {
        $found++;
        $port=get_bucket($list[$i]);
        $channel=substr($list[$i],strlen(DATA_PREFIX."_server_".$server."_"));
        $server_id=base64_encode($server." ".$channel." ".$port);
        set_bucket(DATA_PREFIX."_server_command_$server_id","stop");
        sleep(4);
        unset_bucket($list[$i]);
        privmsg("deleted server bucket \"".$list[$i]."\"");
      }
    }
    if ($found==0)
    {
      privmsg("no servers found");
    }
    break;
  case "start":
    $channel="";
    $port="";
    if (count($parts)==1)
    {
      $channel=$parts[0];
      $port=50000;
      while (file_exists(DATA_PATH.DATA_PREFIX."_port_".$port.".txt")==True)
      {
        $port++;
      }
    }
    if (count($parts)==2)
    {
      $channel=$parts[0];
      $port=$parts[1];
    }
    if (($channel==="") or ($port===""))
    {
      privmsg("syntax: $alias start <channel> [<tcp_port>]");
      privmsg("example: $alias start #channel 50000");
      privmsg("example: $alias start #channel");
      privmsg("tcp_port should be >= 50000");
    }
    else
    {
      if (get_bucket(DATA_PREFIX."_server_".$server."_$channel")=="")
      {
        run_server($server,$channel,$port,$hostname);
      }
      else
      {
        privmsg("server for $channel on $server already running");
      }
    }
    break;
  case "stop":
    if (count($parts)==1)
    {
      $channel=$parts[0];
      if ($channel=="all")
      {
        $list=bucket_list();
        $list=explode(" ",$list);
        $prefix=DATA_PREFIX."_server_";
        $found=0;
        for ($i=0;$i<count($list);$i++)
        {
          if (substr($list[$i],0,strlen($prefix))===$prefix)
          {
            $found++;
            $port=get_bucket($list[$i]);
            $channel=substr($list[$i],strlen(DATA_PREFIX."_server_".$server."_"));
            $server_id=base64_encode($server." ".$channel." ".$port);
            set_bucket(DATA_PREFIX."_server_command_$server_id","stop");
          }
        }
        if ($found==0)
        {
          privmsg("no servers found");
        }
        return;
      }
      $port=get_bucket(DATA_PREFIX."_server_".$server."_$channel");
      $server_id=base64_encode($server." ".$channel." ".$port);
      if ($port<>"")
      {
        set_bucket(DATA_PREFIX."_server_command_$server_id","stop");
      }
      else
      {
        privmsg("server not found");
      }
    }
    else
    {
      privmsg("syntax: $alias stop #channel");
      privmsg("special: $alias stop all");
    }
    break;
  case "restart":
    if (count($parts)==1)
    {
      $channel=$parts[0];
      $port=get_bucket(DATA_PREFIX."_server_".$server."_$channel");
      $server_id=base64_encode($server." ".$channel." ".$port);
      if ($port<>"")
      {
        set_bucket(DATA_PREFIX."_server_command_$server_id","stop");
        $t=microtime(True);
        while ((get_bucket(DATA_PREFIX."_server_".$server."_$channel")<>"") and ((microtime(True)-$t)<10e6))
        {
          usleep(0.05e6);
        }
        if (get_bucket(DATA_PREFIX."_server_".$server."_$channel")<>"")
        {
          privmsg("error: unable to verify server has been stopped");
          return;
        }
        sleep(4);
        run_server($server,$channel,$port,$hostname);
      }
      else
      {
        privmsg("server not found");
      }
    }
    else
    {
      privmsg("syntax: $alias restart #channel");
    }
    break;
}

#####################################################################################################

function run_server($irc_server,$channel,$listen_port,$hostname)
{
  $server_data=array(
    "irc_server"=>$irc_server,
    "channel"=>$channel,
    "listen_port"=>$listen_port,
    "game_data_updated"=>True,
    "game_data"=>array(),
    "server_admin"=>$hostname);
  $sneak_server_id=base64_encode($irc_server." ".$channel." ".$listen_port);
  $port_filename=DATA_PATH."sneak_port_$listen_port.txt";
  if (file_exists($port_filename)==True)
  {
    privmsg("sneak server listening on port $listen_port already running for ".trim(file_get_contents($port_filename)));
    return;
  }
  $data_filename=DATA_PATH."sneak_data_".base64_encode($irc_server." ".$channel).".txt";
  if (file_exists($data_filename)==True)
  {
    $server_data["game_data"]=json_decode(file_get_contents($data_filename),True);
    $server_data["game_data_updated"]=False;
  }
  $listen_address="127.0.0.1";
  $max_data_length=1024;
  $connections=array();
  privmsg("starting game server for channel $channel@$irc_server, listening on $listen_address:$listen_port");
  $server=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
  if ($server===False)
  {
    server_privmsg($server_data,"*** socket_create() failed: reason: ".socket_strerror(socket_last_error()));
    return;
  }
  if (socket_get_option($server,SOL_SOCKET,SO_REUSEADDR)===False)
  {
    server_privmsg($server_data,"*** socket_get_option() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  if (@socket_bind($server,$listen_address,$listen_port)===False)
  {
    server_privmsg($server_data,"*** socket_bind() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  if (socket_listen($server,5)===False)
  {
    server_privmsg($server_data,"*** socket_listen() failed: reason: ".socket_strerror(socket_last_error($server)));
    return;
  }
  if (file_put_contents($port_filename,"$channel $irc_server")===False)
  {
    server_privmsg($server_data,"error saving port file \"$port_filename\"");
    return;
  }
  $bucket_index="sneak_server_".$irc_server."_$channel";
  set_bucket($bucket_index,$listen_port);
  $clients=array($server);
  while (True)
  {
    usleep(0.05e6);
    if (get_bucket($bucket_index)<>$listen_port)
    {
      server_privmsg($server_data,"server bucket not found - stopping");
      break;
    }
    $server_command=get_bucket("sneak_server_command_$sneak_server_id");
    switch ($server_command)
    {
      case "stop":
        unset_bucket("sneak_server_command_$sneak_server_id");
        break 2;
    }
    loop_process($server_data,$server,$clients,$connections);
    $read=$clients;
    $write=NULL;
    $except=NULL;
    if (socket_select($read,$write,$except,0)<1)
    {
      continue;
    }
    if (in_array($server,$read)==True)
    {
      $client=socket_accept($server);
      $clients[]=$client;
      $client_index=array_search($client,$clients);
      $addr="";
      socket_getpeername($client,$addr);
      #server_privmsg($server_data,"connected to remote address $addr");
      on_connect($server_data,$server,$clients,$connections,$client_index);
      $n=count($clients)-1;
      socket_write($client,"successfully connected to sneak server\n");
      socket_write($client,"there are $n clients connected\n");
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
          #server_privmsg($server_data,"disconnecting from remote address $addr");
        }
        on_disconnect($server_data,$server,$clients,$connections,$client_index);
        socket_close($read_client);
        unset($clients[$client_index]);
        #server_privmsg($server_data,"client disconnected");
        continue;
      }
      $data=trim($data);
      if ($data=="")
      {
        continue;
      }
      $addr="";
      socket_getpeername($read_client,$addr);
      #server_privmsg($server_data,"message received from $addr: $data");
      if (($data=="quit") or ($data=="shutdown"))
      {
        server_privmsg($server_data,"$data received from $addr");
        if ($data=="quit")
        {
          socket_shutdown($read_client,2);
          socket_close($read_client);
          unset($clients[$client_index]);
          break;
        }
        break 2;
      }
      log_msg($server_data,$server,$clients,$connections,$addr,$client_index,$data);
      on_msg($server_data,$server,$clients,$connections,$client_index,$data);
    }
    if ($server_data["game_data_updated"]==True)
    {
      if (file_put_contents($data_filename,json_encode($server_data["game_data"],JSON_PRETTY_PRINT))===False)
      {
        server_privmsg($server_data,"fatal error writing game data file \"$data_filename\"");
        break;
      }
    }
  }
  broadcast($server_data,$server,$clients,$connections,"*** SERVER SHUTTING DOWN NOW!");
  sleep(1);
  foreach ($clients as $client_index => $socket)
  {
    if ($clients[$client_index]<>$server)
    {
      $addr="";
      socket_getpeername($clients[$client_index],$addr);
      server_privmsg($server_data,"disconnecting from remote address $addr");
      socket_shutdown($clients[$client_index],2);
      socket_close($clients[$client_index]);
      unset($clients[$client_index]);
    }
  }
  socket_shutdown($server,2);
  socket_close($server);
  if (@unlink($port_filename)===False)
  {
    server_privmsg($server_data,"error deleting port file \"$port_filename\"");
  }
  server_privmsg($server_data,"stopping game server");
  unset_bucket("sneak_server_".$irc_server."_$channel");
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

function loop_process(&$server_data,&$server,&$clients,&$connections)
{
  # do other stuff here if need be
}

#####################################################################################################

function broadcast(&$server_data,&$server,&$clients,&$connections,$msg)
{
  #server_privmsg($server_data,"BROADCAST: $msg");
  foreach ($clients as $send_client)
  {
    if ($send_client<>$server)
    {
      socket_write($send_client,"$msg\n");
    }
  }
}

#####################################################################################################

function do_reply(&$server_data,&$server,&$clients,&$connections,$client_index,$msg)
{
  $addr="";
  socket_getpeername($clients[$client_index],$addr);
  #server_privmsg($server_data,"REPLY TO $addr: $msg");
  socket_write($clients[$client_index],"$msg\n");
}

#####################################################################################################

function on_connect(&$server_data,&$server,&$clients,&$connections,$client_index)
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
    broadcast($server_data,$server,$clients,$connections,"*** CLIENT CONNECTED: $addr");
    server_privmsg($server_data,"*** CLIENT CONNECTED: $addr");
  }
  else
  {
    do_reply($server_data,$server,$clients,$connections,$client_index,"*** CLIENT CONNECT ERROR: CONNECTION EXISTS ALREADY");
  }
}

#####################################################################################################

function on_disconnect(&$server_data,&$server,&$clients,&$connections,$client_index)
{
  $connection_index=connection_index($connections,$client_index);
  if ($connection_index===False)
  {
    server_privmsg($server_data,"*** CLIENT DISCONNECT ERROR: CONNECTION NOT FOUND");
  }
  else
  {
    $addr=$connections[$connection_index]["addr"];
    server_privmsg($server_data,"*** CLIENT DISCONNECTED: $addr");
    unset($connections[$connection_index]);
  }
}

#####################################################################################################

function on_msg(&$server_data,&$server,&$clients,&$connections,$client_index,$data)
{
  $unpacked=base64_decode($data);
  if ($unpacked===False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"decoding error");
    return;
  }
  $unpacked=unserialize($unpacked);
  if ($unpacked===False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"unserializing error");
    return;
  }
  if (is_array($unpacked)==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: request is not an array");
    return;
  }
  if (isset($unpacked["channel"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: channel missing");
    return;
  }
  $channel=$unpacked["channel"];
  if (isset($unpacked["nick"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: nick missing");
    return;
  }
  $nick=$unpacked["nick"];
  if (isset($unpacked["user"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: user missing");
    return;
  }
  $user=$unpacked["user"];
  if (isset($unpacked["hostname"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: hostname missing");
    return;
  }
  $hostname=$unpacked["hostname"];
  if (isset($unpacked["trailing"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: trailing missing");
    return;
  }
  $trailing=trim($unpacked["trailing"]);
  if ($trailing=="")
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: trailing empty");
    return;
  }
  $parts=explode(" ",$trailing);
  $action=array_shift($parts);
  if ($action<>"admin-init-chan")
  {
    if (isset($server_data["game_data"][$channel])==False)
    {
      server_reply($server_data,$server,$clients,$connections,$client_index,"error: channel not initialized");
      return;
    }
    if (substr($action,0,6)<>"admin-")
    {
      if (isset($server_data["game_data"][$channel]["players"][$hostname])==False)
      {
        init_player($server_data,$channel,$hostname);
      }
    }
  }
  $response=array();
  $response["msg"]=array();
  switch ($action)
  {
    case "admin-del-chan":
      if ($server_data["server_admin"]==$hostname)
      {
        if (count($parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$parts[0];
        if (isset($server_data["game_data"][$subject])==True)
        {
          unset($server_data["game_data"][$subject]);
          $server_data["game_data_updated"]=True;
          $response["msg"][]="channel \"$subject\" deleted";
        }
        else
        {
          $response["msg"][]="channel \"$subject\" not found";
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "admin-init-chan":
      if ($server_data["server_admin"]==$hostname)
      {
        if (count($parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$parts[0];
        if (isset($server_data["game_data"][$subject])==True)
        {
          unset($server_data["game_data"][$subject]);
        }
        init_chan($server_data,$subject);
        $response["msg"][]="channel \"$subject\" initialized";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-kill":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$parts[0];
        if (isset($server_data["game_data"][$channel]["players"][$subject])==True)
        {
          unset($server_data["game_data"][$channel]["players"][$subject]);
          $server_data["game_data_updated"]=True;
          $response["msg"][]="player \"$subject\" deleted from the game server in this channel";
        }
        else
        {
          $response["msg"][]="player \"$subject\" not found on the game server in this channel";
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-player-data":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($parts)<>1)
        {
          $response["msg"][]="invalid number of parameters";
          break;
        }
        $subject=$parts[0];
        $user_data=users_get_data($subject);
        if (isset($user_data["hostname"])==False)
        {
          $response["msg"][]="nick \"$subject\" not found";
        }
        else
        {
          $subject=$user_data["hostname"];
          if (isset($server_data["game_data"][$channel]["players"][$subject])==True)
          {
            $output=var_export($server_data["game_data"][$channel]["players"][$subject],True);
            output_ixio_paste($output,False);
            $response["msg"][]="player data for \"$subject\" dumped to http://ix.io/nAz";
          }
          else
          {
           $response["msg"][]="player \"$subject\" not found on the game server in this channel";
          }
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-map":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        $response["msg"][]="i farted";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-edit-player":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        if (count($parts)<=3)
        {
          $response["msg"][]="not enough parameters";
          break;
        }
        $subject=array_shift($parts);
        $user_data=users_get_data($subject);
        if (isset($user_data["hostname"])==False)
        {
          $response["msg"][]="nick \"$subject\" not found";
        }
        else
        {
          $subject=$user_data["hostname"];
          if (isset($server_data["game_data"][$channel]["players"][$subject])==True)
          {
            $data=implode(" ",$parts);
            $elements=explode("=",$data);
            for ($i=0;$i<count($elements);$i++)
            {
              $elements[$i]=trim($elements[$i]);
            }
            if (count($elements)<2)
            {
              $response["msg"][]="syntax error";
              break;
            }
            $key=array_shift($elements);
            $value=implode("=",$elements);
            $key_elements=explode(" ",$key);
            $data=&$server_data["game_data"][$channel]["players"][$subject];
            for ($i=0;$i<count($key_elements);$i++)
            {
              if (isset($data[$key_elements[$i]])==False)
              {
                $data[$key_elements[$i]]=array();
              }
              $data=&$data[$key_elements[$i]];
            }
            $data=$value;
            unset($data);
            $server_data["game_data_updated"]=True;
            $response["msg"][]="player data for \"$subject\" updated";
          }
          else
          {
           $response["msg"][]="player \"$subject\" not found on the game server in this channel";
          }
        }
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "gm-edit-goody":
      if (is_gm($server_data,$hostname,$channel)==True)
      {
        $response["msg"][]="i farted";
      }
      else
      {
        $response["msg"][]="not authorized";
      }
      break;
    case "help":
    case "?":

      break;
    case "player-list":

      break;
    case "chan-list":

      break;
    case "start":

      break;
    case "status":

      break;
    case "die":

      break;
    case "rank":

      break;
    case "l":
    case "left":

      break;
    case "r":
    case "right":

      break;
    case "u":
    case "up":

      break;
    case "d":
    case "down":

      break;
  }
  if (count($response["msg"])==0)
  {
    $response["msg"][]="invalid action";
  }
  $data=base64_encode(serialize($response));
  do_reply($server_data,$server,$clients,$connections,$client_index,$data);
}

#####################################################################################################

function is_gm(&$server_data,$hostname,$channel)
{
  if (isset($server_data["game_data"][$channel]["moderators"])==False)
  {
    return False;
  }
  if ($hostname<>"")
  {
    if (in_array($hostname,$server_data["game_data"][$channel]["moderators"])==True)
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function server_reply(&$server_data,&$server,&$clients,&$connections,$client_index,$msg)
{
  $response=array();
  $response["msg"]=$msg;
  $data=base64_encode(serialize($response));
  do_reply($server_data,$server,$clients,$connections,$client_index,$data);
}

#####################################################################################################

function log_msg(&$server_data,&$server,&$clients,&$connections,$addr,$client_index,$data)
{
  # TODO
}

#####################################################################################################

function server_privmsg(&$server_data,$msg)
{
  $irc_server=$server_data["irc_server"];
  $channel=$server_data["channel"];
  $listen_port=$server_data["listen_port"];
  privmsg("$channel@$irc_server:$listen_port >> $msg");
}

#####################################################################################################

function init_chan(&$server_data,$channel)
{
  $record=array();
  $record["moderators"]=array($server_data["server_admin"]);
  $record["players"]=array();
  $record["goodies"]=array();
  $record["map_size"]=30;
  $server_data["game_data"][$channel]=$record;
  $server_data["game_data_updated"]=True;
}

#####################################################################################################

function init_player(&$server_data,$channel,$hostname)
{
  $record=array();
  $record["hostname"]=$hostname;
  $server_data["game_data"][$channel]["players"][$hostname]=$record;
  $server_data["game_data_updated"]=True;
}

#####################################################################################################

?>
