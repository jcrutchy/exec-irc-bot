<?php

# gpl2
# by crutchy
# 11-june-2014

# STRIPPED-DOWN VERSION OF EXEC FOR TESTING SCRIPTS

#####################################################################################################

define("NICK","exec_lt");
define("EXEC_FILE","exec_lt");
define("EXEC_DELIM","|");
define("STDOUT_PREFIX_RAW","IRC_RAW"); # if script stdout is prefixed with this, will be output to irc socket (raw)
define("STDOUT_PREFIX_MSG","IRC_MSG"); # if script stdout is prefixed with this, will be output to irc socket as privmsg
define("STDOUT_PREFIX_TERM","TERM"); # if script stdout is prefixed with this, will be output to the terminal only
define("INIT_CHAN_LIST","#1");
define("MAX_MSG_LENGTH",800);
define("IRC_HOST","irc.sylnt.us");
define("IRC_PORT","6667");
define("TEMPLATE_DELIM","%%");

# internal command aliases (can also use in exec file with alias locking, but that would be just weird)
define("CMD_QUIT","~q_lt");
define("CMD_RELOAD","~reload_lt");

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

$handles=array(); # stores executed process information

$exec_list=exec_load();
if ($exec_list===False)
{
  term_echo("error loading exec");
  return;
}

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
    term_echo("process terminated normally");
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
  handle_data($buf);
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

function handle_data($data,$is_sock=False)
{
  global $exec_list;
  echo $data;
  $items=parse_data($data);
  if ($items!==False)
  {
    if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
    {
      dojoin(INIT_CHAN_LIST);
      return;
    }
    $args=explode(" ",$items["trailing"]);
    switch ($args[0])
    {
      case CMD_QUIT:
        if (count($args)==1)
        {
          doquit();
        }
        break;
      case CMD_RELOAD:
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
      default:
        process_scripts($items); # execute scripts occurring for a specific alias
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
  term_echo($msg);
}

#####################################################################################################

function process_scripts($items)
{
  global $handles;
  global $exec_list;
  $nick=trim($items["nick"]);
  $destination=trim($items["destination"]);
  $data=trim($items["data"]);
  $cmd=trim($items["cmd"]);
  $trailing=$items["trailing"];
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
  if (isset($exec_list[$alias])==False)
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

?>
