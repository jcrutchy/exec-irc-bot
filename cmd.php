<?php

# gpl2
# by crutchy
# 25-april-2014

define("PRIVMSG_CHAN","#~");
define("NICK","exec");

ini_set("display_errors","on");

$cmd=$argv[1];
$trailing=$argv[2];
$data=$argv[3];
$dest=$argv[4];
$params=$argv[5];
$nick=$argv[6];

/*
:irc.sylnt.us 311 exec crutchy ~crutchy_ 724-640-25-593.cust.aussiebb.net * :crutchy
:irc.sylnt.us 319 exec crutchy :@#~ #Soylent +#test #sublight 
:irc.sylnt.us 312 exec crutchy irc.sylnt.us :It's all about the people!
:irc.sylnt.us 317 exec crutchy 1 1397864053 :seconds idle, signon time
:irc.sylnt.us 330 exec crutchy crutchy :is logged in as
:irc.sylnt.us 318 exec crutchy :End of /WHOIS list.

:crutchy!~crutchy_@724-640-25-593.cust.aussiebb.net PART #Soylent :Ex-Chat
:crutchy!~crutchy_@724-640-25-593.cust.aussiebb.net JOIN #Soylent
*/

switch ($cmd)
{
  case "330": # is logged in as
    $parts=explode(" ",$params);
    if ((count($parts)==3) and ($parts[0]==NICK))
    {
      $nick=$parts[1];
      $account=$parts[2];
      echo ":exec NOTICE ".PRIVMSG_CHAN." :civ login $nick $account\n";
    }
    break;
  case "JOIN":
    echo "IRC_RAW WHOIS $nick\n";
    break;
  case "PART":
    echo ":exec NOTICE ".PRIVMSG_CHAN." :civ logout $nick\n";
    break;
  case "PRIVMSG":
    break;
  case "NOTICE":
    break;
}

?>
