<?php

#####################################################################################################

# TODO: PRIVMSG EVENTS APPEAR IN CHANNEL WITH ANY ALIAS LOCK
# TODO: PIPED SCRIPTS => ~alias1 trailing | ~alias2 trailing | ~alias3 trailing (stdout of left pipes to shellarg of right) ???
# TODO: OPTION TO REDIRECT PRIVMSG OUTPUT FROM SCRIPTS TO A BUCKET
# TODO: /WAIT COMMAND THAT ALLOWS INDIVIDUAL SCRIPTS TO BE HALTED TILL A CERTAIN COMMAND IS RECEIVED BY THE BOT (SIMILAR USAGE TO BUCKET_GET)
# TODO: QUIT COMMANDS FOR SCRIPTS (SIMILAR TO INIT & STARTUP) - USEFUL FOR SHUTTING DOWN DATA SERVERS
# TODO: ADD FLAG TO HAVE EXEC IGNORE ITSELF
# TODO: ADD && OPERATOR FOR STRINGING COMMANDS TOGETHER. OUTPUT FROM EACH COMMAND SHOULD BE ON SEPARATE LINES

require_once("irc_lib.php");

set_time_limit(0); # script needs to run for indefinite time (overrides setting in php.ini)
ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);
date_default_timezone_set("UTC");

define("START_TIME",microtime(True)); # used for %%start%% template

if (isset($argv[1])==False)
{
  # default installation-specific settings
  define("DEFAULT_NICK","x");
  define("USER_NAME","x");
  define("FULL_NAME","exec.bot");
  define("PASSWORD_FILE","../pwd/exec");
  define("BUCKETS_FILE","../data/buckets");
  define("IGNORE_FILE","../data/ignore");
  define("EXEC_FILE","exec.txt");
  define("INIT_CHAN_LIST","#crutchy,#debug"); # comma delimited
  define("IRC_HOST_CONNECT","chat.soylentnews.org");
  define("IRC_HOST","irc.sylnt.us");
  define("IRC_PORT","6697");
  define("OPERATOR_ACCOUNT","crutchy");
  define("OPERATOR_HOSTNAME","709-27-2-01.cust.aussiebb.net");
  define("DEBUG_CHAN","#debug");
  define("NICKSERV_IDENTIFY_PROMPT","You have 60 seconds to identify to your nickname before it is changed.");
  define("ADMIN_ACCOUNTS","chromas,juggs,martyb,cmn32480");
  define("MYSQL_LOG","1");
  define("NICKSERV_IDENTIFY","1");
  define("IFACE_ENABLE","0");
  define("SSL_PEER_NAME","*.soylentnews.org");
  define("SSL_CA_FILE","/etc/ssl/certs/ca-certificates.crt");
  #define("SSL_CA_FILE","/home/jared/git/data/soylentnews.crt"); # cafile must contain both peer cert (https://staff.soylentnews.org/~bob/wildcard.crt) and then CA cert (http://sylnt.us/SoylentNewsCA.crt) in single bundled file in order of peer, then CA
}
elseif (file_exists($argv[1])==True)
{
  $settings=file_get_contents($argv[1]);
  $settings=explode("\n",$settings);
  for ($i=0;$i<count($settings);$i++)
  {
    $line=trim($settings[$i]);
    if ($line=="")
    {
      continue;
    }
    $keyval=explode("=",$line);
    if (count($keyval)<>2)
    {
      continue;
    }
    define($keyval[0],$keyval[1]);
  }
}
else
{
  die("INVALID COMMAND LINE ARGUMENT\n");
}

if (MYSQL_LOG=="1")
{
  require_once("scripts/lib_mysql.php");
}

define("EXEC_OUTPUT_BUFFER_FILE","../data/exec_iface");

