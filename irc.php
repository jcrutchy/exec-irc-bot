<?php

# gpl2
# by crutchy
# 14-june-2014

# irc.php

# TODO: per channel alias bans
# TODO: random timer interval

#####################################################################################################

define("NICK","exec");
define("PASSWORD",file_get_contents("../pwd/".NICK));
define("EXEC_FILE","exec.txt");
define("BUCKETS_FILE","../data/buckets");
define("EXEC_DELIM","|");
define("STDOUT_PREFIX_RAW","IRC_RAW"); # if script stdout is prefixed with this, will be output to irc socket (raw)
define("STDOUT_PREFIX_MSG","IRC_MSG"); # if script stdout is prefixed with this, will be output to irc socket as privmsg
define("STDOUT_PREFIX_TERM","TERM"); # if script stdout is prefixed with this, will be output to the terminal only
#define("INIT_CHAN_LIST","#civ,#soylent,##,#test,#*,#,#>,#shell,#~,#derp,#wiki,#sublight,#help,#exec,#1,#0,#/,#staff,#dev,#editorial,#frontend,#pipedot,#rss-bot,#style");
define("INIT_CHAN_LIST","#exec,#civ");
define("MAX_MSG_LENGTH",800);
define("IRC_HOST","irc.sylnt.us");
#define("IRC_HOST","localhost");
define("IRC_PORT","6667");
define("IGNORE_TIME",20); # seconds (flood control)
define("DELTA_TOLERANCE",1.5); # seconds (flood control)
define("TEMPLATE_DELIM","%%");
define("CHANNEL_MONITOR","#exec");
define("LOG_PATH","/var/www/irciv.us.to/exec_logs/");

# stdout bot directives
define("DIRECTIVE_QUIT","<<quit>>");

# reserved aliases
define("ALIAS_ALL","*");
define("ALIAS_INIT","<init>");
define("ALIAS_QUIT","<quit>");

# bucket messages (buckets is an array filled by pipes)
define("BUCKET_GET","BUCKET_GET");
define("BUCKET_SET","BUCKET_SET");
define("BUCKET_UNSET","BUCKET_UNSET");

# internal command aliases (can also use in exec file with alias locking, but that would be just weird)
define("CMD_ADMIN_QUIT","~q");
define("CMD_ADMIN_RESTART","~restart");
define("CMD_ADMIN_RELOAD","~reload");
define("CMD_ADMIN_DEST_OVERRIDE","~dest-override");
define("CMD_ADMIN_DEST_CLEAR","~dest-clear");
define("CMD_ADMIN_BUCKETS_DUMP","~buckets-dump"); # dump buckets to terminal
define("CMD_ADMIN_BUCKETS_SAVE","~buckets-save"); # save buckets to file
define("CMD_ADMIN_BUCKETS_LOAD","~buckets-load"); # load buckets from file
define("CMD_ADMIN_BUCKETS_FLUSH","~buckets-flush"); # re-initialize buckets
define("CMD_ADMIN_BUCKETS_LIST","~buckets-list"); # output list of set bucket indexes to the terminal
define("CMD_ADMIN_TOGGLE_MONITOR","~monitor");
define("CMD_LOCK","~lock");
define("CMD_UNLOCK","~unlock");
define("CMD_LIST","~list");
define("CMD_LIST_AUTH","~list-auth");

# exec file shell command templates (replaced by the bot with actual values before executing)
define("TEMPLATE_TRAILING","trailing");
define("TEMPLATE_NICK","nick");
define("TEMPLATE_DESTINATION","dest");
define("TEMPLATE_START","start");
define("TEMPLATE_ALIAS","alias");
define("TEMPLATE_DATA","data");
define("TEMPLATE_CMD","cmd");
define("TEMPLATE_PARAMS","params");

set_time_limit(0); # script needs to run for indefinite time (overrides setting in php.ini)
ini_set("memory_limit","128M");
ini_set("display_errors","on"); # output errors to stdout

define("START_TIME",microtime(True)); # used for %%start%% template

