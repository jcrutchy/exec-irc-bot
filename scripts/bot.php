<?php

# gpl2
# by crutchy
# 7-sep-2014

#####################################################################################################

# ~bot say dogfart :dogfart PRIVMSG # :test

/*
<crutchy> dogfartopoly join #soylent
<crutchy> dogfartopoly slap chromas
*/

ini_set("display_errors","on");

require_once("irc_lib.php");

define("BOT_LIST_BUCKET","<<MINIONS>>");

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
    # if $trailing not found by lib_users.php function, unset from <<MINIONS>> bucket
    /*$bots=get_array_bucket("<<MINIONS>>");
    if (in_array($trailing,$bots)==True)
    {
      echo "/PRIVMSG $nick: minion \"$trailing\" is already registered";
      return;
    }
    unset($bots);
    append_array_bucket("<<MINIONS>>",$trailing);*/
    $socket=fsockopen("ssl://irc.sylnt.us","6697");
    if ($socket===False)
    {
      term_echo("ERROR CREATING IRC SOCKET");
      return;
    }
    stream_set_blocking($socket,0);
    rawmsg("NICK $trailing");
    rawmsg("USER $trailing hostname servername :$trailing.bot");
    while (True)
    {
      usleep(0.1e6);
      $data=get_bucket("MINION_CMD_$trailing");
      if ($data<>"")
      {
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
      if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
      {
        dojoin("#");
      }
    }
    return;
  case "say":
    $bot_nick=$parts[0];
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    $items=parse_data($trailing);
    if ($items!==False)
    {
      set_bucket("MINION_CMD_$bot_nick",$trailing);
    }
    else
    {
      echo "/PRIVMSG $nick: invalid command \"$trailing\" for $bot_nick";
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

?>
