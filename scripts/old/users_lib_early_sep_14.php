<?php

# gpl2
# by crutchy
# 6-sep-2014

#####################################################################################################

require_once("lib.php");

define("BUCKET_USER_TEMPLATE","<<EXEC_USER_%%nick%%>>");

#####################################################################################################

function get_account($nick)
{
  $nick=strtolower(trim($nick));
  $index=user_bucket_index($nick);
  $user=get_array_bucket($index);
  if (isset($user["account"])==True)
  {
    return $user["account"];
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function get_user($nick)
{
  $nick=strtolower(trim($nick));
  $index=user_bucket_index($nick);
  return get_array_bucket($index);
}

#####################################################################################################

function get_user_array()
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
    $result[]=get_array_bucket($index);
  }
  return $result;
}

#####################################################################################################

function save_all($filename)
{
  $users=get_user_array();
  $data="";
  for ($i=0;$i<count($users);$i++)
  {
    if ($data<>"")
    {
      $data=$data."\n";
    }
    $data=$data.format_array($users[$i],"nick|channels");
  }
  if (file_put_contents($filename,$data)===False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function get_channel_users($channel)
{
  $channel=strtolower(trim($channel));
  if ($channel=="")
  {
    return False;
  }
  $users=get_user_array();
  $result=array();
  for ($i=0;$i<count($users);$i++)
  {
    if (isset($users[$i]["channels"])==True)
    {
      if (in_array($channel,$users[$i]["channels"])==True)
      {
        $result[]=$users[$i]["nick"];
      }
    }
  }
  return $result;
}

#####################################################################################################

function user_bucket_index($nick)
{
  return "<<EXEC_USER_$nick>>";
}

#####################################################################################################

function init_channels(&$user)
{
  if (isset($user["channels"])==False)
  {
    $user["channels"]=array();
  }
}

#####################################################################################################

function add_channel(&$user,$channel)
{
  init_channels($user);
  if (in_array($channel,$user["channels"])==False)
  {
    $user["channels"][]=$channel;
  }
}

#####################################################################################################

function remove_channel(&$user,$channel)
{
  init_channels($user);
  $key=array_search($channel,$user["channels"]);
  if ($key===False)
  {
    return;
  }
  $user["channels"]=array_splice($user["channels"],$key,1);
}

#####################################################################################################

function handle_353($trailing) # <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<4)
  {
    return;
  }
  $channel=strtolower(trim($parts[2]));
  if ($channel=="")
  {
    return;
  }
  for ($i=1;$i<=3;$i++)
  {
    array_shift($parts);
  }
  for ($i=0;$i<count($parts);$i++)
  {
    $nick=strtolower(trim($parts[$i]));
    if ($nick=="")
    {
      continue;
    }
    # check for + or @ as first char of nick
    $auth=$nick[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $nick=trim(substr($nick,1));
      if ($nick=="")
      {
        continue;
      }
    }
    else
    {
      $auth="";
    }
    term_echo("*** USERS: handle_353: nick = $nick");
    term_echo("*** USERS: handle_353: auth = $auth");
    $index=user_bucket_index($nick);
    $user=get_array_bucket($index);
    $user["nick"]=$nick;
    $user["auth"]=$auth;
    add_channel($user,$channel);
    set_array_bucket($user,$index,False);
    sleep(1);
    do_whois($nick); # only if nick account not set already
  }
}

#####################################################################################################

function handle_330($trailing) # <calling_nick> <nick> <account>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>3)
  {
    return;
  }
  $nick=strtolower(trim($parts[1]));
  $account=strtolower(trim($parts[2]));
  if (($nick=="") or ($account==""))
  {
    return;
  }
  term_echo("*** USERS: handle_330: nick = $nick");
  term_echo("*** USERS: handle_330: account = $account");
  $index=user_bucket_index($nick);
  $user=get_array_bucket($index);
  $user["nick"]=$nick;
  $user["account"]=$account;
  set_array_bucket($user,$index,False);
}

#####################################################################################################

function handle_join($nick,$channel)
{
  $nick=strtolower(trim($nick));
  $channel=strtolower(trim($channel));
  if (($nick=="") or ($channel==""))
  {
    return;
  }
  term_echo("*** USERS: handle_join: nick = $nick");
  term_echo("*** USERS: handle_join: channel = $channel");
  $index=user_bucket_index($nick);
  $user=get_array_bucket($index);
  $user["nick"]=$nick;
  add_channel($user,$channel);
  set_array_bucket($user,$index,False);
}

#####################################################################################################

function handle_kick($op_nick,$trailing) # <channel> <kicked_nick>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>2)
  {
    return;
  }
  $op_nick=strtolower(trim($op_nick));
  $channel=strtolower(trim($parts[0]));
  $kicked_nick=strtolower(trim($parts[1]));
  if (($op_nick=="") or ($channel=="") or ($kicked_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_kick: op_nick = $op_nick");
  term_echo("*** USERS: handle_kick: channel = $channel");
  term_echo("*** USERS: handle_kick: kicked_nick = $kicked_nick");
  $index=user_bucket_index($kicked_nick);
  $user=get_array_bucket($index);
  $user["nick"]=$nick;
  remove_channel($user,$channel);
  set_array_bucket($user,$index,False);
}

#####################################################################################################

function handle_nick($old_nick,$new_nick)
{
  $old_nick=strtolower(trim($old_nick));
  $new_nick=strtolower(trim($new_nick));
  if (($old_nick=="") or ($new_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_nick: old_nick = $old_nick");
  term_echo("*** USERS: handle_nick: new_nick = $new_nick");
  $index=user_bucket_index($old_nick);
  $user=get_array_bucket($index);
  $user["nick"]=$new_nick;
  unset_bucket($index);
  $index=user_bucket_index($new_nick);
  set_array_bucket($user,$index,False);
}

#####################################################################################################

function handle_part($nick,$channel)
{
  $nick=strtolower(trim($nick));
  $channel=strtolower(trim($channel));
  if (($nick=="") or ($channel==""))
  {
    return;
  }
  term_echo("*** USERS: handle_part: nick = $nick");
  term_echo("*** USERS: handle_part: channel = $channel");
  $index=user_bucket_index($nick);
  $user=get_array_bucket($index);
  $user["nick"]=$nick;
  remove_channel($user,$channel);
  set_array_bucket($user,$index,False);
  # if $nick = exec, unset all buckets for users who are only in the parted channel
}

#####################################################################################################

function handle_quit($nick)
{
  $nick=strtolower(trim($nick));
  if ($nick=="")
  {
    return;
  }
  term_echo("*** USERS: handle_quit: nick = $nick");
  $index=user_bucket_index($nick);
  unset_bucket($index);
  # if $nick = exec, unset all buckets
}

#####################################################################################################

function do_whois($nick)
{
  #rawmsg("WHOIS $nick");
}

#####################################################################################################

?>
