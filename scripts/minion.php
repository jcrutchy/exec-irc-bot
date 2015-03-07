<?php

#####################################################################################################

/*
exec:~minion|0|0|0|1|crutchy|||<<MINIONS>>|php scripts/minion.php %%trailing%% %%dest%% %%nick%% %%alias%%
exec:~freenode|0|0|0|1|crutchy||||php scripts/minion.php %%trailing%% %%dest%% %%nick%% %%alias%%
startup:~join #freenode
*/

#####################################################################################################

/*
~minion quit sylnt
~minion new sylnt irc.freenode.net 6667 #sylnt
~minion forward sylnt #freenode
~minion join sylnt #devuan,#devuan-dev,#IRCiv,#epoch
~minion raw sylnt :sylnt PART #debian
*/

ini_set("display_errors","on");

require_once("irc_lib.php");
require_once("lib_buckets.php");
require_once("users_lib.php");

define("BOT_BUCKET","<<MINIONS>>");
define("IRC_HOST","irc.sylnt.us");

#refresh_minions();

$trailing=trim($argv[1]);
$dest=trim($argv[2]);
$nick=trim($argv[3]);
$alias=trim($argv[4]);

if ($alias=="~freenode")
{
  echo "/IRC :exec INTERNAL :~join #freenode\n";
  echo "/IRC :exec INTERNAL :~minion new sylnt irc.freenode.net 6667 #sylnt\n";
  sleep(30);
  echo "/IRC :exec INTERNAL :~minion join sylnt #sylnt,##exec,#devuan,#devuan-dev\n";
  sleep(5);
  echo "/IRC :exec INTERNAL :~minion forward sylnt #freenode\n";
}

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
      $server=$parts[0];
      $port=$parts[1];
    }
    else
    {
      $server="irc.sylnt.us";
      $port="6667";
    }
    $socket=fsockopen($server,$port);
    if ($socket===False)
    {
      term_echo("ERROR CREATING IRC SOCKET");
      return;
    }
    stream_set_blocking($socket,0);
    rawmsg("NICK $bot_nick");
    rawmsg("USER $bot_nick 0host 0server :$bot_nick.bot");
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
            rawmsg($data);
          }
          else
          {
            $tokens=explode(" ",$data);
            if ((count($tokens)==2) and (strtoupper($tokens[0])=="FORWARD"))
            {
              $forward=$tokens[1];
              term_echo("*** FORWARD SET: ALL DATA WILL BE FORWARDED TO $forward ON EXEC BOT HOST NETWORK");
            }
            unset($tokens);
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
      if (($items["cmd"]=="QUIT") and ($items["nick"]==$bot_nick))
      {
        term_echo("*** QUITTING $bot_nick CLIENT SCRIPT");
        return;
      }
      if ($forward!==False)
      {
        switch ($items["cmd"])
        {
          #case "JOIN":
          #case "PART":
          #case "QUIT":
          #case "NICK":
          #case "KICK":
          #case "TOPIC":
          case "PRIVMSG":
          #case "MODE":
          #case "NOTICE":
            $msg="PRIVMSG $forward :*** $bot_nick@$server >> ".chr(3)."02".$items["nick"]." ".chr(3)."03".$items["cmd"];
            if ($items["params"]<>"")
            {
              $msg=$msg." ".chr(3)."04".$items["params"];
            }
            if ($items["trailing"]<>"")
            {
              $msg=$msg.chr(3)." :".chr(3)."05".$items["trailing"];
            }
            echo "/IRC $msg\n";
            break;
        }
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
    if (count($parts)>1)
    {
      $dest=$parts[0];
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
      $data=":$bot_nick ".strtoupper($cmd)." $dest :$trailing";
      handle_bot_data($data,$bot_nick);
    }
    break;
  case "raw":
    handle_bot_data($trailing,$bot_nick);
    break;
  case "forward":
    $data="FORWARD $trailing";
    set_bucket("MINION_CMD_$bot_nick","FORWARD $trailing");
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
