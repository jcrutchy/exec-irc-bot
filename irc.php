<?php

# gpl2
# by crutchy
# 20-aug-2014

#####################################################################################################

# installation-specific settings
define("NICK","exec");
define("PASSWORD_FILE","../pwd/".NICK);
define("BUCKETS_FILE","../data/buckets");
define("IGNORE_FILE","../data/ignore");
define("EXEC_FILE","exec.txt");
define("INIT_CHAN_LIST","#"); # comma delimited
define("EXEC_LOG_PATH","/var/www/irciv.us.to/exec_logs/");
define("IRC_LOG_URL","http://irciv.us.to/irc_logs/");
define("IRC_HOST_CONNECT","irc.sylnt.us");
define("IRC_HOST","irc.sylnt.us");
define("IRC_PORT","6697");
define("MEMORY_LIMIT","128M");

$admin_accounts=array("crutchy","xlefay","chromas");

#####################################################################################################

define("EXEC_DELIM","|");
define("MAX_MSG_LENGTH",800);
define("IGNORE_TIME",20); # seconds (alias abuse control)
define("DELTA_TOLERANCE",1.5); # seconds (alias abuse control)
define("TEMPLATE_DELIM","%%");

# stdout bot directives
define("DIRECTIVE_QUIT","<<quit>>");

# internally used buckets
define("BUCKET_LOGGED_CHANS","<<LOGGED_CHANNELS>>");
define("BUCKET_IGNORE_NEXT","<<BOT_IGNORE_NEXT>>");

# reserved aliases
define("ALIAS_ALL","*");
define("ALIAS_LOG_ITEMS","<log>");
define("ALIAS_INIT","<init>");
define("ALIAS_STARTUP","<startup>");
define("ALIAS_QUIT","<quit>");

# commands intercepted from stdout
define("CMD_BUCKET_GET","BUCKET_GET");
define("CMD_BUCKET_SET","BUCKET_SET");
define("CMD_BUCKET_UNSET","BUCKET_UNSET");
define("CMD_BUCKET_APPEND","BUCKET_APPEND");
define("CMD_INTERNAL","INTERNAL");

define("PREFIX_DELIM","/");
define("PREFIX_IRC",PREFIX_DELIM."IRC");
define("PREFIX_PRIVMSG",PREFIX_DELIM."PRIVMSG");
define("PREFIX_BUCKET_GET",PREFIX_DELIM.CMD_BUCKET_GET);
define("PREFIX_BUCKET_SET",PREFIX_DELIM.CMD_BUCKET_SET);
define("PREFIX_BUCKET_UNSET",PREFIX_DELIM.CMD_BUCKET_UNSET);
define("PREFIX_BUCKET_APPEND",PREFIX_DELIM.CMD_BUCKET_APPEND);
define("PREFIX_INTERNAL",PREFIX_DELIM.CMD_INTERNAL);

# internal aliases (can also use in exec file with alias locking, but that would be just weird)
define("ALIAS_INTERNAL_RESTART","~restart-internal");
define("ALIAS_ADMIN_QUIT","~q");
define("ALIAS_ADMIN_PS","~ps");
define("ALIAS_ADMIN_KILL","~kill");
define("ALIAS_ADMIN_KILLALL","~killall");
define("ALIAS_ADMIN_RESTART","~restart");
define("ALIAS_ADMIN_REHASH","~rehash");
define("ALIAS_ADMIN_DEST_OVERRIDE","~dest-override");
define("ALIAS_ADMIN_DEST_CLEAR","~dest-clear");
define("ALIAS_ADMIN_IGNORE","~ignore");
define("ALIAS_ADMIN_UNIGNORE","~unignore");
define("ALIAS_ADMIN_LIST_IGNORE","~ignore-list");
define("ALIAS_ADMIN_BUCKETS_DUMP","~buckets-dump"); # dump buckets to terminal
define("ALIAS_ADMIN_BUCKETS_SAVE","~buckets-save"); # save buckets to file
define("ALIAS_ADMIN_BUCKETS_LOAD","~buckets-load"); # load buckets from file
define("ALIAS_ADMIN_BUCKETS_FLUSH","~buckets-flush"); # re-initialize buckets
define("ALIAS_ADMIN_BUCKETS_LIST","~buckets-list"); # output list of set bucket indexes to the terminal
define("ALIAS_LOG","~log");
define("ALIAS_LOCK","~lock");
define("ALIAS_UNLOCK","~unlock");
define("ALIAS_LIST","~list");
define("ALIAS_LIST_AUTH","~list-auth");