define("EXEC_DELIM","|");
define("EXEC_DIRECTIVE_DELIM"," ");
define("EXEC_INCLUDE","include");
define("EXEC_INIT","init");
define("EXEC_STARTUP","startup");
define("EXEC_HELP","help");
define("FILE_DIRECTIVE_DELIM",":");
define("FILE_DIRECTIVE_EXEC","exec");
define("FILE_DIRECTIVE_INIT","init");
define("FILE_DIRECTIVE_STARTUP","startup");
define("FILE_DIRECTIVE_HELP","help");
define("MAX_MSG_LENGTH",458);
define("IGNORE_TIME",20); # seconds (alias abuse control)
define("DELTA_TOLERANCE",1.5); # seconds (alias abuse control)
define("TEMPLATE_DELIM","%%");

# stdout bot directives
define("DIRECTIVE_QUIT","<<quit>>");

# internally used buckets
define("BUCKET_IGNORE_NEXT","<<BOT_IGNORE_NEXT>>");
define("BUCKET_USERS","<<EXEC_USERS>>");
define("BUCKET_EVENT_HANDLERS","<<EXEC_EVENT_HANDLERS>>");
define("BUCKET_CONNECTION_ESTABLISHED","<<IRC_CONNECTION_ESTABLISHED>>");
define("BUCKET_SELF_TRIGGER_EVENTS_FLAG","<<SELF_TRIGGER_EVENTS_FLAG>>");
define("BUCKET_EXEC_LIST","<<EXEC_LIST>>");
define("BUCKET_BOT_NICK","<<BOT_NICK>>");
define("BUCKET_ADMIN_ACCOUNTS_LIST","<<ADMIN_ACCOUNTS_LIST>>");
define("BUCKET_OPERATOR_ACCOUNT","<<OPERATOR_ACCOUNT>>");
define("BUCKET_OPERATOR_HOSTNAME","<<OPERATOR_HOSTNAME>>");
define("BUCKET_MEMORY_USAGE","<<BOT_MEMORY_USAGE>>");
define("BUCKET_OUTPUT_CONTROL","<<OUTPUT_CONTROL>>");
define("BUCKET_SHUTDOWN","<<SHUTDOWN>>");

define("BUCKET_PROCESS_TEMPLATE_PREFIX","process_template_");
define("BUCKET_ALIAS_ELEMENT_PREFIX","alias_element_");

$internal_bucket_indexes=array(
  BUCKET_IGNORE_NEXT,
  BUCKET_USERS,
  BUCKET_EVENT_HANDLERS,
  BUCKET_CONNECTION_ESTABLISHED,
  #BUCKET_SELF_TRIGGER_EVENTS_FLAG,
  BUCKET_EXEC_LIST,
  BUCKET_BOT_NICK,
  BUCKET_PROCESS_TEMPLATE_PREFIX,
  BUCKET_ALIAS_ELEMENT_PREFIX);

# reserved aliases
define("ALIAS_ALL","*");
define("ALIAS_INIT","<init>");
define("ALIAS_STARTUP","<startup>");
define("ALIAS_QUIT","<quit>");

# commands intercepted from stdout
# TODO: ADD COMMANDS TO DYNAMICALLY ADD/REMOVE BUCKET LOCKS TO AN ALIAS DEFINITION
define("CMD_BUCKET_GET","BUCKET_GET");
define("CMD_BUCKET_SET","BUCKET_SET");
define("CMD_BUCKET_UNSET","BUCKET_UNSET");
define("CMD_BUCKET_APPEND","BUCKET_APPEND");
define("CMD_BUCKET_LIST","BUCKET_LIST");
define("CMD_INTERNAL","INTERNAL");
define("CMD_PAUSE","BOT_IRC_PAUSE");
define("CMD_UNPAUSE","BOT_IRC_UNPAUSE");
define("CMD_INIT","INIT");
define("CMD_STARTUP","STARTUP");
define("CMD_DELETE_HANDLER","DELETE_HANDLER");

