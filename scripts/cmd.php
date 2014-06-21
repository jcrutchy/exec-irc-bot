<?php

# gpl2
# by crutchy
# 21-june-2014

#####################################################################################################

define("NICK_SEDBOT","SedBot");
define("NICK_BENDER","Bender");
define("ACCOUNT_BENDER","deadpork");

ini_set("display_errors","on");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];
$alias=$argv[7];

require_once("irciv_lib.php"); # also requires lib.php

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
        if ($account==ACCOUNT_BENDER)
        {
          set_bucket("BENDER_LAST_FEED_MESSAGE_VERIFIED",get_bucket("BENDER_LAST_FEED_MESSAGE"));
          break;
        }
        $player_channel_list=explode(" ",get_bucket($nick."_channel_list"));
        for ($i=0;$i<count($game_chans);$i++)
        {
          if (in_array($game_chans[$i],$player_channel_list)==True)
          {
            echo ":".NICK_EXEC." NOTICE :~civ login $nick $account\n";
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
      $chans=get_bucket(NICK_SEDBOT."_channel_list");
      if ($chans=="")
      {
        echo "IRC_RAW WHOIS ".NICK_SEDBOT."\n";
        sleep(1);
      }
      if (trim($parts[0])==NICK_EXEC)
      {
        if (in_array(trim($parts[1]),$game_chans)==True)
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
  case "JOIN": # :SedBot!~SedBot@github.com/FoobarBazbot/sedbot JOIN #Soylent
    if ($nick==NICK_EXEC)
    {

      #$chans=get_bucket(NICK_EXEC."_channel_list"); # TODO

      if (in_array($trailing,$game_chans)==True)
      {
        echo ":".NICK_EXEC." NOTICE $trailing :~civ-map generate\n";
      }
    }
    elseif ($nick==NICK_SEDBOT)
    {
      echo "IRC_RAW WHOIS $nick\n";
    }
    else
    {
      # do a whois if $dest is a game channel
      if (in_array($dest,$game_chans)==True)
      {
        echo "IRC_RAW WHOIS $nick\n";
      }
    }
    break;
  case "KILL":
  case "KICK":
  case "QUIT": # :SedBot!~SedBot@github.com/FoobarBazbot/sedbot QUIT :Ping timeout: 240 seconds
  case "PART": # :crutchy!~crutchy@709-27-2-01.cust.aussiebb.net PART #Soylent :Leaving
    if ($nick==NICK_SEDBOT)
    {
      $chans=get_bucket(NICK_SEDBOT."_channel_list");
      unset_bucket(NICK_SEDBOT."_channel_list");
      # privmsg sedbot channels that exec sed is being enabled
      $chans=explode(" ",$chans);
      for ($i=0;$i<count($chans);$i++)
      {
        echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$chans[$i]." :exec sed enabled\n";
      }
    }
    elseif ($nick<>NICK_EXEC)
    {
      if ($dest<>"")
      {
        if (in_array($dest,$game_chans)==True)
        {
          echo ":".NICK_EXEC." NOTICE :~civ logout $nick\n";
        }
      }
      else
      {
        $player_channel_list=explode(" ",get_bucket($nick."_channel_list"));
        for ($i=0;$i<count($game_chans);$i++)
        {
          if (in_array($game_chans[$i],$player_channel_list)==True)
          {
            echo ":".NICK_EXEC." NOTICE :~civ logout $nick\n";
            break;
          }
        }
      }
    }
    sleep(3);
    unset_bucket($nick."_channel_list");
    break;
  #case "043": # Sent to the client when their nickname was forced to change due to a collision
  #case "436": # Returned by a server to a client when it detects a nickname collision
  case "NICK": # :juggs!~juggs@Soylent/Users/63/Juggs NICK :juggs|afk
    $chans=get_bucket($nick."_channel_list");
    $player_channel_list=explode(" ",$chans);
    for ($i=0;$i<count($game_chans);$i++)
    {
      if (in_array($game_chans[$i],$player_channel_list)==True)
      {
        echo ":".NICK_EXEC." NOTICE :~civ rename $nick $trailing\n";
        break;
      }
    }
    unset_bucket($nick."_channel_list");
    set_bucket($trailing."_channel_list",$chans);
    break;
  case "PRIVMSG":
    echo ":$nick NOTICE $dest :~sed_6705140699 $trailing\n";
    #echo ":$nick NOTICE $dest :~stats_6423280149 $trailing\n";
    #echo ":$nick NOTICE $dest :~bucket_vars_4540691864 $trailing\n";
    #echo ":$nick NOTICE $dest :~grab_9103124086 $trailing\n";
    #echo ":$nick NOTICE $dest :~funnel_0341209204 $trailing\n";
    if ($nick==NICK_BENDER)
    {
      $delim="[SoylentNews] - ";
      if (substr($trailing,0,strlen($delim))==$delim)
      {
        set_bucket("BENDER_LAST_FEED_MESSAGE",$trailing);
        echo "IRC_RAW WHOIS $nick\n";
      }
    }
    break;
  case "NOTICE":
    break;
  case "MODE":
    break;
  case "INVITE": # :crutchy!~crutchy@709-27-2-01.cust.aussiebb.net INVITE exec :#0
    echo "IRC_RAW JOIN $trailing\n";
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
      $chans=explode(" ",$trailing);
      for ($i=0;$i<count($chans);$i++)
      {
        if ((substr($chans[$i],0,1)=="+") or (substr($chans[$i],0,1)=="@"))
        {
          $chans[$i]=substr($chans[$i],1);
        }
      }
      $chan_list=implode(" ",$chans);
      set_bucket($parts[1]."_channel_list",$chan_list);
      if ($parts[1]==NICK_SEDBOT)
      {
        # privmsg sedbot channels that exec sed is being disabled
        for ($i=0;$i<count($chans);$i++)
        {
          echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$chans[$i]." :exec sed disabled\n";
        }
      }
    }
    break;
  case "401": # No such nick/channel
    break;
}

#####################################################################################################

?>
