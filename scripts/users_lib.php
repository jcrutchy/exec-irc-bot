<?php

# gpl2
# by crutchy
# 18-sep-2014

#####################################################################################################

function nick_exists($nick,$channel)
{

}

#####################################################################################################

function list_nicks($channel)
{

}

#####################################################################################################

function list_channels($nick)
{

}

#####################################################################################################

function count_nicks($channel)
{

}

#####################################################################################################

function handle_353($trailing) # <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
{
  term_echo($trailing);
  $parts=explode(" ",$trailing);
  if (count($parts)<4)
  {
    term_echo("*** USERS: handle_353: invalid number of parts");
    return;
  }
  $channel=$parts[2];
  if ($channel=="")
  {
    term_echo("*** USERS: handle_353: empty channel");
    return;
  }
  for ($i=3;$i<count($parts);$i++)
  {
    $nick=$parts[$i];
    if ($nick=="")
    {
      term_echo("*** USERS: handle_353: empty nick");
      continue;
    }
    $auth=$nick[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $nick=substr($nick,1);
      if ($nick=="")
      {
        term_echo("*** USERS: handle_353: empty auth nick");
        continue;
      }
    }
    # TODO
  }
}

#####################################################################################################

function handle_330($trailing) # <calling_nick> <subject_nick> <account>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>3)
  {
    term_echo("*** USERS: handle_330: invalid number of parts");
    return;
  }
  $subject_nick=$parts[1];
  if ($subject_nick=="")
  {
    term_echo("*** USERS: handle_330: empty subject_nick");
    return;
  }
  $account=$parts[2];
  if ($account=="")
  {
    term_echo("*** USERS: handle_330: empty account");
    return;
  }
  # TODO
}

#####################################################################################################

function handle_319($trailing) # <calling_nick> <subject_nick> <chan1> <+chan2> <@chan3>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<3)
  {
    term_echo("*** USERS: handle_319: invalid number of parts");
    return;
  }
  $subject_nick=$parts[1];
  if ($subject_nick=="")
  {
    term_echo("*** USERS: handle_319: empty subject_nick");
    return;
  }
  for ($i=2;$i<count($parts);$i++)
  {
    $channel=$parts[$i];
    if ($channel=="")
    {
      term_echo("*** USERS: handle_319: empty channel");
      continue;
    }
    $auth=$channel[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $channel=substr($channel,1);
      if ($channel=="")
      {
        term_echo("*** USERS: handle_319: empty auth channel");
        continue;
      }
    }
    term_echo("*** USERS: handle_319: channel = $channel");
    # TODO
  }
}

#####################################################################################################

function handle_join($nick,$channel)
{
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_join: empty nick or channel");
    return;
  }
  term_echo("*** USERS: handle_join: nick = $nick");
  term_echo("*** USERS: handle_join: channel = $channel");
  # TODO
}

#####################################################################################################

function handle_kick($trailing) # <channel> <kicked_nick>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>2)
  {
    term_echo("*** USERS: handle_kick: invalid number of parts");
    return;
  }
  $channel=$parts[0];
  $kicked_nick=$parts[1];
  if (($channel=="") or ($kicked_nick==""))
  {
    term_echo("*** USERS: handle_kick: empty channel or kicked nick");
    return;
  }
  term_echo("*** USERS: handle_kick: channel = $channel");
  term_echo("*** USERS: handle_kick: kicked_nick = $kicked_nick");
  # TODO
}

#####################################################################################################

function handle_nick($old_nick,$new_nick)
{
  if (($old_nick=="") or ($new_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_nick: old_nick = $old_nick");
  term_echo("*** USERS: handle_nick: new_nick = $new_nick");
  # TODO
}

#####################################################################################################

function handle_part($nick,$channel)
{
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_part: empty channel or nick");
    return;
  }
  term_echo("*** USERS: handle_part: nick = $nick");
  term_echo("*** USERS: handle_part: channel = $channel");
  # TODO
}

#####################################################################################################

function handle_quit($nick)
{
  if ($nick=="")
  {
    term_echo("*** USERS: handle_quit: empty nick");
    return;
  }
  term_echo("*** USERS: handle_quit: nick = $nick");
  # TODO
}

#####################################################################################################

?>
