<?php

# gpl2
# by crutchy
# 24-april-2014

#####################################################################################################

define("NICK","exec");
define("PASSWORD",file_get_contents("../pwd/".NICK));
define("EXEC_FILE","exec");
define("EXEC_DELIM","|");
define("STDOUT_PREFIX_RAW","IRC_RAW"); # if script stdout is prefixed with this, will be output to irc socket (raw)
define("STDOUT_PREFIX_MSG","IRC_MSG"); # if script stdout is prefixed with this, will be output to irc socket as privmsg
define("INIT_CHAN_LIST","#~");
define("MAX_MSG_LENGTH",800);
define("IRC_HOST","irc.sylnt.us");
define("IRC_PORT","6667");
define("IGNORE_TIME",20); # seconds
define("DELTA_TOLERANCE",1.5); # seconds
define("TEMPLATE_DELIM","%%");

# internal command aliases (can't use in exec file)
define("CMD_QUIT","~q");
define("CMD_LOCK","~lock");
define("CMD_UNLOCK","~unlock");
define("CMD_RELOAD","~reload");

# exec file shell command templates (replaced by the bot with actual values before executing)
define("TEMPLATE_TRAILING","trailing");
define("TEMPLATE_NICK","nick");
define("TEMPLATE_DESTINATION","dest");
define("TEMPLATE_START","start");
define("TEMPLATE_ALIAS","alias");
define("TEMPLATE_DATA","data");
define("TEMPLATE_CMD","cmd");
define("TEMPLATE_EXEC","exec");

set_time_limit(0); # script needs to run for indefinite time (overrides setting in php.ini)
ini_set("display_errors","on"); # output errors to stdout

define("START_TIME",microtime(True)); # used for %%start%% template

$exec_list=array(); # stores exec file data
$alias_locks=array(); # optionally stores an alias for each nick, which then treats every privmsg by that nick as being prefixed by the set alias
$handles=array(); # stores executed process information
$time_deltas=array(); # keeps track of how often nicks call an alias (used for flood control)

$admin_nicks=array("crutchy");

if (exec_load($exec_list)==False)
{
  term_echo("error loading exec");
  return;
}

$socket=fsockopen(IRC_HOST,IRC_PORT);
stream_set_blocking($socket,0);
rawmsg("NICK ".NICK);
rawmsg("USER ".NICK." hostname servername :".NICK);

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
  process_socket($socket);
  usleep(0.1e6); # 0.1 second
}

#####################################################################################################

function handle_process($handle)
{
  if (is_resource($handle["process"])==False)
  {
    return False;
  }
  process_stdout($handle);
  process_stderr($handle);
  $proc_info=proc_get_status($handle["process"]);
  if ($proc_info["running"]==False)
  {
    proc_close($handle["process"]);
    if ($handle["alias"]<>"*")
    {
      term_echo("process terminated normally");
    }
    return False;
  }
  if ((microtime(True)-$handle["start"])>$handle["timeout"])
  {
    proc_close($handle["process"]);
    privmsg($handle["destination"],$handle["nick"],"error: command timed out");
    return False;
  }
  return True;
}

#####################################################################################################

function process_stdout($handle)
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
    else
    {
      term_echo($msg);
    }
  }
}

#####################################################################################################

function process_stderr($handle)
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

function process_socket($socket)
{
  global $admin_nicks;
  $data=fgets($socket);
  if ($data===False)
  {
    return;
  }
  if (pingpong($data)==True)
  {
    return;
  }
  echo $data;
  $items=parse_data($data);
  if ($items!==False)
  {
    $args=explode(" ",$items["trailing"]);
    if (($items["trailing"]==CMD_QUIT) and (in_array($items["nick"],$admin_nicks)==True))
    {
      doquit();
    }
    elseif (($args[0]==CMD_LOCK) and (check_nick($items,CMD_LOCK)==True))
    {
      if (count($args)==2)
      {
        $alias_locks[$items["nick"]]=$args[1];
        privmsg($items["destination"],$items["nick"],"alias \"".$args[1]."\" locked for nick \"".$items["nick"]."\"");
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"syntax: ~lock <alias>");
      }
    }
    elseif (($items["trailing"]==CMD_UNLOCK) and (check_nick($items,CMD_UNLOCK)==True) and (isset($alias_locks[$items["nick"]])==True))
    {
      privmsg($items["destination"],$items["nick"],"alias \"".$alias_locks[$items["nick"]]."\" unlocked for nick \"".$items["nick"]."\"");
      unset($alias_locks[$items["nick"]]);
    }
    elseif (($items["trailing"]==CMD_RELOAD) and (check_nick($items["nick"],CMD_RELOAD)==True) and (in_array($items["nick"],$admin_nicks)==True))
    {
      if (exec_load($exec_list)==True)
      {
        privmsg($items["destination"],$items["nick"],"successfully reloaded exec");
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"error reloading exec");
      }
    }
    elseif ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
    {
      dojoin(INIT_CHAN_LIST);
    }
    elseif (($items["cmd"]=="NOTICE") and ($items["nick"]=="NickServ") and ($items["trailing"]=="You have 60 seconds to identify to your nickname before it is changed."))
    {
      rawmsg("NickServ IDENTIFY ".PASSWORD);
    }
    else
    {
      process_scripts($items); # execute scripts occurring for a specific alias
      process_scripts($items,True); # process scripts occuring for every line (* alias)
    }
  }
}

