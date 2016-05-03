<?php

#####################################################################################################

/*
exec:add ~sneak
exec:edit ~sneak cmd php scripts/sneak/sneak_client.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~sneak
*/

#####################################################################################################

/*

sneak
=====

sneak is an irc game where each player aims to increase their kills by moving into the same coordinate as other players

the play area is square and continuous (when a player moves off one edge they appear on the opposite egde)

the size of the play area is dependent on the number of players; when there are lots of players, the size increases
when the number of players decreases and an edge is unoccupoied, the play area is reduced

when a player is killed, their handicap decreases by one. when a player kills another, their handicap increases by one

players are identified by their nick!user@hostname

there are goody boxes randomly dispersed around the play area, that are consumed when occupied and appear randomly at unoccupied coordinates
goody boxes could kill the player, relocate them to a random coordinate (if occupied resulting in a kill for that player), or could indicate the relative location of the nearest player (eg: 2 spaces up, 4 spaces right)

output to players is via pm
channel output to #sneak is only certain events (when someone dies or the map changes)

*/

#####################################################################################################

# ONLY SEND STUFF TO THE SERVER THAT AFFECTS THE DATA FILES, LIKE COMMANDS THAT CHANGE DATA AND REQUESTS FOR DATA
# THINGS LIKE PROCESSING ix.io OUTPUT ETC SHOULD BE DONE IN THE CLIENT SCRIPT

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
date_default_timezone_set("UTC");

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
$port_filename_prefix="sneak_port_";
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
  privmsg("error: unable to find sneak server port file for this irc server and channel");
  return;
}

$socket=fsockopen("127.0.0.1",$port);
if ($socket===False)
{
  privmsg("error connecting to sneak server @ 127.0.0.1:$port");
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