$alias_locks=array(); # optionally stores an alias for each nick, which then treats every privmsg by that nick as being prefixed by the set alias
$handles=array(); # stores executed process information
$time_deltas=array(); # keeps track of how often nicks call an alias (used for flood control)
$buckets=array(); # common place for scripts to store stuff
$dest_overrides=array(); # optionally stores a destination for each nick, which treats every privmsg by that nick as having the set destination

$admin_accounts=array("crutchy");
$admin_data="";
$admin_nick="";

$monitor_enabled=False;

$throttle_flag=False;
$rawmsg_times=array();

$admin_commands=array(
  CMD_ADMIN_QUIT,
  CMD_ADMIN_RESTART,
  CMD_ADMIN_RELOAD,
  CMD_ADMIN_DEST_OVERRIDE,
  CMD_ADMIN_DEST_CLEAR,
  CMD_ADMIN_BUCKETS_DUMP,
  CMD_ADMIN_BUCKETS_SAVE,
  CMD_ADMIN_BUCKETS_LOAD,
  CMD_ADMIN_BUCKETS_FLUSH,
  CMD_ADMIN_BUCKETS_LIST,
  CMD_ADMIN_TOGGLE_MONITOR);

$exec_list=exec_load();
if ($exec_list===False)
{
  term_echo("error loading exec");
  return;
}

init();
$socket=fsockopen(IRC_HOST,IRC_PORT);
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

function init()
{
  $items=parse_data("INIT");
  process_scripts($items,ALIAS_INIT);
}

#####################################################################################################

function get_list($items)
{
  global $exec_list;
  $msg="~list ~list-auth ~lock ~unlock";
  privmsg($items["destination"],$items["nick"],$msg);
  $msg="";
  var_dump($exec_list);
  foreach ($exec_list as $alias => $data)
  {
    if ((count($data["accounts"])==0) and (strlen($alias)<=20) and ($alias<>ALIAS_ALL) and ($alias<>ALIAS_INIT) and ($alias<>ALIAS_QUIT))
    {
      if ($msg<>"")
      {
        $msg=$msg." ";
      }
      $msg=$msg.$alias;
    }
  }
  privmsg($items["destination"],$items["nick"],$msg);
}

#####################################################################################################

function get_list_auth($items)
{
  global $exec_list;
  $msg="~q ~reload ~dest-override ~dest-clear ~buckets-dump ~buckets-save ~buckets-load ~buckets-flush ~buckets-list ~monitor ~restart";
  privmsg($items["destination"],$items["nick"],$msg);
  $msg="";
  foreach ($exec_list as $alias => $data)
  {
    if ((count($data["accounts"])>0) and (strlen($alias)<=20) and ($alias<>ALIAS_ALL) and ($alias<>ALIAS_INIT) and ($alias<>ALIAS_QUIT))
    {
      if ($msg<>"")
      {
        $msg=$msg." ";
      }
      $msg=$msg.$alias;
    }
  }
  privmsg($items["destination"],$items["nick"],$msg);
}

#####################################################################################################

function log_data($data)
{
  $filename=LOG_PATH.date("Ymd",time()).".txt";
  $line="<<".date("Y-m-d H:i:s",microtime(True)).">> ".rtrim($data)."\n";
  file_put_contents($filename,$line,FILE_APPEND);
}

#####################################################################################################

function handle_process($handle)
{
  handle_stdout($handle);
  handle_stderr($handle);
  $meta=stream_get_meta_data($handle["pipe_stdout"]);
  if ($meta["eof"]==True)
  {
    proc_close($handle["process"]);
    if ($handle["alias"]<>"*")
    {
      term_echo("process terminated normally");
    }
    return False;
  }
  if ($handle["timeout"]>=0)
  {
    if ((microtime(True)-$handle["start"])>$handle["timeout"])
    {
      proc_close($handle["process"]);
      privmsg($handle["destination"],$handle["nick"],"error: command timed out");
      return False;
    }
  }
  return True;
}

#####################################################################################################

