<?php

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

function cmd_join($client_index,$items)
{
  global $nicks;
  global $channels;
  $nick=client_nick($client_index);
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
  $prefix=$nicks[strtolower($nick)]["prefix"];
  $msg=":".$prefix." JOIN ".$chan;
  #$msg="*** JOIN MESSAGE RECEIVED FROM $addr";
  #do_reply($client_index,$msg);
  broadcast($msg);
  $msg=":".SERVER_HOSTNAME." 353 $nick = $chan :".implode(" ",$channels[$chan]["nicks"]);
  do_reply($client_index,$msg);
  $msg=":".SERVER_HOSTNAME." 366 $nick $chan :End of /NAMES list.";
  do_reply($client_index,$msg);
  $msg=":".SERVER_HOSTNAME." 324 $nick $chan +nt";
  do_reply($client_index,$msg);
  $msg=":".SERVER_HOSTNAME." 329 $nick $chan ".time();
  do_reply($client_index,$msg);
}

#####################################################################################################

?>
