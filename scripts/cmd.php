<?php

# gpl2
# by crutchy
# 7-june-2014

#####################################################################################################

define("NICK_SEDBOT","SedBot");

ini_set("display_errors","on");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

require_once("lib.php");

switch ($cmd)
{
  case "330": # is logged in as
    $parts=explode(" ",$params);
    if ((count($parts)==3) and ($parts[0]==NICK_EXEC))
    {
      $nick=$parts[1];
      $account=$parts[2];
      if ($nick<>NICK_EXEC)
      {
        $player_channel_list=explode(" ",get_bucket($nick."_channel_list"));
        $irciv_game_chans=unserialize(get_bucket("IRCIV_GAME_CHANNELS"));
        for ($i=0;$i<count($irciv_game_chans);$i++)
        {
          if (in_array($irciv_game_chans[$i],$player_channel_list)==True)
          {
            echo "NOTICE :~civ login $nick $account\n";
            break;
          }
        }
      }
    }
    break;
  case "353": # channel names list
    # :irc.sylnt.us 353 exec = #civ :exec @crutchy arti monopoly chromas Loggie
    $parts=explode("=",$params);
    if (count($parts)==2)
    {
      if (trim($parts[0])==NICK_EXEC)
      {
        $irciv_game_chans=unserialize(get_bucket("IRCIV_GAME_CHANNELS"));
        if (in_array(trim($parts[1]),$irciv_game_chans)==True)
        {
          $names=explode(" ",$trailing);
          for ($i=0;$i<count($names);$i++)
          {
            $name=$names[$i];
            if ((substr($name,0,1)=="+") or (substr($name,0,1)=="@"))
            {
              $name=substr($name,1);
            }
            if ($name==NICK_EXEC)
            {
              continue;
            }
            echo "IRC_RAW WHOIS $name\n";
            sleep(1);
          }
        }
      }
    }
    break;
  case "JOIN":
    if ($nick==NICK_EXEC)
    {
      $irciv_game_chans=unserialize(get_bucket("IRCIV_GAME_CHANNELS"));
      for ($i=0;$i<count($irciv_game_chans);$i++)
      {
        echo ":".NICK_EXEC." NOTICE ".$irciv_game_chans[$i]." :~civ-map generate\n";
      }
    }
    else
    {
      echo "IRC_RAW WHOIS $nick\n";
    }
    break;
  case "KILL":
  case "KICK":
  case "QUIT":
  case "PART":
    echo "NOTICE :~civ logout $nick\n";
    break;
  #case "043": # Sent to the client when their nickname was forced to change due to a collision
  #case "436": # Returned by a server to a client when it detects a nickname collision
  case "NICK":
    echo "NOTICE :~civ rename $nick $trailing\n";
    break;
  case "PRIVMSG":
    echo ":$nick NOTICE $dest :~AUJ73HF839CHH2933HRJPA8N2H $trailing\n"; # sed.php
    echo ":$nick NOTICE $dest :~HDIN48SH2M6H0XY4BJB4Y8XGF4 $trailing\n"; # bucket_vars.php
    #echo ":$nick NOTICE $dest :~JRB8D93MSCRQ92E4M1LE9BCX89 $trailing\n"; # grab.php
    #echo ":$nick NOTICE $dest :~TXVHG62M7CGR4K9SC5H6R1S29G $trailing\n"; # funnel.php
    break;
  case "NOTICE":
    break;
  case "MODE":
    break;
  case "PING":
    break;
  case "263": # When a server drops a command without processing it, it MUST use this reply.
    break;
  case "471": # Returned when attempting to join a channel which is set +l and is already full
    break;
  case "404":
    break;
  case "311":
    #:irc.sylnt.us 311 exec tme520 ~TME520 218-883-738-54.tpgi.com.au * :TME520
    break;
  case "319":
    #:irc.sylnt.us 319 exec crutchy :#wiki +#test #sublight #help @#exec #derp @#civ @#1 @#0 ## @#/ @#> @#~ @#
    $parts=explode(" ",$params);
    if (count($parts)==2)
    {
      set_bucket($parts[1]."_channel_list",$trailing);
    }
    break;
  case "401": # No such nick/channel
    $parts=explode(" ",$params);
    if (count($parts)==2)
    {
      if ($parts[1]==NICK_SEDBOT)
      {
        unset_bucket(NICK_SEDBOT."_channel_list");
      }
    }
    break;
}

#####################################################################################################

?>