function handle_stdout($handle)
{
  if (is_resource($handle["pipe_stdout"])==False)
  {
    return;
  }
  $buf=fgets($handle["pipe_stdout"]);
  if ($buf===False)
  {
    return;
  }
  if (trim($buf)==DIRECTIVE_QUIT)
  {
    doquit();
  }
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  if ($handle["auto_privmsg"]==1)
  {
    privmsg($handle["destination"],$handle["nick"],$msg);
  }
  else
  {
    $parts=explode(" ",$msg);
    $prefix=$parts[0];
    array_shift($parts);
    $prefix_msg=implode(" ",$parts);
    if ($prefix==STDOUT_PREFIX_RAW)
    {
      rawmsg($prefix_msg);
    }
    elseif ($prefix==STDOUT_PREFIX_MSG)
    {
      privmsg($handle["destination"],$handle["nick"],$prefix_msg);
    }
    elseif ($prefix==STDOUT_PREFIX_TERM)
    {
      term_echo($prefix_msg);
    }
  }
  if (handle_buckets($msg,$handle)==False)
  {
    handle_data($buf);
  }
}

#####################################################################################################

function handle_stderr($handle)
{
  if (is_resource($handle["pipe_stderr"])==False)
  {
    return;
  }
  $buf=fgets($handle["pipe_stderr"]);
  if ($buf===False)
  {
    return;
  }
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  term_echo($msg);
}

#####################################################################################################

function handle_stdin($handle,$data)
{
  if (is_resource($handle["pipe_stdin"])==False)
  {
    return False;
  }
  $lines=str_split($data,1024);
  $lines[]="<<EOF>>";
  for ($i=0;$i<count($lines);$i++)
  {
    $result=fwrite($handle["pipe_stdin"],$lines[$i]."\n");
    if ($result===False)
    {
      return False;
    }
  }
  return True;
}

#####################################################################################################

function handle_buckets($data,$handle)
{
  global $buckets;
  $items=parse_data($data);
  if ($items===False)
  {
    return False;
  }
  $trailing=$items["trailing"];
  switch ($items["cmd"])
  {
    case BUCKET_GET:
      $index=$trailing;
      if (isset($buckets[$index])==True)
      {
        $size=round(strlen($buckets[$index])/1024,1)."kb";
        $result=handle_stdin($handle,$buckets[$index]);
        if ($result===False)
        {
          term_echo("BUCKET_GET [$index]: ERROR WRITING BUCKET DATA TO STDIN ($size)");
        }
        else
        {
          term_echo("BUCKET_GET [$index]: SUCCESS ($size)");
        }
      }
      else
      {
        handle_stdin($handle,"\n");
        term_echo("BUCKET_GET [$index]: BUCKET NOT SET");
      }
      return True;
    case BUCKET_SET:
      $parts=explode(" ",$trailing);
      if (count($parts)<2)
      {
        term_echo("BUCKET_SET: INVALID TRAILING: '$trailing'");
      }
      else
      {
        $index=$parts[0];
        unset($parts[0]);
        $trailing=implode(" ",$parts);
        $buckets[$index]=$trailing;
        term_echo("BUCKET_SET [$index]: SUCCESS");
      }
      return True;
    case BUCKET_UNSET:
      $index=$trailing;
      if (isset($buckets[$index])==True)
      {
        unset($buckets[$index]);
        term_echo("BUCKET_UNSET [$index]: SUCCESS");
      }
      else
      {
        term_echo("BUCKET_UNSET [$index]: BUCKET NOT SET");
      }
      return True;
  }
}

#####################################################################################################

function buckets_dump($items)
{
  global $buckets;
  term_echo("############ BEGIN BUCKETS DUMP ############");
  var_dump($buckets);
  term_echo("############# END BUCKETS DUMP #############");
}

#####################################################################################################

function buckets_save($items)
{
  global $buckets;
  $data=serialize($buckets);
  if ($data===False)
  {
    privmsg($items["destination"],$items["nick"],"error serializing buckets");
    return;
  }
  if (file_put_contents(BUCKETS_FILE,$data)===False)
  {
    privmsg($items["destination"],$items["nick"],"error saving buckets file");
    return;
  }
  privmsg($items["destination"],$items["nick"],"successfully saved buckets file");
}

#####################################################################################################

function buckets_load($items)
{
  global $buckets;
  $data=file_get_contents(BUCKETS_FILE);
  if ($data===False)
  {
    privmsg($items["destination"],$items["nick"],"error reading buckets file");
    return;
  }
  $data=unserialize($data);
  if ($data===False)
  {
    privmsg($items["destination"],$items["nick"],"error unserializing buckets file");
    return;
  }
  $buckets=$data;
  privmsg($items["destination"],$items["nick"],"successfully loaded buckets file");
}

