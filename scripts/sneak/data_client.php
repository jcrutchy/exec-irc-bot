<?php

#####################################################################################################

/*

required command line parameters: %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%

TODO: if client commands are to be sent from a different channel (or pm), a way to specify the game channel needs to be incorporated in the client script to work out which server to connect to

*/

#####################################################################################################

require_once(__DIR__."/../lib.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

$port="";
$file_list=scandir(DATA_PATH);
$port_filename_prefix=DATA_PREFIX."_port_";
$port_filename_suffix=".txt";
for ($i=0;$i<count($file_list);$i++)
{
  $test_filename=$file_list[$i];
  if (substr($test_filename,0,strlen($port_filename_prefix))<>$port_filename_prefix)
  {
    continue;
  }
  if (substr($test_filename,strlen($test_filename)-strlen($port_filename_suffix))<>$port_filename_suffix)
  {
    continue;
  }
  $test_port=substr($test_filename,strlen($port_filename_prefix),strlen($test_filename)-strlen($port_filename_suffix)-strlen($port_filename_prefix));
  $port_data=trim(file_get_contents(DATA_PATH.$test_filename));
  $port_data=explode(" ",$port_data);
  if (count($port_data)<>2)
  {
    continue;
  }
  $test_channel=$port_data[0];
  $test_server=$port_data[1];
  if (($test_channel===$dest) and ($server===$test_server))
  {
    $port=$test_port;
    break;
  }
}
if ($port=="")
{
  privmsg("error: unable to find server port file for this irc server and channel");
  return;
}

$socket=fsockopen("127.0.0.1",$port);
if ($socket===False)
{
  privmsg("error connecting to server @ 127.0.0.1:$port");
  unlink(DATA_PATH.$port_filename_prefix.$port.$port_filename_suffix);
  return;
}
stream_set_blocking($socket,0);

$unpacked=array();
$unpacked["channel"]=$dest;
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