#####################################################################################################

function rawmsg($msg)
{
  global $socket;
  fputs($socket,$msg."\n");
}

#####################################################################################################

function exec_load(&$exec_list)
{
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
    if (count($parts)<>5)
    {
      continue;
    }
    if (($parts[0]=="") or (($parts[2]<>"0") and ($parts[2]<>"1")) or (($parts[3]<>"0") and ($parts[3]<>"1")) or ($parts[4]==""))
    {
      continue;
    }
    $alias=$parts[0];
    $exec_list[$alias]["timeout"]=$parts[1]; # seconds
    $exec_list[$alias]["auto"]=$parts[2]; # auto privmsg (0 = no, 1 = yes)
    $exec_list[$alias]["empty"]=$parts[3]; # empty msg permitted (0 = no, 1 = yes)
    $exec_list[$alias]["cmd"]=$parts[4]; # shell command
  }
  return True;
}

#####################################################################################################

function get_exec($alias)
{
  global $exec_list;
  if (isset($exec_list[$alias])==True)
  {
    $exec=$exec_list[$alias];
    return $alias.EXEC_DELIM.$exec["timeout"].EXEC_DELIM.$exec["auto"].EXEC_DELIM.$exec["empty"].EXEC_DELIM.$exec["cmd"];
  }
  else
  {
    return "";
  }
}

#####################################################################################################

function doquit()
{
  global $handles;
  global $socket;
  $n=count($handles);
  for ($i=0;$i<$n;$i++)
  {
    if (is_resource($handles[$i]["process"])==True)
    {
      proc_close($handles[$i]["process"]);
    }
  }
  rawmsg("QUIT");
  fclose($socket);
  term_echo("QUITTING SCRIPT");
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
      rawmsg("PONG ".$parts[1]);
      return True;
    }
  }
  return False;
}

#####################################################################################################

function term_echo($msg)
{
  echo "\033[1;31m$msg\033[0m\n";
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
  if ($sub[0]==":") # prefix found
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
  if ($result["cmd"]=="PRIVMSG")
  {
    $result["destination"]=$result["params"];
  }
  if ($result["prefix"]<>"")
  {
    # prefix format: nick!user@hostname
    $prefix=$result["prefix"];
    $i=strpos($prefix,"!");
    $result["nick"]=substr($prefix,0,$i);
    $prefix=substr($prefix,$i+1);
    $i=strpos($prefix,"@");
    $result["user"]=substr($prefix,0,$i);
    $prefix=substr($prefix,$i+1);
    $result["hostname"]=$prefix;
  }
  return $result;
}

#####################################################################################################

function privmsg($destination,$nick,$msg)
{
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
  if (substr($destination,0,1)=="#")
  {
    rawmsg(":".NICK." PRIVMSG $destination :$msg");
  }
  else
  {
    rawmsg(":".NICK." PRIVMSG $nick :$msg");
  }
  term_echo($msg);
}

#####################################################################################################

function process_scripts($items,$doall=False)
{
  global $handles;
  global $exec_list;
  global $alias_locks;
  $nick=trim($items["nick"]);
  $destination=trim($items["destination"]);
  $data=trim($items["data"]);
  $cmd=trim($items["cmd"]);
  $alias="*";
  $trailing=$items["trailing"];
  if ($doall==False)
  {
    if (isset($alias_locks[$nick])==True)
    {
      $alias=$alias_locks[$nick];
    }
    else
    {
      $parts=explode(" ",$items["trailing"]);
      $alias=trim($parts[0]);
      if (isset($exec_list[$alias])==False)
      {
        return;
      }
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
    }
    if ($alias=="*")
    {
      return;
    }
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
    privmsg($destination,$nick,"alias requires additional trailing argument");
    return;
  }
  $exec=get_exec($alias);
  $template=$exec_list[$alias]["cmd"];
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TRAILING.TEMPLATE_DELIM,escapeshellarg($trailing),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_NICK.TEMPLATE_DELIM,escapeshellarg($nick),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DESTINATION.TEMPLATE_DELIM,escapeshellarg($destination),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_START.TEMPLATE_DELIM,escapeshellarg(START_TIME),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_ALIAS.TEMPLATE_DELIM,escapeshellarg($alias),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DATA.TEMPLATE_DELIM,escapeshellarg($data),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_CMD.TEMPLATE_DELIM,escapeshellarg($cmd),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_EXEC.TEMPLATE_DELIM,escapeshellarg($exec),$template);
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
    "auto_privmsg"=>$exec_list[$alias]["auto"],
    "start"=>$start,
    "nick"=>$items["nick"],
    "destination"=>$items["destination"]);
  stream_set_blocking($pipes[0],0);
  stream_set_blocking($pipes[1],0);
  stream_set_blocking($pipes[2],0);
}

#####################################################################################################

function check_nick($items,$alias)
{
  global $time_deltas;
  if ($items["nick"]==NICK)
  {
    return True;
  }
  if ($items["cmd"]<>"PRIVMSG")
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

?>
