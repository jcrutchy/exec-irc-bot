<?php

# gpl2
# by crutchy

/*
<< JOIN #stuff
>> :crutchy!~crutchy@709-27-2-01.cust.aussiebb.net JOIN #stuff
<< MODE #stuff
<< WHO #stuff
>> :irc.sylnt.us MODE #stuff +nt
>> :irc.sylnt.us 353 crutchy = #stuff :@crutchy
>> :irc.sylnt.us 366 crutchy #stuff :End of /NAMES list.
>> :irc.sylnt.us 324 crutchy #stuff +nt
>> :irc.sylnt.us 329 crutchy #stuff 1417818719
>> :irc.sylnt.us 352 crutchy #stuff ~crutchy 709-27-2-01.cust.aussiebb.net irc.sylnt.us crutchy H@ :0 crutchy
>> :irc.sylnt.us 315 crutchy #stuff :End of /WHO list.
*/

#####################################################################################################

function cmd_join(&$connections,&$nicks,&$channels,&$client,$items)
{
  $nick=client_nick($connections,$nicks,$client);
  if ($nick===False)
  {
    return;
  }
  $addr=$nicks[$nick]["connection"]["addr"];
  $chan=$items["params"];
  if (isset($channels[$chan])==False)
  {
    $channels[$chan]=array();
    $channels[$chan]["nicks"]=array();
  }
  $channels[$chan]["nicks"][]=$nick;
  echo "*** JOIN MESSAGE RECEIVED FROM $addr\n";
}

#####################################################################################################

?>
