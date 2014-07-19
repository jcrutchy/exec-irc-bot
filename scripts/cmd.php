<?php

# gpl2
# by crutchy
# 19-july-2014

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

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

switch (strtoupper($cmd))
{
  case "PONG":
    echo "/MSG :exec INTERNAL :~ping $params\n";
    break;
  case "INTERNAL":

    break;
  case "BUCKET_GET":

    break;
  case "BUCKET_SET":

    break;
  case "BUCKET_UNSET":

    break;
  case "INVITE": # :crutchy!~crutchy@709-27-2-01.cust.aussiebb.net INVITE exec :#~
    echo "/IRC JOIN $trailing\n";
    break;
  case "JOIN": # :exec!~exec@709-27-2-01.cust.aussiebb.net JOIN #
    echo "/INTERNAL ~users-internal JOIN $params\n";
    break;
  case "KICK": # :NCommander!~mcasadeva@Soylent/Staff/Sysop/mcasadevall KICK #staff exec :gravel test
    echo "/INTERNAL ~users-internal KICK $params\n";
    echo "/INTERNAL ~sed-internal KICK $params\n";
    break;
  case "KILL":

    break;
  case "MODE":
    #echo "/INTERNAL ~users-internal...
    break;
  case "NICK": # :Landon_!~Landon@Soylent/Staff/IRC/Landon NICK :Landon
    echo "/INTERNAL ~users-internal NICK $trailing\n";
    break;
  case "NOTICE":

    break;
  case "PART": # :Drop!~Drop___@via1-vhat2-0-3-jppz214.perr.cable.virginm.net PART #Soylent :Leaving
    echo "/INTERNAL ~users-internal PART $dest\n";
    echo "/INTERNAL ~sed-internal PART $dest\n";
    break;
  case "PRIVMSG":
    echo "/INTERNAL ~sed-internal PRIVMSG $trailing\n";
    break;
  case "QUIT":
    echo "/INTERNAL ~users-internal QUIT\n";
    break;
  case "043": # nickname was forced to change due to a collision

    break;
  case "263": # server dropped command without processing it

    break;
  case "303": # ison

    break;
  case "311": # :irc.sylnt.us 311 exec tme520 ~TME520 218-883-738-54.tpgi.com.au * :TME520

    break;
  case "319": # :irc.sylnt.us 319 exec crutchy :#wiki +#test #sublight #help @#exec #derp @#civ @#1 @#0 ## @#/ @#> @#~ @#
    echo "/INTERNAL ~users-internal 319 $params $trailing\n";
    break;
  case "330": # :irc.sylnt.us 330 exec crutchy crutchy :is logged in as
    echo "/INTERNAL ~users-internal 330 $params\n";
    break;
  case "353": # :irc.sylnt.us 353 exec = #civ :exec @crutchy chromas arti
    echo "/INTERNAL ~users-internal 353 $params $trailing\n";
    break;
  case "401": # :irc.sylnt.us 401 exec SedBot :No such nick/channel

    break;
  case "436": # server detected a nickname collision

    break;
  case "471": # attempting to join a channel which is set +l and is already full

    break;
  case "354": # :irc.sylnt.us 354 crutchy 152 #Soylent mrcoolbp H@+
    echo "/INTERNAL ~users-internal 354 $params\n";
    break;
  case "322": # :irc.sylnt.us 322 crutchy # 8 :exec's home base and proving ground. testing of other bots and general chit chat welcome :-)
    echo "/INTERNAL ~users-internal 322 $params\n";
    break;
}

#####################################################################################################

?>
