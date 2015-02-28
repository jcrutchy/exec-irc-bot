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

require_once("lib_mysql.php");

$items=unserialize(base64_decode($argv[1]));

$cmd=$items["cmd"];
$data=$items["data"];
$trailing=$items["trailing"];
$dest=$items["destination"];
$params=$items["params"];
$nick=$items["nick"];

log_mysql($items);

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
    echo "/INTERNAL ~antispam ".$argv[1]."\n";
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

#####################################################################################################

?>