#####################################################################################################

function buckets_flush($items)
{
  global $buckets;
  $buckets=array();
  privmsg($items["destination"],$items["nick"],"buckets flushed");
}

#####################################################################################################

function buckets_list($items)
{
  global $buckets;
  privmsg($items["destination"],$items["nick"],"bucket list output to terminal");
  foreach ($buckets as $index => $data)
  {
    term_echo($index);
  }
  privmsg($items["destination"],$items["nick"],"bucket count: ".count($buckets));
}

#####################################################################################################

function handle_socket($socket)
{
  $data=fgets($socket);
  if ($data!==False)
  {
    if (pingpong($data)==False)
    {
      handle_data($data,True);
    }
  }
}

#####################################################################################################

function has_account_list($alias)
{
  global $exec_list;
  if (isset($exec_list[$alias])==True)
  {
    if (count($exec_list[$alias]["accounts"])>0)
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function handle_data($data,$is_sock=False)
{
  global $alias_locks;
  global $dest_overrides;
  global $admin_accounts;
  global $admin_data;
  global $admin_nick;
  global $admin_commands;
  global $exec_list;
  global $monitor_enabled;
  global $throttle_flag;
  echo $data;
  log_data($data);
  $items=parse_data($data);
  if ($items!==False)
  {
    if (($monitor_enabled==True) and (is_numeric($items["cmd"])==False) and ($is_sock==True))
    {
      rawmsg(":".NICK." PRIVMSG ".CHANNEL_MONITOR." :>> $data\n",False);
    }
    if ($items["destination"]==CHANNEL_MONITOR)
    {
      return;
    }
    if (($items["prefix"]==IRC_HOST) and (strpos(strtolower($items["trailing"]),"throttled due to flooding")!==False))
    {
      $throttle_flag=True;
      return;
    }
    if ($items["cmd"]==330) # is logged in as
    {
      $parts=explode(" ",$items["params"]);
      if (($admin_data<>"") and (count($parts)==3) and ($parts[0]==NICK))
      {
        $nick=$parts[1];
        $account=$parts[2];
        $admin_items=parse_data($admin_data);
        $args=explode(" ",$admin_items["trailing"]);
        $cmd=$args[0];
        if ($admin_items["nick"]==$nick)
        {
          if (has_account_list($cmd)==True)
          {
            if ((in_array($account,$exec_list[$cmd]["accounts"])==False) and (in_array($account,$admin_accounts)==False) and ($account<>NICK))
            {
              term_echo("authentication failure: \"$account\" attempted to run \"$cmd\" but is not in exec line account list");
              $admin_nick="";
              $admin_data="";
              return;
            }
          }
          else
          {
            if (in_array($account,$admin_accounts)==False)
            {
              term_echo("authentication failure: \"$account\" attempted to run \"$cmd\" but is not in admin account list");
              $admin_nick="";
              $admin_data="";
              return;
            }
          }
          $admin_nick=$nick;
          $items=$admin_items;
          $args=explode(" ",$items["trailing"]);
        }
        else
        {
          $admin_nick="";
          $admin_data="";
          return;
        }
      }
      else
      {
        $admin_nick="";
        $admin_data="";
      }
    }
    if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
    {
      dojoin(INIT_CHAN_LIST);
      $monitor_enabled=True;
      return;
    }
    if (($items["cmd"]=="NOTICE") and ($items["nick"]=="NickServ") and ($items["trailing"]=="You have 60 seconds to identify to your nickname before it is changed."))
    {
      rawmsg("NickServ IDENTIFY ".PASSWORD,False);
      return;
    }
    $args=explode(" ",$items["trailing"]);
    if ((in_array($args[0],$admin_commands)==True) or (has_account_list($args[0])==True))
    {
      if ($admin_nick==$items["nick"])
      {
        $admin_nick="";
        $admin_data="";
      }
      else
      {
        term_echo("authenticating admin");
        $admin_data=$items["data"];
        rawmsg("WHOIS ".$items["nick"]);
        return;
      }
    }
    switch ($args[0])
    {
      case CMD_ADMIN_QUIT:
        if (count($args)==1)
        {
          process_scripts($items,ALIAS_QUIT);
        }
        break;
      case CMD_LIST:
        if (check_nick($items,CMD_LOCK)==True)
        {
          if (count($args)==1)
          {
            get_list($items);
          }
        }
        break;
      case CMD_LIST_AUTH:
        if (check_nick($items,CMD_LOCK)==True)
        {
          if (count($args)==1)
          {
            get_list_auth($items);
          }
        }
        break;
      case CMD_LOCK:
        if (check_nick($items,CMD_LOCK)==True)
        {
          if (count($args)==2)
          {
            $alias_locks[$items["nick"]][$items["destination"]]=$args[1];
            privmsg($items["destination"],$items["nick"],"alias \"".$args[1]."\" locked for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
          }
          else
          {
            privmsg($items["destination"],$items["nick"],"syntax: ".CMD_LOCK." <alias>");
          }
        }
        break;
      case CMD_UNLOCK:
        if ((check_nick($items,CMD_UNLOCK)==True) and (isset($alias_locks[$items["nick"]][$items["destination"]])==True))
        {
          privmsg($items["destination"],$items["nick"],"alias \"".$alias_locks[$items["nick"]][$items["destination"]]."\" unlocked for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
          unset($alias_locks[$items["nick"]][$items["destination"]]);
        }
        break;
      case CMD_ADMIN_TOGGLE_MONITOR:
        if (count($args)==1)
        {
          if ($monitor_enabled==True)
          {
            $monitor_enabled=False;
            privmsg($items["destination"],$items["nick"],"exec incoming data monitor disabled");
          }
          else
          {
            $monitor_enabled=True;
            privmsg($items["destination"],$items["nick"],"exec incoming data monitor enabled");
          }
        }
        break;
      case CMD_ADMIN_DEST_OVERRIDE:
        if (count($args)==2)
        {
          privmsg($items["destination"],$items["nick"],"destination override \"".$args[1]."\" set for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
          $dest_overrides[$items["nick"]][$items["destination"]]=$args[1];
        }
        else
        {
          privmsg($items["destination"],$items["nick"],"syntax: ".CMD_ADMIN_DEST_OVERRIDE." <dest>");
        }
        break;
      case CMD_ADMIN_DEST_CLEAR:
        if (isset($dest_overrides[$items["nick"]][$items["destination"]])==True)
        {
          $override=$dest_overrides[$items["nick"]][$items["destination"]];
          unset($dest_overrides[$items["nick"]][$items["destination"]]);
          privmsg($items["destination"],$items["nick"],"destination override \"$override\" cleared for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
        }
        break;
      case CMD_ADMIN_RELOAD:
        if (count($args)==1)
        {
          if (exec_load()===False)
          {
            privmsg($items["destination"],$items["nick"],"error reloading exec file");
            doquit();
          }
          else
          {
            privmsg($items["destination"],$items["nick"],"successfully reloaded exec file");
          }
        }
        break;
      case CMD_ADMIN_BUCKETS_DUMP:
        if (count($args)==1)
        {
          buckets_dump($items);
        }
        break;
      case CMD_ADMIN_BUCKETS_SAVE:
        if (count($args)==1)
        {
          buckets_save($items);
        }
        break;
      case CMD_ADMIN_BUCKETS_LOAD:
        if (count($args)==1)
        {
          buckets_load($items);
        }
        break;
      case CMD_ADMIN_BUCKETS_FLUSH:
        if (count($args)==1)
        {
          buckets_flush($items);
        }
        break;
      case CMD_ADMIN_BUCKETS_LIST:
        if (count($args)==1)
        {
          buckets_list($items);
        }
        break;
      case CMD_ADMIN_RESTART:
        if (count($args)==1)
        {
          doquit(True);
        }
        break;
      default:
        process_scripts($items); # execute scripts occurring for a specific alias
        process_scripts($items,ALIAS_ALL); # process scripts occuring for every line (* alias)
    }
  }
}

#####################################################################################################

function rawmsg($msg,$privmsg=True)
{
  global $socket;
  global $throttle_flag;
  global $rawmsg_times;
  $flood_count=6; # messages to allow through without any delays
  if (count($rawmsg_times)>1)
  {
    $throttle_time=10; # sec
    $last=$rawmsg_times[count($rawmsg_times)-1];
    $dt=microtime(True)-$last;
    if (($throttle_flag==True) and ($dt<$throttle_time))
    {
      return;
    }
    $meta=stream_get_meta_data($socket);
    if ($meta["unread_bytes"]>0)
    {
      term_echo("rawmsg function: socket data unread: ".$meta["unread_bytes"]." bytes");
    }
    $throttle_flag=False;
    $flood_time=0.6; # sec
    $dt=$rawmsg_times[0]-$last;
    if ($dt<$flood_time)
    {
      usleep($flood_time*1e6);
      $rawmsg_times=array();
    }
    elseif ($dt>$throttle_time)
    {
      $rawmsg_times=array();
    }
  }
  fputs($socket,$msg."\n");
  $rawmsg_times[]=microtime(True);
  while (count($rawmsg_times)>$flood_count)
  {
    array_shift($rawmsg_times);
  }
  if ($privmsg==True)
  {
    # eventually pipe this to a separate monitoring terminal stdout
    rawmsg(":".NICK." PRIVMSG ".CHANNEL_MONITOR." :<< $msg",False);
  }
}

#####################################################################################################

function exec_load()
{
  global $exec_list;
  $exec_list=array();
  $data=file_get_contents(EXEC_FILE);
  if ($data===False)
  {
    return False;
  }
  $data=explode("\n",$data);
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    if ($line=="")
    {
      continue;
    }
    if (substr($line,0,1)=="#")
    {
      continue;
    }
    $parts=explode(EXEC_DELIM,$line);
    if (count($parts)<7)
    {
      continue;
    }
    $alias=trim($parts[0]);
    $timeout=trim($parts[1]); # seconds
    $repeat=trim($parts[2]); # seconds
    $auto=trim($parts[3]); # auto privmsg (0 = no, 1 = yes)
    $empty=trim($parts[4]); # empty msg permitted (0 = no, 1 = yes)
    $accounts=array();
    $accounts_str=trim($parts[5]);
    if ($accounts_str<>"")
    {
      $accounts=explode(",",$accounts_str); # comma-delimited list of NickServ accounts authorised to run script (or empty)
    }
    for ($j=0;$j<=5;$j++)
    {
      array_shift($parts);
    }
    $cmd=trim(implode("|",$parts)); # shell command
    if (($alias=="") or (is_numeric($timeout)==False) or (is_numeric($repeat)==False) or (($auto<>"0") and ($auto<>"1")) or (($empty<>"0") and ($empty<>"1")) or ($cmd==""))
    {
      continue;
    }
    $exec_list[$alias]["timeout"]=$timeout;
    $exec_list[$alias]["repeat"]=$repeat;
    $exec_list[$alias]["auto"]=$auto;
    $exec_list[$alias]["empty"]=$empty;
    $exec_list[$alias]["accounts"]=$accounts;
    $exec_list[$alias]["cmd"]=$cmd;
  }
  return $exec_list;
}

#####################################################################################################

function doquit($restart=False)
{
  global $handles;
  global $socket;
  global $argv;
  $n=count($handles);
  for ($i=0;$i<$n;$i++)
  {
    if (is_resource($handles[$i]["process"])==True)
    {
      proc_close($handles[$i]["process"]);
    }
  }
  rawmsg("NickServ LOGOUT");
  rawmsg("QUIT");
  fclose($socket);
  term_echo("QUITTING SCRIPT");
  if ($restart==True)
  {
    pcntl_exec($_SERVER["_"],$argv);
  }
  die();
}

#####################################################################################################

function dojoin($chanlist)
{
  rawmsg("JOIN $chanlist");
}

#####################################################################################################

function pingpong($data)
{
  $parts=explode(" ",$data);
  if (count($parts)>1)
  {
    if ($parts[0]=="PING")
    {
      rawmsg("PONG ".$parts[1],False);
      return True;
    }
  }
  return False;
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[31m$msg\033[0m\n";
  log_data($msg);
}

#####################################################################################################

function parse_data($data)
{
  # :<prefix> <command> <params> :<trailing>
  # the only required part of the message is the command name
  if ($data=="")
  {
    return False;
  }
  $sub=trim($data);
  $result["microtime"]=microtime(True);
  $result["time"]=date("Y-m-d H:i:s",$result["microtime"]);
  $result["data"]=$sub;
  $result["prefix"]=""; # if there is no prefix, then the source of the message is the server for the current connection (such as for PING)
  $result["params"]="";
  $result["trailing"]="";
  $result["nick"]="";
  $result["user"]="";
  $result["hostname"]="";
  $result["destination"]=""; # for privmsg = <params>
  if (substr($sub,0,1)==":") # prefix found
  {
    $i=strpos($sub," ");
    $result["prefix"]=substr($sub,1,$i-1);
    $sub=substr($sub,$i+1);
  }
  $i=strpos($sub," :");
  if ($i!==False) # trailing found
  {
    $result["trailing"]=trim(substr($sub,$i+2));
    $sub=substr($sub,0,$i);
  }
  $i=strpos($sub," ");
  if ($i!==False) # params found
  {
    $result["params"]=substr($sub,$i+1);
    $sub=substr($sub,0,$i);
  }
  $result["cmd"]=$sub;
  if ($result["cmd"]=="")
  {
    return False;
  }
  $result["destination"]=$result["params"];
  if ($result["prefix"]<>"")
  {
    # prefix format: nick!user@hostname
    $prefix=$result["prefix"];
    $i=strpos($prefix,"!");
    if ($i===False)
    {
      $result["nick"]=$prefix;
    }
    else
    {
      $result["nick"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $i=strpos($prefix,"@");
      $result["user"]=substr($prefix,0,$i);
      $prefix=substr($prefix,$i+1);
      $result["hostname"]=$prefix;
    }
  }
  return $result;
}

#####################################################################################################

function privmsg($destination,$nick,$msg)
{
  global $dest_overrides;
  if ($destination=="")
  {
    term_echo("PRIVMSG: DESTINATION NOT SPECIFIED");
    return;
  }
  if ($msg=="")
  {
    term_echo("PRIVMSG: NO TEXT TO SEND");
    return;
  }
  $msg=substr($msg,0,MAX_MSG_LENGTH);
  if (isset($dest_overrides[$nick][$destination])==True)
  {
    $data=":".NICK." PRIVMSG ".$dest_overrides[$nick][$destination]." :$msg";
    rawmsg($data);
  }
  else
  {
    if (substr($destination,0,1)=="#")
    {
      $data=":".NICK." PRIVMSG $destination :$msg";
      rawmsg($data);
    }
    else
    {
      $data=":".NICK." PRIVMSG $nick :$msg";
      rawmsg($data);
    }
  }
  term_echo($msg);
}

#####################################################################################################

function process_scripts($items,$reserved="")
{
  global $handles;
  global $exec_list;
  global $alias_locks;
  $nick=trim($items["nick"]);
  $destination=trim($items["destination"]);
  $data=trim($items["data"]);
  $cmd=trim($items["cmd"]);
  $trailing=$items["trailing"];
  if ($reserved=="")
  {
    if (isset($alias_locks[$nick][$destination])==True)
    {
      $alias=$alias_locks[$nick][$destination];
    }
    else
    {
      $parts=explode(" ",$items["trailing"]);
      $alias=strtolower(trim($parts[0]));
      if (isset($exec_list[$alias])==False)
      {
        return;
      }
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
    }
    if (($alias==ALIAS_ALL) or ($alias==ALIAS_INIT) or ($alias==ALIAS_QUIT))
    {
      return;
    }
  }
  else
  {
    $alias=$reserved;
  }
  if (isset($exec_list[$alias])==False)
  {
    return;
  }
  if (check_nick($items,$alias)==False)
  {
    return;
  }
  if (($exec_list[$alias]["empty"]==0) and ($trailing=="") and ($destination<>"") and ($nick<>""))
  {
    privmsg($destination,$nick,"alias \"$alias\" requires additional trailing argument");
    return;
  }
  $template=$exec_list[$alias]["cmd"];
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TRAILING.TEMPLATE_DELIM,escapeshellarg($trailing),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_NICK.TEMPLATE_DELIM,escapeshellarg($nick),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DESTINATION.TEMPLATE_DELIM,escapeshellarg($destination),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_START.TEMPLATE_DELIM,escapeshellarg(START_TIME),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_ALIAS.TEMPLATE_DELIM,escapeshellarg($alias),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DATA.TEMPLATE_DELIM,escapeshellarg($data),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_CMD.TEMPLATE_DELIM,escapeshellarg($cmd),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_PARAMS.TEMPLATE_DELIM,escapeshellarg($items["params"]),$template);
  $command="exec ".$template;
  $command=$template;
  $cwd=NULL;
  $env=NULL;
  $descriptorspec=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
  if ($alias<>"*")
  {
    term_echo($command);
  }
  $process=proc_open($command,$descriptorspec,$pipes,$cwd,$env);
  $start=microtime(True);
  $handles[]=array(
    "process"=>$process,
    "command"=>$command,
    "pipe_stdin"=>$pipes[0],
    "pipe_stdout"=>$pipes[1],
    "pipe_stderr"=>$pipes[2],
    "alias"=>$alias,
    "template"=>$exec_list[$alias]["cmd"],
    "allow_empty"=>$exec_list[$alias]["empty"],
    "timeout"=>$exec_list[$alias]["timeout"],
    "repeat"=>$exec_list[$alias]["repeat"],
    "auto_privmsg"=>$exec_list[$alias]["auto"],
    "start"=>$start,
    "nick"=>$items["nick"],
    "destination"=>$items["destination"]);
  stream_set_blocking($pipes[1],0);
  stream_set_blocking($pipes[2],0);
}

#####################################################################################################

function check_nick($items,$alias)
{
  global $time_deltas;
  if ($items["cmd"]<>"PRIVMSG")
  {
    return True;
  }
  if (($items["nick"]==NICK) or ($alias=="*"))
  {
    return True;
  }
  $lnick=strtolower($items["nick"]);
  if (isset($time_deltas[$lnick][$alias]["time"])==False)
  {
    $time_deltas[$lnick][$alias]["time"]=microtime(True);
    $time_deltas[$lnick][$alias]["last_delta"]=0;
    return True;
  }
  $last_delta=$time_deltas[$lnick][$alias]["last_delta"];
  $this_delta=microtime(True)-$time_deltas[$lnick][$alias]["time"];
  $time_deltas[$lnick][$alias]["last_delta"]=$this_delta;
  if (abs($last_delta-$this_delta)<DELTA_TOLERANCE)
  {
    $time_deltas[$lnick][$alias]["last"]["ignore_start"]=microtime(True);
    term_echo("ALIAS \"$alias\" BY NICK \"".$items["nick"]."\" IGNORED FOR ".IGNORE_TIME." SECONDS");
  }
  else
  {
    if (isset($time_deltas[$lnick][$alias]["last"]["ignore_start"])==True)
    {
      if ((microtime(True)-$time_deltas[$lnick][$alias]["last"]["ignore_start"])>=IGNORE_TIME)
      {
        unset($time_deltas[$lnick][$alias]["last"]["ignore_start"]);
        term_echo("IGNORE CLEARED FOR ALIAS \"$alias\" BY NICK \"".$items["nick"]."\"");
      }
    }
  }
  if (isset($time_deltas[$lnick][$alias]["last"]["ignore_start"])==True)
  {
    return False;
  }
  return True;
}

#####################################################################################################

function process_timed_execs()
{
  global $exec_list;
  foreach ($exec_list as $alias => $exec_data)
  {
    if ($exec_data["repeat"]<=0)
    {
      continue;
    }
    $time_set=False;
    if (isset($exec_data["repeat_time"])==True)
    {
      $time_set=True;
      $delta=microtime(True)-$exec_data["repeat_time"];
      if ($delta<$exec_data["repeat"])
      {
        continue;
      }
    }
    $data=":exec NOTICE :$alias";
    $items=parse_data($data);
    if ($items===False)
    {
      continue;
    }
    $exec_list[$alias]["repeat_time"]=microtime(True);
    if ($time_set==True)
    {
      process_scripts($items);
    }
  }
}

#####################################################################################################

?>
