<?php

#####################################################################################################

/*

required command line parameters: %%trailing%% %%nick%% %%dest%% %%server%% %%hostname%% %%alias%% %%cmd%%

can run one data server per APP_NAME per server

data files are named: DATA_PATH.APP_NAME."_data_".base64_encode($irc_server).".txt"

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
required data server code (eg for sneak_server.php):

#exec:add ~sneak-server
#exec:edit ~sneak-server timeout 0
#exec:edit ~sneak-server cmd php scripts/sneak/sneak_server.php %%trailing%% %%nick%% %%dest%% %%server%% %%hostname%% %%alias%% %%cmd%%
#exec:enable ~sneak-server
#startup:~join #sneak

define("APP_NAME","sneak");
require_once("data_server.php");

optional event handler functions:
function server_start_handler(&$server_data,&$server,&$clients,&$connections)
function server_stop_handler(&$server_data,&$server,&$clients,&$connections)
function server_connect_handler(&$server_data,&$server,&$clients,&$connections,$client_index)
function server_disconnect_handler(&$server_data,&$server,&$clients,&$connections,$client_index)
function server_msg_handler(&$server_data,&$server,&$clients,&$connections,$client_index,$unpacked,&$response,$trailing_parts,$action)

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

*/

#####################################################################################################

/*

GAME IDEAS:
- sneak
- battleship
- risk
- dungeon
- IRCiv

TODO: ADD STANDARD GAME GRAPHICS & IMAGE SERVER UPLOAD LIBRARY

*/

#####################################################################################################

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set("UTC");

require_once(__DIR__."/../lib.php");
require_once("data_utils.php");

define("MODS_PATH",__DIR__."/mods/");

$trailing=strtolower(trim($argv[1]));
$nick=$argv[2];
$dest=$argv[3];
$server=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];

check_server_bucket();

if ($cmd<>"INTERNAL")
{
  $user="$hostname $server";
  $admins_filename=DATA_PATH.APP_NAME."_admins.txt";
  if (file_exists($admins_filename)==False)
  {
    privmsg("server admins file \"".$admins_filename."\" not found");
    return;
  }
  $admin_users=file_get_contents($admins_filename);
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
}
else
{
  term_echo("data server: bypassing authentication for internal command");
  $dest="#".APP_NAME;
  $hostname=""; # TODO: SET TO BOT OPERATOR'S HOSTNAME
}

$server_bucket=get_server_bucket();

$parts=explode(" ",$trailing);
$action=array_shift($parts);
switch ($action)
{
  case "status":
    if ($server_bucket===False)
    {
      privmsg(APP_NAME." server not found");
    }
    else
    {
      privmsg(APP_NAME." server listening on port ".$server_bucket["port"]);
    }
    break;
  case "start":
    if ($server_bucket===False)
    {
      run_server($server,$hostname,$dest);
    }
    else
    {
      privmsg(APP_NAME." server already running");
    }
    break;
  case "stop":
    if ($server_bucket===False)
    {
      privmsg(APP_NAME." server not found");
    }
    else
    {
      unset_bucket(SERVER_BUCKET_INDEX);
    }
    break;
  case "test":
    if (count($parts)==0)
    {
      privmsg("error: missing test mod action");
    }
    privmsg("test mode");
    define("TEST_MODE","1");
    $action=array_shift($parts);
    $server_data=array(
      "irc_server"=>$server,
      "listen_port"=>50000,
      "dest"=>$dest,
      "app_data_updated"=>False,
      "app_data"=>array(),
      "server_admin"=>$hostname);
    $server=Null;
    $clients=array();
    $connections=array();
    $unpacked=array(
      "dest"=>$dest,
      "nick"=>$nick,
      "user"=>"",
      "hostname"=>$hostname,
      "trailing"=>implode(" ",$parts));
    $response=array();
    $response["msg"]=array();
    load_mod($server_data,$server,$clients,$connections,0,$unpacked,$response,$parts,$action);
    for ($i=0;$i<count($response["msg"]);$i++)
    {
      privmsg($response["msg"][$i]);
    }
    return;
  default:
    privmsg("syntax: $alias status|start|stop");
    break;
}