define("PREFIX_DELIM","/");
define("PREFIX_IRC",PREFIX_DELIM."IRC");
define("PREFIX_EXEC_ADD",PREFIX_DELIM."EXEC-ADD");
define("PREFIX_EXEC_DEL",PREFIX_DELIM."EXEC-DEL");
define("PREFIX_EXEC_SAVE",PREFIX_DELIM."EXEC-SAVE");
define("PREFIX_PRIVMSG",PREFIX_DELIM."PRIVMSG");
define("PREFIX_BUCKET_GET",PREFIX_DELIM.CMD_BUCKET_GET);
define("PREFIX_BUCKET_SET",PREFIX_DELIM.CMD_BUCKET_SET);
define("PREFIX_BUCKET_UNSET",PREFIX_DELIM.CMD_BUCKET_UNSET);
define("PREFIX_BUCKET_APPEND",PREFIX_DELIM.CMD_BUCKET_APPEND);
define("PREFIX_BUCKET_LIST",PREFIX_DELIM.CMD_BUCKET_LIST);
define("PREFIX_INTERNAL",PREFIX_DELIM.CMD_INTERNAL);
define("PREFIX_PAUSE",PREFIX_DELIM.CMD_PAUSE);
define("PREFIX_UNPAUSE",PREFIX_DELIM.CMD_UNPAUSE);
define("PREFIX_DELETE_HANDLER",PREFIX_DELIM.CMD_DELETE_HANDLER);
define("PREFIX_READER_EXEC_LIST",PREFIX_DELIM."READER_EXEC_LIST");
define("PREFIX_READER_BUCKETS",PREFIX_DELIM."READER_BUCKETS");
define("PREFIX_READER_HANDLES",PREFIX_DELIM."READER_HANDLES");

# internal aliases (can also use in exec file with alias locking, but that would be just weird)
define("ALIAS_INTERNAL_RESTART","~restart-internal");
define("ALIAS_ADMIN_ALIAS_MACRO","~alias-macro");
define("ALIAS_ADMIN_QUIT","~quit");
define("ALIAS_ADMIN_NICK","~nick");
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
define("ALIAS_ADMIN_EXEC_CONFLICTS","~exec-conflicts");
define("ALIAS_ADMIN_EXEC_LIST","~exec-list");
define("ALIAS_ADMIN_EXEC_TIMERS","~exec-timers");
define("ALIAS_ADMIN_EXEC_ERRORS","~exec-errors");
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
define("TEMPLATE_ITEMS","items");
define("TEMPLATE_CMD","cmd");
define("TEMPLATE_PARAMS","params");
define("TEMPLATE_TIMESTAMP","timestamp");
define("TEMPLATE_SERVER","server");
define("TEMPLATE_USER","user");
define("TEMPLATE_HOSTNAME","hostname");
define("TEMPLATE_PREFIX","prefix");

define("THROTTLE_LOCKOUT_TIME",10); # sec
define("ANTI_FLOOD_DELAY",0.7); # sec
define("RAWMSG_TIME_COUNT",5); # messages to send without any delays

$admin_accounts=explode(",",ADMIN_ACCOUNTS);

$alias_locks=array(); # optionally stores an alias for each nick, which then treats every privmsg by that nick as being prefixed by the set alias
$handles=array(); # stores executed process information
$time_deltas=array(); # keeps track of how often nicks call an alias (used for alias abuse control)
$buckets=array(); # common place for scripts to store stuff (index cannot contain spaces, bucket content must be a string)
$dest_overrides=array(); # optionally stores a destination for each nick, which treats every privmsg by that nick as having the set destination
$bucket_locks=array(); # any bucket index put here by execution of an alias with bucket locks in its definition line cannot be read or written by other scripts: index=>array(pid1,pid2,etc)

$admin_data="";
$admin_is_sock="";

$irc_pause=False;

$throttle_time=False; # set when "throttled" is detected in a message from the server
$rawmsg_times=array();

# aliases that may only be executed by the bot operator account
$operator_aliases=array(
  ALIAS_ADMIN_ALIAS_MACRO);

$admin_aliases=array(
  ALIAS_ADMIN_QUIT,
  ALIAS_ADMIN_NICK,
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
  ALIAS_ADMIN_LIST_IGNORE,
  ALIAS_ADMIN_EXEC_CONFLICTS,
  ALIAS_ADMIN_EXEC_LIST,
  ALIAS_ADMIN_EXEC_TIMERS,
  ALIAS_ADMIN_EXEC_ERRORS);

