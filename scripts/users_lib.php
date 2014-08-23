<?php

# gpl2
# by crutchy
# 23-aug-2014

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
    # check for + or @ as first char of nick
    /*$record=array();
    $record["channel"]=$channel;
    $record["mode_info"]=$mode_info;
    $index=user_bucket_index($nick);
    $data=serialize($record);
    set_bucket($index,$data);
    sleep(1);
    do_whois($nick);*/
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
  # do stuff
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
  # do stuff
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
  # do stuff
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
  # do stuff
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
  # do stuff
}

#####################################################################################################

function handle_quit($nick)
{
  $nick=strtolower(trim($nick));
  if ($nick=="")
  {
    return;
  }
  # do stuff
}

#####################################################################################################

function do_whois($nick)
{
  rawmsg("WHOIS $nick");
}

#####################################################################################################

?>
