<?php

# gpl2
# by crutchy
# 15-july-2014

#####################################################################################################

require_once("lib.php");

#####################################################################################################

function on_join($nick,$chan)
{
  pm("#","users: \"$nick\" joined \"$chan\"");
}

#####################################################################################################

function on_part($nick,$chan)
{
  pm("#","users: \"$nick\" parted \"$chan\"");
}

#####################################################################################################

function on_kick($op_nick,$kicked_nick,$chan)
{
  pm("#","users: \"$op_nick\" kicked \"$kicked_nick\" from \"$chan\"");
}

#####################################################################################################

function on_quit_chan($nick,$chan)
{
  pm("#","users: \"$nick\" quit from \"$chan\"");
}

#####################################################################################################

function on_quit($nick)
{
  pm("#","users: \"$nick\" quit");
}

#####################################################################################################

function on_nick_chan($old,$new,$chan)
{
  pm("#","users: \"$old\" changed nick to \"$new\" in \"$chan\"");
}

#####################################################################################################

function on_nick($old,$new)
{
  pm("#","users: \"$old\" changed nick to \"$new\"");
}

#####################################################################################################

function whois($nick)
{

}

#####################################################################################################

?>