$reserved_aliases=array(
  ALIAS_ALL,
  ALIAS_INIT,
  ALIAS_STARTUP,
  ALIAS_QUIT);

$silent_timeout_commands=array(
  CMD_INTERNAL,
  CMD_BUCKET_GET,
  CMD_BUCKET_SET,
  CMD_BUCKET_UNSET,
  CMD_BUCKET_APPEND,
  CMD_BUCKET_LIST,
  CMD_PAUSE,
  CMD_UNPAUSE);

$valid_data_cmd=get_valid_data_cmd();

$init=array();
$startup=array();
$help=array();

initialize_buckets();

$exec_errors=array(); # stores exec load errors
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

$direct_stdin=fopen("php://stdin","r");
stream_set_blocking($direct_stdin,0);

if (file_exists(EXEC_OUTPUT_BUFFER_FILE)==True)
{
  unlink(EXEC_OUTPUT_BUFFER_FILE);
}

if (IFACE_ENABLE==="1")
{
  if (posix_mkfifo(EXEC_OUTPUT_BUFFER_FILE,0700)==False)
  {
    term_echo("error creating output buffer file");
    return;
  }
  $out_buffer=fopen(EXEC_OUTPUT_BUFFER_FILE,"a+");
  if ($out_buffer===False)
  {
    term_echo("error opening output buffer file for writing");
    return;
  }
  stream_set_blocking($out_buffer,0);
}

init();

$socket=initialize_socket();
initialize_irc_connection();

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
  handle_direct_stdin();
  process_timed_execs();
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[33m".date("Y-m-d H:i:s",microtime(True))." > \033[31m$msg\033[0m\n";
}

#####################################################################################################

function privmsg($destination,$nick,$msg)
{
  global $dest_overrides;
  if ($destination=="")
  {
    term_echo("PRIVMSG: DESTINATION NOT SPECIFIED: nick=\"$nick\", msg=\"$msg\"");
    return;
  }
  if ($msg=="")
  {
    term_echo("PRIVMSG: NO TEXT TO SEND: nick=\"$nick\", destination=\"$destination\"");
    return;
  }
  $msg=substr($msg,0,MAX_MSG_LENGTH);
  if (isset($dest_overrides[$nick][$destination])==True)
  {
    $data=":".get_bot_nick()." PRIVMSG ".$dest_overrides[$nick][$destination]." :$msg";
    rawmsg($data);
  }
  else
  {
    if (substr($destination,0,1)=="#")
    {
      $data=":".get_bot_nick()." PRIVMSG $destination :$msg";
      rawmsg($data);
    }
    else
    {
      $data=":".get_bot_nick()." PRIVMSG $nick :$msg";
      rawmsg($data);
    }
  }
}

#####################################################################################################

function rawmsg($msg,$obfuscate=False)
{
  global $socket;
  global $throttle_time;
  global $rawmsg_times;
  if ($throttle_time!==False)
  {
    $delta=microtime(True)-$throttle_time;
    if ($delta>THROTTLE_LOCKOUT_TIME)
    {
      $throttle_time=False;
    }
    else
    {
      term_echo("*** REFUSED OUTGOING MESSAGE DUE TO SERVER THROTTLING: $msg");
      return;
    }
  }
  $n=count($rawmsg_times);
  if ($n>0)
  {
    $last=$rawmsg_times[$n-1];
    $dt=microtime(True)-$last;
    if ($dt>THROTTLE_LOCKOUT_TIME)
    {
      $rawmsg_times=array();
    }
    else
    {
      if ($n>=RAWMSG_TIME_COUNT)
      {
        usleep(ANTI_FLOOD_DELAY*1e6);
      }
    }
  }
  fwrite($socket,$msg."\n");
  $rawmsg_times[]=microtime(True);
  while (count($rawmsg_times)>RAWMSG_TIME_COUNT)
  {
    array_shift($rawmsg_times);
  }
  if ($obfuscate==False)
  {
    handle_data($msg."\n",True,False,True);
  }
  else
  {
    term_echo("RAWMSG: (obfuscated)");
  }
}

#####################################################################################################

?>
