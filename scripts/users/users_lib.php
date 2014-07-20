<?php

# gpl2
# by crutchy
# 18-july-2014

#####################################################################################################

require_once("scripts/lib.php");

define("BUCKET_CHANNELS","<<channels>>");
define("BUCKET_USERS","<<users>>");

#####################################################################################################

function on_join($nick,$chan)
{
  term_echo("users: \"$nick\" joined \"$chan\"");
}

#####################################################################################################

function on_part($nick,$chan)
{
  term_echo("users: \"$nick\" parted \"$chan\"");
}

#####################################################################################################

function on_kick($op_nick,$kicked_nick,$chan)
{
  term_echo("users: \"$op_nick\" kicked \"$kicked_nick\" from \"$chan\"");
}

#####################################################################################################

function on_quit_chan($nick,$chan)
{
  term_echo("users: \"$nick\" quit from \"$chan\"");
}

#####################################################################################################

function on_quit($nick)
{
  term_echo("users: \"$nick\" quit");
}

#####################################################################################################

function on_nick_chan($old,$new,$chan)
{
  term_echo("users: \"$old\" changed nick to \"$new\" in \"$chan\"");
}

#####################################################################################################

function on_nick($old,$new)
{
  term_echo("users: \"$old\" changed nick to \"$new\"");
}

#####################################################################################################

function on_nick_chan_list($nick)
{
  term_echo("users: channel list generated for nick \"$nick\"");
}

#####################################################################################################

function on_nick_chan_list_add($nick,$chan)
{
  term_echo("users: \"$chan\" added to channel list for \"$nick\"");
}

#####################################################################################################

function on_nickserv_account($nick,$account)
{
  term_echo("users: account for \"$nick\" set to \"$account\"");
}

#####################################################################################################

function on_chan_nick_list_add($chan,$nick)
{
  term_echo("users: \"$chan\" added to channel list for \"$nick\"");
}

#####################################################################################################

function on_chan_nick_list($chan)
{
  term_echo("users: nick list generated for channel \"$chan\"");
}

#####################################################################################################

function whois($nick)
{
  rawmsg("WHOIS $nick");
}

#####################################################################################################

?>
