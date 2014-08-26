<?php

# gpl2
# by crutchy
# 26-aug-2014

#####################################################################################################

ini_set("display_errors","on");

require_once("irc_lib.php");
require_once("lib_buckets.php");

$trailing=trim($argv[1]);
$dest=trim($argv[2]);
$nick=trim($argv[3]);
$parts=explode(" ",$trailing);
if (count($parts)<2)
{
  return;
}
$cmd=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));
switch ($cmd)
{
  case "new":
    $socket=fsockopen("ssl://irc.sylnt.us","6697");
    if ($socket===False)
    {
      term_echo("ERROR CREATING IRC SOCKET");
      return;
    }
    stream_set_blocking($socket,0);
    rawmsg("NICK $trailing");
    rawmsg("USER $trailing hostname servername :$trailing.bot");
    $valid_data_cmd=get_valid_data_cmd(False);
    while (True)
    {
      usleep(0.1e6);
      $data=get_bucket("MINION_CMD_$trailing");
      $items=parse_data($data);
      if ($items!==False)
      {
        unset_bucket("MINION_CMD_$trailing");
        rawmsg($data);
      }
      $data=fgets($socket);
      if ($data===False)
      {
        continue;
      }
      $data=trim($data);
      if (pingpong($data)==True)
      {
        continue;
      }
      term_echo($trailing." >> ".$data);
      $items=parse_data($data);
      if ($items===False)
      {
        continue;
      }
      if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
      {
        dojoin("#");
      }
    }
    break;
  case "say":
    $bot_nick=$parts[0];
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    $items=parse_data($trailing);
    if ($items!==False)
    {
      term_echo("BOT SAY: MINION_CMD_$bot_nick = $trailing");
      set_bucket("MINION_CMD_$bot_nick",$trailing);
    }
    else
    {
      echo "/PRIVMSG $nick: invalid command \"$trailing\" for $bot_nick";
    }
    break;
}

#####################################################################################################

function rawmsg($msg)
{
  global $socket;
  fputs($socket,$msg."\n");
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[35m$msg\033[0m\n";
}

#####################################################################################################

?>
