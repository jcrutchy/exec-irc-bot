<?php

# gpl2
# by crutchy
# 7-july-2014

#####################################################################################################

# installation-specific settings
define("NICK","exec");
define("PASSWORD_FILE","../pwd/".NICK);
define("BUCKETS_FILE","../data/buckets");
define("EXEC_FILE","exec.txt");
define("INIT_CHAN_LIST","#"); # comma delimited
define("LOG_PATH","/var/www/irciv.us.to/exec_logs/");
define("IRC_HOST","irc.sylnt.us");
define("IRC_PORT","6697");
define("MEMORY_LIMIT","128M");

$admin_accounts=array("crutchy");

#####################################################################################################

define("EXEC_DELIM","|");
define("MAX_MSG_LENGTH",800);
define("IGNORE_TIME",20); # seconds (flood control)
define("DELTA_TOLERANCE",1.5); # seconds (flood control)
define("TEMPLATE_DELIM","%%");

# stdout bot directives
define("DIRECTIVE_QUIT","<<quit>>");

# reserved aliases
define("ALIAS_ALL","*");
define("ALIAS_INIT","<init>");
define("ALIAS_QUIT","<quit>");

# commands intercepted from stdout
define("CMD_BUCKET_GET","BUCKET_GET");
define("CMD_BUCKET_SET","BUCKET_SET");
define("CMD_BUCKET_UNSET","BUCKET_UNSET");
define("CMD_INTERNAL","INTERNAL");

define("PREFIX_DELIM","/");
define("PREFIX_IRC",PREFIX_DELIM."IRC");
define("PREFIX_PRIVMSG",PREFIX_DELIM."PRIVMSG");
define("PREFIX_BUCKET_GET",PREFIX_DELIM.CMD_BUCKET_GET);
define("PREFIX_BUCKET_SET",PREFIX_DELIM.CMD_BUCKET_SET);
define("PREFIX_BUCKET_UNSET",PREFIX_DELIM.CMD_BUCKET_UNSET);
define("PREFIX_INTERNAL",PREFIX_DELIM.CMD_INTERNAL);

# internal aliases (can also use in exec file with alias locking, but that would be just weird)
define("ALIAS_ADMIN_QUIT","~q");
define("ALIAS_ADMIN_RESTART","~restart");
define("ALIAS_ADMIN_RELOAD","~reload");
define("ALIAS_ADMIN_DEST_OVERRIDE","~dest-override");
define("ALIAS_ADMIN_DEST_CLEAR","~dest-clear");
define("ALIAS_ADMIN_BUCKETS_DUMP","~buckets-dump"); # dump buckets to terminal
define("ALIAS_ADMIN_BUCKETS_SAVE","~buckets-save"); # save buckets to file
define("ALIAS_ADMIN_BUCKETS_LOAD","~buckets-load"); # load buckets from file
define("ALIAS_ADMIN_BUCKETS_FLUSH","~buckets-flush"); # re-initialize buckets
define("ALIAS_ADMIN_BUCKETS_LIST","~buckets-list"); # output list of set bucket indexes to the terminal
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
$buckets=array(); # common place for scripts to store stuff
$dest_overrides=array(); # optionally stores a destination for each nick, which treats every privmsg by that nick as having the set destination

$admin_data="";

$throttle_flag=False;
$rawmsg_times=array();

$admin_aliases=array(
  ALIAS_ADMIN_QUIT,
  ALIAS_ADMIN_RESTART,
  ALIAS_ADMIN_RELOAD,
  ALIAS_ADMIN_DEST_OVERRIDE,
  ALIAS_ADMIN_DEST_CLEAR,
  ALIAS_ADMIN_BUCKETS_DUMP,
  ALIAS_ADMIN_BUCKETS_SAVE,
  ALIAS_ADMIN_BUCKETS_LOAD,
  ALIAS_ADMIN_BUCKETS_FLUSH,
  ALIAS_ADMIN_BUCKETS_LIST);

$valid_data_cmd=get_valid_data_cmd();

$exec_list=exec_load();
if ($exec_list===False)
{
  term_echo("error loading exec file");
  return;
}

init();
if (IRC_PORT=="6697")
{
  $socket=fsockopen("ssl://".IRC_HOST,IRC_PORT);
}
else
{
  $socket=fsockopen(IRC_HOST,IRC_PORT);
}
if ($socket===False)
{
  term_echo("ERROR CREATING IRC SOCKET");
  die();
}
stream_set_blocking($socket,0);
rawmsg("NICK ".NICK);
rawmsg("USER ".NICK." hostname servername :".NICK.".bot");

# main program loop
while (True)
{
  for ($i=0;$i<count($handles);$i++)
  {
    if (handle_process($handles[$i])==False)
    {
      unset($handles[$i]);
    }
  }
  $handles=array_values($handles);
  handle_socket($socket);
  usleep(0.01e6); # 0.01 second to prevent cpu flogging
  process_timed_execs();
}

#####################################################################################################

?>
