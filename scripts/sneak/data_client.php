<?php

#####################################################################################################

# required command line parameters: %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%

require_once(__DIR__."/../lib.php");
require_once("data_utils.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

check_server_bucket();

if ($trailing=="")
{
  return;
}

$server_bucket=get_server_bucket();
if ($server_bucket===False)
{
  privmsg("server not found");
  return;
}

$socket=fsockopen("127.0.0.1",$server_bucket["port"]);
if ($socket===False)
{
  privmsg("error connecting to server @ 127.0.0.1:".$server_bucket["port"]);
  return;
}
stream_set_blocking($socket,0);

$unpacked=array();
$unpacked["dest"]=$dest;
$unpacked["nick"]=$nick;
$unpacked["user"]=$user;
$unpacked["hostname"]=$hostname;
$unpacked["trailing"]=$trailing;
$data=base64_encode(serialize($unpacked));
fputs($socket,$data."\n");
$t=microtime(True);
$unpacked=array();
while (True)
{
  usleep(0.1e6);
  if ((microtime(True)-$t)>5e6)
  {
    break;
  }
  $data=fgets($socket);
  if ($data===False)
  {
    continue;
  }
  $data=trim($data);
  $unpacked=array();
  $unpacked=@base64_decode($data);
  if ($unpacked===False)
  {
    continue;
  }
  $unpacked=@unserialize($unpacked);
  if ($unpacked===False)
  {
    continue;
  }
  if (is_array($unpacked)==False)
  {
    continue;
  }
  break;
}

if (isset($unpacked["msg"])==False)
{
  privmsg(chr(3)."03"."error: response message not found");
  return;
}

if (is_array($unpacked["msg"])==False)
{
  privmsg(chr(3)."03"."error: response message not an array");
  return;
}

if (count($unpacked["msg"])==0)
{
  privmsg(chr(3)."03"."error: response message array has no elements");
  return;
}

if (count($unpacked["msg"])>10)
{
  privmsg(chr(3)."03"."error: response message array has too many elements");
  return;
}

for ($i=0;$i<count($unpacked["msg"]);$i++)
{
  privmsg(chr(3)."03".$unpacked["msg"][$i]);
}

#####################################################################################################

?>
