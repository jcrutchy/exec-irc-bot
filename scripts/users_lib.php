<?php

# gpl2
# by crutchy
# 10-aug-2014

#####################################################################################################

require_once("lib.php");

define("BUCKET_CHANNELS","<<EXEC_CHANNEL_DATA>>");
define("BUCKET_NICKS","<<EXEC_NICK_DATA>>");

#####################################################################################################

function users_rebuild()
{
  global $channels;
  global $nicks;
  $channels=array();
  $nicks=array();
  do_list();
}

#####################################################################################################

function init_channel($channel)
{
  global $channels;
  $channels[$channel]=array();
  $channels[$channel]["nicks"]=array();
}

#####################################################################################################

function init_nick($nick)
{
  global $nicks;
  $nicks[$nick]=array();
  $nicks[$nick]["channels"]=array();
  $nicks[$nick]["account"]="";
  $nicks[$nick]["mode_info"]="";
}

#####################################################################################################

function users_add($channel,$nick)
{
  global $channels;
  global $nicks;
  if (isset($channels[$channel])==False)
  {
    init_channel($channel);
  }
  if (isset($nicks[$nick])==False)
  {
    init_nick($nick);
  }
  if (in_array($channel,$nicks[$nick]["channels"])==False)
  {
    $nicks[$nick]["channels"][]=$channel;
  }
  if (in_array($nick,$channels[$channel]["nicks"])==False)
  {
    $channels[$channel]["nicks"][]=$nick;
  }
}

#####################################################################################################

function handle_322($trailing) # <calling_nick> <channel> <nick_count>
{
  global $channels;
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
  if (isset($channels[$channel])==False)
  {
    init_channel($channel);
  }
  sleep(3);
  do_who($channel);
}

#####################################################################################################

function handle_354($trailing) # <calling_nick> 152 <channel> <nick> <mode_info>
{
  global $channels;
  global $nicks;
  $parts=explode(" ",$trailing);
  if (count($parts)<>5)
  {
    return;
  }
  $channel=strtolower(trim($parts[2]));
  $nick=strtolower(trim($parts[3]));
  $mode_info=strtolower(trim($parts[4]));
  if (($channel=="") or ($nick==""))
  {
    return;
  }
  users_add($channel,$nick);
  $nicks[$nick]["mode_info"]=$mode_info;
  sleep(3);
  do_whois($nick);
}

#####################################################################################################

function handle_330($trailing) # <calling_nick> <nick> <account>
{
  global $channels;
  global $nicks;
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
  if (isset($nicks[$nick])==False)
  {
    init_nick($nick);
  }
  $nicks[$nick]["account"]=$account;
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
