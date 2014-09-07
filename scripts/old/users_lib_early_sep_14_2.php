<?php

# gpl2
# by crutchy
# 7-sep-2014

#####################################################################################################

require_once("lib.php");

#####################################################################################################

function channel_nicks($channel)
{
  $db=get_array_bucket("<<NICK_DATABASE>>");
  if (count($db)==0)
  {
    term_echo("*** USERS: channel_nicks: no nicks in database");
    return;
  }
  $results=array();
  foreach ($db as $nick => $data)
  {
    if (in_array($channel,$data["channels"])==True)
    {
      $results[]=$nick;
    }
  }
  if (count($results)==0)
  {
    term_echo("*** USERS: channel_nicks: no nicks registered in channel $channel");
    return;
  }

  /*$buckets=bucket_list();
  $buckets=explode(" ",$buckets);
  $nicks=array();
  $prefix="<<EXEC_USER_";
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,strlen($prefix))==$prefix)
    {
      $nicks[]=substr($buckets[$i],strlen($prefix),strlen($buckets[$i])-strlen($prefix)-2);
    }
  }

  $result=array();
  for ($i=0;$i<count($nicks);$i++)
  {
    $nick=$nicks[$i];
    $index="<<EXEC_USER_$nick>>";
    $user=get_array_bucket($index);
    if (isset($user["channels"])==True)
    {
      if (in_array($channel,$user["channels"])==True)
      {
        $results[]=$nick;
      }
    }
  }*/

  $result=array();
  for ($i=0;$i<count($nicks);$i++)
  {
    $nick=$nicks[$i];
    if (isset($user["channels"])==True)
    {
      if (in_array($channel,$user["channels"])==True)
      {
        $results[]=$nick;
      }
    }
  }

  sort($results);
  privmsg(implode(" ",$results));
}

#####################################################################################################

function handle_353($trailing) # <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
{
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
  for ($i=1;$i<=3;$i++)
  {
    array_shift($parts);
  }
  $nicks=array();
  for ($i=0;$i<count($parts);$i++)
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
    term_echo("*** USERS: handle_353: nick = $nick");
    $nicks[]=$nick;
  }
  $db=get_array_bucket("<<NICK_DATABASE>>");
  for ($i=0;$i<count($nicks);$i++)
  {
    $nick=$nicks[$i];
    if (isset($db[$nick])==True)
    {
      if (isset($db[$nick]["channels"])==False)
      {
        $db[$nick]["channels"]=array();
      }
      if (in_array($channel,$db[$nick]["channels"])==False)
      {
        $db[$nick]["channels"][]=$channel;
      }
    }
    else
    {
      $db[$nick]["channels"]=array();
      $db[$nick]["channels"][]=$channel;
    }
  }
  set_array_bucket($db,"<<NICK_DATABASE>>",False);
  /*for ($i=0;$i<count($nicks);$i++)
  {
    $nick=$nicks[$i];
    $index="<<EXEC_USER_$nick>>";
    $user=get_array_bucket($index);
    if (isset($user["channels"])==False)
    {
      $user["channels"]=array();
    }
    if (in_array($channel,$user["channels"])==False)
    {
      $user["channels"][]=$channel;
    }
    set_array_bucket($user,$index,False);
  }*/
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
}

#####################################################################################################

?>
