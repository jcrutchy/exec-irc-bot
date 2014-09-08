<?php

# gpl2
# by crutchy
# 8-sep-2014

#####################################################################################################

# ~bot say dogfart :dogfart PRIVMSG # :test

/*
<crutchy> dogfartopoly join #soylent
<crutchy> dogfartopoly slap chromas
*/

ini_set("display_errors","on");

require_once("irc_lib.php");
require_once("lib_buckets.php");
require_once("users_lib.php");

define("BOT_BUCKET","<<MINIONS>>");

refresh_minions();

$trailing=trim($argv[1]);
$dest=trim($argv[2]);
$nick=trim($argv[3]);
$parts=explode(" ",$trailing);
if (count($parts)<2)
{
  return;
}
$valid_data_cmd=get_valid_data_cmd(False);
$cmd=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($cmd)
{
  case "new":
    if (nick_exists($trailing,$dest)==True)
    {
      privmsg("$trailing is already here");
      return;
    }
    $socket=fsockopen("ssl://irc.sylnt.us","6697");
    if ($socket===False)
    {
      term_echo("ERROR CREATING IRC SOCKET");
      return;
    }
    stream_set_blocking($socket,0);
    rawmsg("NICK $trailing");
    rawmsg("USER $trailing hostname servername :$trailing.bot");
    add_minion($trailing);
    while (True)
    {
      usleep(0.1e6);
      $data=get_bucket("MINION_CMD_$trailing");
      if ($data<>"")
      {
        term_echo($data);
        if (unset_bucket("MINION_CMD_$trailing")==True)
        {
          $items=parse_data($data);
          if ($items!==False)
          {
            rawmsg($data);
          }
        }
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
      if ($items["nick"]<>$trailing)
      {
        continue;
      }
      if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
      {
        dojoin($dest);
      }
      if ($items["cmd"]=="QUIT")
      {
        return;
      }
    }
    return;
  case "say":
    # dogfart join #soylent
    $bot_nick=$parts[0];
    array_shift($parts);
    if (count($parts)==0)
    {
      return;
    }
    $cmd=strtoupper($parts[0]);
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    $data="";
    switch ($cmd)
    {
      case "JOIN":
        $data=":$bot_nick $cmd $trailing";
        break;
      case "PART":
        $data=":$bot_nick $cmd $dest :$trailing";
        break;
      case "QUIT":
        $data=":$bot_nick $cmd :$trailing";
        break;
      case "NICK":
        $data=":$bot_nick $cmd :$trailing";
        break;
      case "PRIVMSG":
        $data=":$bot_nick $cmd $dest :$trailing";
        break;
    }
    if ($data=="")
    {
      term_echo("unknown cmd \"$cmd\"");
      return;
    }
    $items=parse_data($data);
    if ($items!==False)
    {
      term_echo("MINION_CMD_$bot_nick bucket set");
      set_bucket("MINION_CMD_$bot_nick",$data);
    }
    else
    {
      term_echo("invalid command \"$data\"");
    }
    return;
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

function refresh_minions()
{

}

#####################################################################################################

function add_minion($nick)
{

}

#####################################################################################################

?>