#####################################################################################################

function run_server($irc_server,$hostname,$dest)
{
  $used_ports=get_user_localhost_ports();
  $listen_port=50000;
  while (in_array($listen_port,$used_ports)==True)
  {
    $listen_port++;
  }
  $server_data=array(
    "irc_server"=>$irc_server,
    "listen_port"=>$listen_port,
    "dest"=>$dest,
    "app_data_updated"=>True,
    "app_data"=>array(),
    "server_admin"=>$hostname);
  $data_filename=DATA_PATH.APP_NAME."_data_".base64_encode($irc_server).".txt";
  if (file_exists($data_filename)==True)
  {
    $server_data["app_data"]=json_decode(file_get_contents($data_filename),True);
    $server_data["app_data_updated"]=False;
  }
  $listen_address="127.0.0.1";
  $max_data_length=1024;
  $connections=array();
  privmsg("starting app server listening on $listen_address:$listen_port");
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
  $pid=getmypid();
  $server_bucket=array("port"=>$listen_port,"pid"=>$pid);
  $server_bucket=serialize($server_bucket);
  $server_bucket=base64_encode($server_bucket);
  set_bucket(SERVER_BUCKET_INDEX,$server_bucket);
  $clients=array($server);
  if (function_exists("server_start_handler")==True)
  {
    server_start_handler($server_data,$server,$clients,$connections);
  }
  while (True)
  {
    usleep(0.05e6);
    if (bot_shutting_down()==True)
    {
      term_echo("*** bot shutdown detected - stopping ".APP_NAME." server ***");
      break;
    }
    $server_bucket=get_server_bucket();
    if ($server_bucket===False)
    {
      break;
    }
    if ($server_bucket["port"]<>$listen_port)
    {
      break;
    }
    if ($server_bucket["pid"]<>$pid)
    {
      break;
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
      on_connect($server_data,$server,$clients,$connections,$client_index);
      $n=count($clients)-1;
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
        on_disconnect($server_data,$server,$clients,$connections,$client_index);
        socket_close($read_client);
        unset($clients[$client_index]);
        continue;
      }
      $data=trim($data);
      if ($data=="")
      {
        continue;
      }
      $addr="";
      socket_getpeername($read_client,$addr);
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
    if ($server_data["app_data_updated"]==True)
    {
      if (file_put_contents($data_filename,json_encode($server_data["app_data"],JSON_PRETTY_PRINT))===False)
      {
        server_privmsg($server_data,"fatal error writing app data file \"$data_filename\" - stopping server");
        break;
      }
    }
  }
  if (function_exists("server_stop_handler")==True)
  {
    server_stop_handler($server_data,$server,$clients,$connections);
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
  unset_bucket(SERVER_BUCKET_INDEX);
  server_privmsg($server_data,"stopping ".APP_NAME." server");
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
    $connections[]=$connection;
    broadcast($server_data,$server,$clients,$connections,"*** CLIENT CONNECTED: $addr");
    if (function_exists("server_connect_handler")==True)
    {
      server_connect_handler($server_data,$server,$clients,$connections,$client_index);
    }
    #server_privmsg($server_data,"*** CLIENT CONNECTED: $addr");
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
    if (function_exists("server_disconnect_handler")==True)
    {
      server_disconnect_handler($server_data,$server,$clients,$connections,$client_index);
    }
    #server_privmsg($server_data,"*** CLIENT DISCONNECTED: $addr");
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
  if (isset($unpacked["dest"])==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: dest missing");
    return;
  }
  $dest=$unpacked["dest"];
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
  # DO NOT ALLOW BRACKETS
  $valid_chars=VALID_UPPERCASE.VALID_LOWERCASE.VALID_NUMERIC." -_";
  if (is_valid_chars($trailing,$valid_chars)==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"error: trailing contains illegal chars");
    return;
  }
  $parts=explode(" ",$trailing);
  $action=array_shift($parts);
  $response=array();
  $response["msg"]=array();
  if (function_exists("server_msg_handler")==True)
  {
    server_msg_handler($server_data,$server,$clients,$connections,$client_index,$unpacked,$response,$parts,$action);
  }
  else
  {
    load_mod($server_data,$server,$clients,$connections,$client_index,$unpacked,$response,$parts,$action);
  }
  if (count($response["msg"])==0)
  {
    $response["msg"][]="invalid action";
  }
  $data=base64_encode(serialize($response));
  do_reply($server_data,$server,$clients,$connections,$client_index,$data);
}

