<?php

# gpl2
# by crutchy
# 10-sep-2014

#####################################################################################################

function user_bucket_index($nick)
{
  return "<<EXEC_USER_$nick>>";
}

#####################################################################################################

function nick_exists($nick,$channel)
{
  $bucket=get_array_bucket(CHANNEL_BUCKET_PREFIX.strtolower($channel));
  if (in_array(strtolower($nick),$bucket)==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function list_nicks($channel)
{
  $buckets=bucket_list();
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
    $index=user_bucket_index($nicks[$i]);
    $user=get_array_bucket($index);
    if (isset($user["channels"][$channel])==True)
    {
      $result[]=$nicks[$i];
    }
  }
  sort($result);
  privmsg("nicks in $channel: ".implode(" ",$result));
}

#####################################################################################################

function list_channels($nick)
{
  $buckets=bucket_list();
  $buckets=explode(" ",$buckets);
  $n=strlen(CHANNEL_BUCKET_PREFIX);
  $channels=array();
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,$n)==CHANNEL_BUCKET_PREFIX)
    {
      $channel=substr($buckets[$i],$n,strlen($buckets[$i])-$n);
      $index=CHANNEL_BUCKET_PREFIX.$channel;
      $nicks=get_array_bucket($index);
      if (in_array($nick,$nicks)==True)
      {
        $channels[]=$channel;
      }
    }
  }
  if (count($channels)>0)
  {
    sort($channels);
    privmsg("channels with $nick: ".implode(" ",$channels));
  }
}

#####################################################################################################

function count_nicks($channel)
{
  $bucket=get_array_bucket(CHANNEL_BUCKET_PREFIX.$channel);
  privmsg("nicks in $channel: ".count($bucket));
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
    $index=user_bucket_index($nick);
    irc_pause();
    $user=get_array_bucket($index);
    if (isset($user["channels"])==False)
    {
      $user["channels"]=array();
    }
    if (isset($user["channels"][$channel])==False)
    {
      $user["channels"][$channel]=array();
    }
    set_array_bucket($user,$index,False);
    irc_unpause();
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
  # TODO: do stuff here
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
  array_shift($parts);
  array_shift($parts);
  for ($i=0;$i<count($parts);$i++)
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
    $index=CHANNEL_BUCKET_PREFIX.$channel;
    irc_pause();
    $nicks=get_array_bucket($index);
    if (in_array($subject_nick,$nicks)==False)
    {
      $nicks[]=$subject_nick;
      set_array_bucket($nicks,$index,False);
    }
    irc_unpause();
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
  $index=CHANNEL_BUCKET_PREFIX.$channel;
  irc_pause();
  $nicks=get_array_bucket($index);
  if (in_array($nick,$nicks)==False)
  {
    $nicks[]=$nick;
    set_array_bucket($nicks,$index,False);
    term_echo("*** USERS: handle_join: $nick added to $channel");
  }
  else
  {
    term_echo("*** USERS: handle_join: $nick already in $channel");
  }
  irc_unpause();
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
  $index=CHANNEL_BUCKET_PREFIX.$channel;
  irc_pause();
  $nicks=get_array_bucket($index);
  $i=array_search($kicked_nick,$nicks);
  if ($i!==False)
  {
    unset($nicks[$i]);
    $nicks=array_values($nicks);
    set_array_bucket($nicks,$index,False);
    term_echo("*** USERS: handle_kick: $kicked_nick removed from $channel");
  }
  else
  {
    term_echo("*** USERS: handle_kick: $kicked_nick not found in $channel");
  }
  irc_unpause();
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
  $buckets=bucket_list();
  $buckets=explode(" ",$buckets);
  $n=strlen(CHANNEL_BUCKET_PREFIX);
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,$n)==CHANNEL_BUCKET_PREFIX)
    {
      $channel=substr($buckets[$i],$n,strlen($buckets[$i])-$n);
      $index=CHANNEL_BUCKET_PREFIX.$channel;
      irc_pause();
      $nicks=get_array_bucket($index);
      $j=array_search($old_nick,$nicks);
      if ($j!==False)
      {
        unset($nicks[$j]);
        $nicks=array_values($nicks);
        $nicks[]=$new_nick;
        set_array_bucket($nicks,$index,False);
        term_echo("*** USERS: handle_nick: $old_nick replaced with $new_nick in $channel");
      }
      irc_unpause();
    }
  }
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
  $index=CHANNEL_BUCKET_PREFIX.$channel;
  irc_pause();
  $nicks=get_array_bucket($index);
  $i=array_search($nick,$nicks);
  if ($i!==False)
  {
    unset($nicks[$i]);
    $nicks=array_values($nicks);
    set_array_bucket($nicks,$index,False);
    term_echo("*** USERS: handle_part: $nick removed from $channel");
  }
  else
  {
    term_echo("*** USERS: handle_part: $nick not found in $channel");
  }
  irc_unpause();
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
  $buckets=bucket_list();
  $buckets=explode(" ",$buckets);
  $n=strlen(CHANNEL_BUCKET_PREFIX);
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,$n)==CHANNEL_BUCKET_PREFIX)
    {
      $channel=substr($buckets[$i],$n,strlen($buckets[$i])-$n);
      $index=CHANNEL_BUCKET_PREFIX.$channel;
      irc_pause();
      $nicks=get_array_bucket($index);
      $j=array_search($nick,$nicks);
      if ($j!==False)
      {
        unset($nicks[$j]);
        $nicks=array_values($nicks);
        set_array_bucket($nicks,$index,False);
        term_echo("*** USERS: handle_quit: $nick removed from $channel");
      }
      irc_unpause();
    }
  }
}

#####################################################################################################

?>