# exec file shell command templates (replaced by the bot with actual values before executing)
define("TEMPLATE_TRAILING","trailing");
define("TEMPLATE_NICK","nick");
define("TEMPLATE_DESTINATION","dest");
define("TEMPLATE_START","start");
define("TEMPLATE_ALIAS","alias");
define("TEMPLATE_DATA","data");
define("TEMPLATE_CMD","cmd");
define("TEMPLATE_PARAMS","params");
define("TEMPLATE_TIMESTAMP","timestamp");

define("THROTTLE_LOCKOUT_TIME",10); # sec
define("ANTI_FLOOD_DELAY",0.6); # sec
define("RAWMSG_TIME_COUNT",6); # messages to send without any delays

require_once("irc_lib.php");

set_time_limit(0); # script needs to run for indefinite time (overrides setting in php.ini)
ini_set("memory_limit",MEMORY_LIMIT);
ini_set("display_errors","on"); # output errors to stdout

define("START_TIME",microtime(True)); # used for %%start%% template

if (file_exists(PASSWORD_FILE)==False)
{
  term_echo("bot NickServ password file not found. quitting");
  return;
}

$alias_locks=array(); # optionally stores an alias for each nick, which then treats every privmsg by that nick as being prefixed by the set alias
$handles=array(); # stores executed process information
$time_deltas=array(); # keeps track of how often nicks call an alias (used for flood control)
$buckets=array(); # common place for scripts to store stuff (index cannot contain spaces, bucket content must be a string)
$dest_overrides=array(); # optionally stores a destination for each nick, which treats every privmsg by that nick as having the set destination

$admin_data="";
$admin_is_sock="";

$logged_chans=array();
$buckets[BUCKET_LOGGED_CHANS]=serialize($logged_chans);
unset($logged_chans);

$throttle_time=False; # set when "throttled" is detected in a message from the server
$rawmsg_times=array();

$admin_aliases=array(
  ALIAS_ADMIN_QUIT,
  ALIAS_ADMIN_RESTART,
  ALIAS_ADMIN_PS,
  ALIAS_ADMIN_KILL,
  ALIAS_ADMIN_KILLALL,
  ALIAS_ADMIN_REHASH,
  ALIAS_ADMIN_DEST_OVERRIDE,
  ALIAS_ADMIN_DEST_CLEAR,
  ALIAS_ADMIN_BUCKETS_DUMP,
  ALIAS_ADMIN_BUCKETS_SAVE,
  ALIAS_ADMIN_BUCKETS_LOAD,
  ALIAS_ADMIN_BUCKETS_FLUSH,
  ALIAS_ADMIN_BUCKETS_LIST,
  ALIAS_ADMIN_IGNORE,
  ALIAS_ADMIN_UNIGNORE,
  ALIAS_ADMIN_LIST_IGNORE);

$reserved_aliases=array(
  ALIAS_ALL,
  ALIAS_LOG_ITEMS,
  ALIAS_INIT,
  ALIAS_STARTUP,
  ALIAS_QUIT);

$valid_data_cmd=get_valid_data_cmd();

$exec_list=exec_load();
if ($exec_list===False)
{
  term_echo("error loading exec file");
  return;
}

$ignore_list=array();
if (file_exists(IGNORE_FILE)==True)
{
  $ignore_data=file_get_contents(IGNORE_FILE);
  if ($ignore_data!==False)
  {
    $ignore_list=explode("\n",$ignore_data);
  }
}
delete_empty_elements($ignore_list);

init();
if (IRC_PORT=="6697")
{
  $socket=fsockopen("ssl://".IRC_HOST_CONNECT,IRC_PORT);
}
else
{
  $socket=fsockopen(IRC_HOST_CONNECT,IRC_PORT);
}
if ($socket===False)
{
  term_echo("ERROR CREATING IRC SOCKET");
}
else
{
  stream_set_blocking($socket,0);
  rawmsg("NICK ".NICK);
  rawmsg("USER ".NICK." hostname servername :".NICK.".bot");
}

# main program loop
while (True)
{
  for ($i=0;$i<count($handles);$i++)
  {
    if (isset($handles[$i])==False)
    {
      continue;
    }
    if (handle_process($handles[$i])==False)
    {
      unset($handles[$i]);
    }
  }
  $handles=array_values($handles);
  handle_socket($socket);
  usleep(0.05e6); # 0.05 second to prevent cpu flogging
  process_timed_execs();
}

#####################################################################################################

?>
