<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~relay|0|0|0|1|@||||php scripts/relay.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

require_once("lib.php");

define("RELAY_HOST","irciv.us.to");
define("RELAY_URI","/?exec");
define("RELAY_PORT","80");
define("EXEC_KEY_FILE","../pwd/exec_key");
define("NOTIFY_HOST","192.168.0.21");
define("NOTIFY_PORT",50000);

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];

$errno=0;
$errstr="";
$socket=@fsockopen(NOTIFY_HOST,NOTIFY_PORT,$errno,$errstr,5);

if ($socket===False)
{
  notice($nick,"ERROR: UNABLE TO CONNECT TO NOTIFICATION SERVER");
  return;
}

notice($nick,"CONNECTED TO NOTIFICATION SERVER");

while (True)
{
  $data=fgets($socket);
  if ($data===False)
  {
    $meta=stream_get_meta_data($socket);
    if ($meta["eof"]==True)
    {
      notice($nick,"CONNECTION TERMINATED");
      return;
    }
    continue;
  }
  $data=trim($data);
  if (strpos($data,"quit-bot-relay")!==False)
  {
    notice($nick,"quitting relay");
    return;
  }
  $arr=@unserialize($data);
  if ($arr===False)
  {
    notice($nick,$data);
  }
  else
  {
    if ((isset($arr["data"])==True) and (isset($arr["username"])==True))
    {
      notice($nick,"sent from ".$arr["username"].": ".$arr["data"]);
    }
    else
    {
      var_dump($arr);
    }
  }
}

#####################################################################################################

/*$key=file_get_contents(EXEC_KEY_FILE);
if ($key===False)
{
  term_echo("ERROR: UNABLE TO READ EXEC KEY FILE");
  return;
}
$params=array();
$params["exec_key"]=$key;
$response=wpost(RELAY_HOST,RELAY_URI,RELAY_PORT,"",$params);
$content=trim(strip_headers($response));
$errstr="<html";
if (($content=="") or ($content=="NO REQUESTS") or (strpos($content,$errstr)!==False))
{
  return;
}
$errstr="ERROR";
if (strpos($content,$errstr)!==False)
{
  term_echo($content);
  return;
}
$request_lines=explode("\n",$content);
for ($i=0;$i<count($request_lines);$i++)
{
  $request_data=@unserialize($request_lines[$i]);
  if ($request_data===False)
  {
    term_echo("RELAY ERROR: PROBLEM UNSERIALIZING REQUEST DATA");
    continue;
  }
  if (isset($request_data["request_id"])==False)
  {
    term_echo("RELAY ERROR: REQUEST ID NOT FOUND");
    continue;
  }
  # $relay_requests[$request_data["request_id"]]=$request_data;
  # unset request after handled
  # construct items with REMOTE as cmd
  # pass request id as dest
  # pass username as nick
  # pass items to handle_data function
  # need to make /REMOTE and REMOTE cmd stdout handlers (unsets request from dest in process data)
  # remote handler calls send_relay_response function
  send_relay_response($request_data["request_id"],"farts are awesome");
}*/

#####################################################################################################

function send_relay_response($request_id,$data)
{
  $key=file_get_contents(EXEC_KEY_FILE);
  if ($key===False)
  {
    term_echo("ERROR: UNABLE TO READ EXEC KEY FILE");
    return;
  }
  $params=array();
  $params["exec_key"]=$key;
  $params["request_id"]=$request_id;
  $params["data"]=$data;
  $response=wpost(RELAY_HOST,RELAY_URI,RELAY_PORT,"",$params);
  unset($key);
  unset($params);
  $content=strip_headers($response);
  var_dump($content);
}

#####################################################################################################

?>
