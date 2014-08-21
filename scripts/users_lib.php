<?php

# gpl2
# by crutchy
# 21-aug-2014

#####################################################################################################

require_once("lib.php");

define("BUCKET_USER_TEMPLATE","<<EXEC_USER_%%nick%%>>");

#####################################################################################################

function user_bucket_index($nick)
{
  return str_replace("%%nick%%",$nick,BUCKET_USER_TEMPLATE);
}

#####################################################################################################

function handle_322($trailing) # <calling_nick> <channel> <nick_count>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>3)
  {
    return;
  }
  $channel=strtolower(trim($parts[1]));
  if ($channel=="")
  {
    return;
  }
  do_who($channel);
}

#####################################################################################################

function handle_354($trailing) # <calling_nick> 152 <channel> <nick> <mode_info>
{
  $parts=explode(" ",$trailing);
  if (count($parts)<>5)
  {
    return;
  }
  $channel=strtolower(trim($parts[2]));
  $nick=strtolower(trim($parts[3]));
  $mode_info=strtolower(trim($parts[4]));
  if (($channel=="") or ($nick=="") or ($mode_info==""))
  {
    return;
  }
  $record=array();
  $record["channel"]=$channel;
  $record["mode_info"]=$mode_info;
  $index=user_bucket_index($nick);
  $data=serialize($record);
  set_bucket($index,$data);
  sleep(1);
  do_whois($nick);
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
  # if $nick == NICK_EXEC then do_who($channel)
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

function do_list()
{
  unset_bucket(BUCKET_NICKS);
  sleep(1);
  rawmsg("LIST >0,<10000");
}

#####################################################################################################

function do_who($channel)
{
  rawmsg("WHO $channel %ctnf,152");
}

#####################################################################################################

function do_whois($nick)
{
  rawmsg("WHOIS $nick");
}

#####################################################################################################

?>
