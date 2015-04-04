<?php

#####################################################################################################
#                                                                                                   #
#                                          ~~~ CAUTION ~~~                                          #
#                                                                                                   #
#   THIS SCRIPT IS CALLED ON EVERY IRC EVENT, INCLUDING THOSE ORIGINATING FROM THIS SCRIPT.         #
#                                                                                                   #
#   INFINITE LOOPS CAN RESULT FROM:                                                                 #
#                                                                                                   #
#     (1) ECHOING A COMAND IN IT'S OWN HANDLER                                                      #
#     (2) ECHOING A COMMAND WHOSE HANDLER ECHOES THE ORIGINAL COMMAND                               #
#                                                                                                   #
#####################################################################################################

require_once("lib.php");

$items=unserialize(base64_decode($argv[1]));

$cmd=$items["cmd"];
$data=$items["data"];
$trailing=$items["trailing"];
$dest=$items["destination"];
$params=$items["params"];
$nick=$items["nick"];

switch (strtoupper($cmd))
{
  case "PONG":
    #echo "/INTERNAL ~pong $trailing\n";
    break;
  case "INTERNAL":
    break;
  case "BUCKET_GET":
    break;
  case "BUCKET_SET":
    break;
  case "BUCKET_UNSET":
    break;
  case "INVITE":
    # :crutchy!~crutchy@709-27-2-01.cust.aussiebb.net INVITE exec :#~
    echo "/IRC JOIN $trailing\n";
    break;
  case "JOIN":
    # :exec!~exec@709-27-2-01.cust.aussiebb.net JOIN #
    #echo "/INTERNAL ~welcome-internal JOIN $params\n";
    break;
  case "KICK":
    # :NCommander!~mcasadeva@Soylent/Staff/Sysop/mcasadevall KICK #staff exec :gravel test
    # :exec!~exec@709-27-2-01.cust.aussiebb.net KICK #comments Loggie :commanded by crutchy
    echo "/INTERNAL ~sed-internal KICK $params\n";
    #echo "/INTERNAL ~welcome-internal KICK $params\n";
    break;
  case "KILL":
    # :juggs!~juggs@Soylent/Staff/IRC/juggs KILL dogfart :crutchy_made_me
    # :dogfart!~dogfart@709-27-2-01.cust.aussiebb.net QUIT :Killed (juggs (crutchy_made_me))
    break;
  case "MODE":
    break;
  case "NICK":
    # :Landon_!~Landon@Soylent/Staff/IRC/Landon NICK :Landon
    break;
  case "NOTICE":
    break;
  case "PART":
    # :Drop!~Drop___@via1-vhat2-0-3-jppz214.perr.cable.virginm.net PART #Soylent :Leaving
    echo "/INTERNAL ~sed-internal PART $dest\n";
    #echo "/INTERNAL ~welcome-internal PART $dest\n";
    break;
  case "PRIVMSG":
    echo "/INTERNAL ~sed-internal PRIVMSG $trailing\n";
    #echo "/INTERNAL ~antispam ".$argv[1]."\n";
    if ($trailing=="💩")
    {
      pm_action($dest,"chucks a nasty sloppy dogshit at aqu4");
    }
    break;
  case "QUIT":
    break;
  case "043":
    # nickname was forced to change due to a collision
    break;
  case "263":
    # server dropped command without processing it
    break;
  case "303":
    # ison
    break;
  case "311":
    # :irc.sylnt.us 311 exec tme520 ~TME520 218-883-738-54.tpgi.com.au * :TME520
    break;
  case "319":
    # :irc.sylnt.us 319 exec crutchy :#wiki +#test #sublight #help @#exec #derp @#civ @#1 @#0 ## @#/ @#> @#~ @#
    break;
  case "330":
    # :irc.sylnt.us 330 exec crutchy_ crutchy :is logged in as
    break;
  case "353":
    # :irc.sylnt.us 353 exec = #civ :exec @crutchy chromas arti
    break;
  case "401":
    # :irc.sylnt.us 401 exec SedBot :No such nick/channel
    break;
  case "436":
    # server detected a nickname collision
    break;
  case "471":
    # attempting to join a channel which is set +l and is already full
    break;
  case "318":
    # :irc.sylnt.us 318 crutchy crutchy :End of /WHOIS list.
    break;
  case "315":
    # :irc.sylnt.us 315 crutchy #Soylent :End of /WHO list.
    break;
  case "354":
    # :irc.sylnt.us 354 crutchy 152 #Soylent mrcoolbp H@+
    break;
  case "322":
    # :irc.sylnt.us 322 crutchy # 8 :exec's home base and proving ground. testing of other bots and general chit chat welcome :-)
    break;
}

