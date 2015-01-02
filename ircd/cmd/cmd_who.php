<?php

# http://www.mircscripts.org/downloads/who.%5B07-08%5Dhtml

#####################################################################################################

function cmd_who($client_index,$items)
{
  global $nicks;
  global $channels;
  # WHO #stuff
  $nick=client_nick($client_index);
  if ($nick===False)
  {
    return;
  }
  $chan=$items["params"];
  if (isset($channels[$chan]["nicks"])==True)
  {
    $n=count($channels[$chan]["nicks"]);
    for ($i=0;$i<$n;$i++)
    {
      # :irc.sylnt.us 352 crutchy #stuff ~crutchy 709-27-2-01.cust.aussiebb.net irc.sylnt.us crutchy H@ :0 crutchy
      $chan_nick=$channels[$chan]["nicks"][$i];
      $username=$nicks[strtolower($chan_nick)]["username"];
      $hostname=$nicks[strtolower($chan_nick)]["hostname"];
      $ident_prefix=$nicks[strtolower($chan_nick)]["connection"]["ident_prefix"];
      $realname=$nicks[strtolower($chan_nick)]["realname"];
      $msg=":".SERVER_HOSTNAME." 352 $nick $chan $ident_prefix"."$username $hostname ".SERVER_HOSTNAME." $chan_nick H@ :0 $realname";
      do_reply($client_index,$msg);
    }
  }
  # :irc.sylnt.us 315 crutchy #stuff :End of /WHO list.
  $msg=":".SERVER_HOSTNAME." 315 $nick $chan :End of /WHO list.";
  do_reply($client_index,$msg);
}

#####################################################################################################

?>
