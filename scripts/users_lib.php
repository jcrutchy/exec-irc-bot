<?php

# gpl2
# by crutchy
# 24-aug-2014

#####################################################################################################

require_once("lib.php");

define("BUCKET_USER_TEMPLATE","<<EXEC_USER_%%nick%%>>");

#####################################################################################################

function user_bucket_index($nick)
{
  return str_replace("%%nick%%",$nick,BUCKET_USER_TEMPLATE);
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
    ##
    sleep(1);
    do_whois($nick);
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
  ##
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
  ##
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
  $channel=strtolower(trim($parts[1]));
  $kicked_nick=strtolower(trim($parts[2]));
  if (($op_nick=="") or ($channel=="") or ($kicked_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_kick: op_nick = $op_nick");
  term_echo("*** USERS: handle_kick: channel = $channel");
  term_echo("*** USERS: handle_kick: kicked_nick = $kicked_nick");
  $index=user_bucket_index($kicked_nick);
  ##
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
  ##
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
  ##
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
  ##
}

#####################################################################################################

function do_whois($nick)
{
  #rawmsg("WHOIS $nick");
}

#####################################################################################################

?>