handle_macros($nick,$dest,$trailing);

#####################################################################################################

function handle_macros($nick,$channel,$trailing)
{
  if (($nick=="") or ($channel=="") or ($trailing==""))
  {
    return;
  }
  if ($trailing==".macro")
  {
    pm($channel,chr(3)."02"."  syntax to add: .macro <trigger> <chanlist> <command_template>");
    pm($channel,chr(3)."02"."  syntax to delete: .macro <trigger> -");
    pm($channel,chr(3)."02"."  <chanlist> is comma-separated or * for any");
  }
  $macro_file=DATA_PATH."exec_macros.txt";
  $macros=load_settings($macro_file,"=");
  if ($macros===False)
  {
    $macros=array();
  }
  $parts=explode(" ",$trailing);
  delete_empty_elements($parts);
  if (count($parts)==0)
  {
    return;
  }
  if ((strtolower(trim($parts[0]))==".macro") and (count($parts)>2))
  {
    $account=users_get_account($nick);
    $allowed=array("crutchy","chromas");
    if (in_array($account,$allowed)==False)
    {
      return;
    }
    $trigger=trim($parts[1]);
    $reserved_triggers=array(".macro");
    if (in_array($trigger,$reserved_triggers)==True)
    {
      privmsg(chr(3)."02"."  *** macro with trigger \"$trigger\" not permitted");
      return;
    }
    $chanlist=trim($parts[2]);
    if ($chanlist=="-")
    {
      unset($macros[$trigger]);
      privmsg(chr(3)."02"."  *** macro with trigger \"$trigger\" deleted");
    }
    elseif (count($parts)>=5)
    {
      array_shift($parts);
      array_shift($parts);
      array_shift($parts);
      $cmd=strtoupper(trim($parts[0]));
      if (($cmd<>"PRIVMSG") and ($cmd<>"INTERNAL"))
      {
        privmsg(chr(3)."02"."  *** error: invalid cmd (must be either INTERNAL or PRIVMSG)");
        return;
      }
      array_shift($parts);
      $command=implode(" ",$parts);
      $reserved_commands=array("~eval");
      for ($i=0;$i<count($reserved_commands);$i++)
      {
        if (strtolower(substr($command,0,strlen($reserved_commands[$i])))==strtolower($reserved_commands[$i]))
        {
          privmsg(chr(3)."02"."  *** macro with command \"$command\" not permitted");
          return;
        }
      }
      $data=array();
      $data["chanlist"]=$chanlist;
      $data["command"]=$command;
      $data["cmd"]=$cmd;
      $macros[$trigger]=serialize($data);
      privmsg(chr(3)."02"."  *** macro with trigger \"$trigger\" and $cmd command template \"$command\" saved");
    }
    save_settings($macros,$macro_file,"=");
  }
  else
  {
    foreach ($macros as $trigger => $data)
    {
      if (substr($trailing,0,strlen($trigger))==$trigger)
      {
        $data=unserialize($data);
        if (($data["chanlist"]=="*") or (in_array(strtolower($channel),explode(",",strtolower($data["chanlist"])))==True))
        {
          $account=users_get_account($nick);
          if ($account<>"")
          {
            $cmd="INTERNAL";
            if (isset($data["cmd"])==True)
            {
              $cmd=$data["cmd"];
            }
            $trailing=substr($trailing,strlen($trigger));
            # TODO: MAKE MORE TRAILING PARSING REPLACE ARGS
            $command=str_replace("%%channel%%",$channel,$data["command"]);
            $command=str_replace("%%nick%%",$nick,$command);
            $command=str_replace("%%trailing%%",$trailing,$command);
            echo "/IRC :".NICK_EXEC." $cmd $channel :$command\n";
          }
        }
        return;
      }
    }
  }
}

#####################################################################################################

?>
