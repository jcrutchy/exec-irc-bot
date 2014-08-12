<?php

# gpl2
# by crutchy
# 12-aug-2014

#####################################################################################################

require_once("lib.php");

define("BUCKET_CHANNELS","<<EXEC_CHANNEL_DATA>>");
define("BUCKET_NICKS","<<EXEC_NICK_DATA>>");
define("BUCKET_ACCOUNTS","<<EXEC_ACCOUNT_DATA>>");

#####################################################################################################

function users_rebuild()
{
  unset_bucket(BUCKET_CHANNELS);
  unset_bucket(BUCKET_NICKS);
  unset_bucket(BUCKET_ACCOUNTS);
  do_list();
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
  append_array_bucket(BUCKET_CHANNELS,"$channel");
  sleep(1);
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
  append_array_bucket(BUCKET_NICKS,"$channel $nick $mode_info");
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
  append_array_bucket(BUCKET_ACCOUNTS,"$nick $account");
}

#####################################################################################################

function do_list()
{
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
