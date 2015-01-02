<?php

#####################################################################################################

function cmd_privmsg($client_index,$items)
{
  global $nicks;
  global $channels;
  # PRIVMSG #soylent :stuff
  $nick=client_nick($client_index);
  if ($nick===False)
  {
    return;
  }
  $chan=$items["params"];
  $trailing=$items["trailing"];
  if (isset($channels[$chan]["nicks"])==True)
  {
    $n=count($channels[$chan]["nicks"]);
    for ($i=0;$i<$n;$i++)
    {
      $index=$nicks[strtolower($nick)]["connection_index"];
      $prefix=$nicks[strtolower($nick)]["prefix"];
      $msg=":$prefix PRIVMSG $chan :$trailing";
      do_reply($index,$msg);
    }
  }
}

#####################################################################################################

?>
