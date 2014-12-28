<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~minion|0|0|0|1|crutchy|||<<MINIONS>>|php scripts/minion.php %%trailing%% %%dest%% %%nick%%
*/

#####################################################################################################

# ~bot privmsg dogfart test

/*
<crutchy> dogfartopoly join #soylent
<crutchy> dogfartopoly slap chromas
*/

ini_set("display_errors","on");

require_once("irc_lib.php");
require_once("lib_buckets.php");
require_once("users_lib.php");

define("BOT_BUCKET","<<MINIONS>>");

#refresh_minions();

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

$bot_nick=$parts[0];
array_shift($parts);
$trailing=trim(implode(" ",$parts));

$forward=False;

switch ($cmd)
{
  case "new":
    if (users_nick_exists($bot_nick,$dest)==True)
    {
      privmsg("$bot_nick is already here");
      return;
    }
    #$socket=fsockopen("ssl://irc.sylnt.us","6697");
    if (count($parts)==3)
    {
      $socket=fsockopen($parts[0],$parts[1]);
    }
    else
    {
      $socket=fsockopen("irc.sylnt.us","6667");
    }
    if ($socket===False)
    {
      term_echo("ERROR CREATING IRC SOCKET");
      return;
    }
    stream_set_blocking($socket,0);
    rawmsg("NICK $bot_nick");
    rawmsg("USER $bot_nick hostname servername :$bot_nick.bot");
    #add_minion($bot_nick);
    while (True)
    {
      usleep(0.1e6);
      $data=get_bucket("MINION_CMD_$bot_nick");
      if ($data<>"")
      {
        term_echo($data);
        if (unset_bucket("MINION_CMD_$bot_nick")==True)
        {
          $items=parse_data($data);
          if ($items!==False)
          {
            if ($items["cmd"]=="FORWARD")
            {
              $forward=$items["params"];
            }
            else
            {
              rawmsg($data);
            }
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
      term_echo($bot_nick." >> ".$data);
      if ($forward!==False)
      {
        echo "/IRC $data\n";
      }
      $items=parse_data($data);
      if ($items===False)
      {
        continue;
      }
      if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
      {
        if (isset($parts[2])==True)
        {
          term_echo("joining \"".$parts[2]."\"...");
          dojoin($parts[2]);
        }
        else
        {
          term_echo("joining \"$dest\"...");
          dojoin($dest);
          term_echo("joining \"#\"...");
          dojoin("#");
        }
      }
      if ($items["nick"]<>$bot_nick)
      {
        continue;
      }
      if ($items["cmd"]=="QUIT")
      {
        return;
      }
    }
    return;
  case "join":
    $data=":$bot_nick ".strtoupper($cmd)." $trailing";
    handle_bot_data($data,$bot_nick);
    break;
  case "part":
    $data=":$bot_nick ".strtoupper($cmd)." $dest :$trailing";
    handle_bot_data($data,$bot_nick);
    break;
  case "quit":
    $data=":$bot_nick ".strtoupper($cmd)." :$trailing";
    handle_bot_data($data,$bot_nick);
    break;
  case "nick":
    $data=":$bot_nick ".strtoupper($cmd)." :$trailing";
    handle_bot_data($data,$bot_nick);
    break;
  case "privmsg":
    $data=":$bot_nick ".strtoupper($cmd)." $dest :$trailing";
    handle_bot_data($data,$bot_nick);
    break;
  case "raw":
    handle_bot_data($trailing,$bot_nick);
    break;
  case "forward":
    $data="FORWARD $trailing";
    handle_bot_data($data,$bot_nick);
    break;
}

#####################################################################################################

function handle_bot_data($data,$bot_nick)
{
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