#####################################################################################################

function server_reply(&$server_data,&$server,&$clients,&$connections,$client_index,$msg)
{
  if (defined("TEST_MODE")==True)
  {
    privmsg("test mode: ".$msg);
    return;
  }
  $response=array();
  $response["msg"][]=$msg;
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
  global $dest;
  pm($dest,$msg);
}

#####################################################################################################

function load_mod(&$server_data,&$server,&$clients,&$connections,$client_index,$unpacked,&$response,$trailing_parts,$action)
{
  # TODO: LOAD MODS ON STARTUP & ONLY RELOAD IF FILE MODIFIED TIME DIFFERS
  $mod_filename="mod_".APP_NAME."_".$action;
  $code=read_mod($server_data,$server,$clients,$connections,$client_index,$mod_filename);
  if ($code===False)
  {
    return False;
  }
  if (defined("TEST_MODE")==True)
  {
    # save $code to temp file, include it, then unlink temp file
    return;
  }
  $result=@eval($code);
  if ($result===False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"mod: file \"".$mod_filename."\" eval returned false");
  }
  return $result;
}

#####################################################################################################

function read_mod(&$server_data,&$server,&$clients,&$connections,$client_index,$filename)
{
  $mod_filename=MODS_PATH.$filename;
  if (file_exists($mod_filename)==False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"mod: file \"".$filename."\" not found");
    return;
  }
  $code=file_get_contents($mod_filename);
  if ($code===False)
  {
    server_reply($server_data,$server,$clients,$connections,$client_index,"mod: error reading file \"".$filename."\"");
    return False;
  }
  # TODO: PROCESS MOD MACROS (SIMILAR TO EXEC MACROS) - EG: mod:include mod_sneak_down (ON SEPARATE LINE IN MULTI-LINE COMMENT OF MOD FILE)
  $lines=explode(PHP_EOL,$code);
  $mod_prefix="mod:";
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    if ($line=="")
    {
      continue;
    }
    if (($line=="<?php") or ($line=="?>"))
    {
      $lines[$i]="";
      continue;
    }
    if (substr($line,0,strlen($mod_prefix))==$mod_prefix)
    {
      $macro=trim(substr($line,strlen($mod_prefix)));
      $lines[$i]="";
      if ($macro=="")
      {
        server_reply($server_data,$server,$clients,$connections,$client_index,"mod: macro empty");
        continue;
      }
      server_reply($server_data,$server,$clients,$connections,$client_index,"mod: macro found => $macro");
      $parts=explode(" ",$macro);
      $operation=array_shift($parts);
      switch ($operation)
      {
        case "include":
          if (count($parts)<>1)
          {
            server_reply($server_data,$server,$clients,$connections,$client_index,"mod: invalid include macro");
            continue;
          }
          $include_filename=array_shift($parts);
          $include_code=read_mod($server_data,$server,$clients,$connections,$client_index,$include_filename);
          if ($include_code===False)
          {
            server_reply($server_data,$server,$clients,$connections,$client_index,"mod: include macro read error");
            continue;
          }
          $include_lines=explode(PHP_EOL,$include_code);
          $lines=array_merge($include_lines,$lines);
          break;
        default:
          server_reply($server_data,$server,$clients,$connections,$client_index,"mod: invalid macro operation");
          break;
      }
    }
  }
  return implode(PHP_EOL,$lines);
}

#####################################################################################################

?>
