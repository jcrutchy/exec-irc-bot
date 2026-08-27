<?php

#####################################################################################################

function get_bot_nick()
{
  global $buckets;
  return $buckets[BUCKET_BOT_NICK];
}

#####################################################################################################

function set_bot_nick($nick)
{
  global $buckets;
  $buckets[BUCKET_BOT_NICK]=$nick;
}

#####################################################################################################

function initialize_irc_connection()
{
  rawmsg("NICK ".get_bot_nick());
  rawmsg("USER ".USER_NAME." hostname servername :".FULL_NAME);
}

#####################################################################################################

function initialize_socket()
{
  $err_no=0;
  $err_msg="";
  if (IRC_PORT=="6697")
  {
    # cafile must contain both peer cert and then CA cert in single bundled file in order of peer, then CA
    $context_options=array(
      "ssl"=>array(
        "peer_name"=>SSL_PEER_NAME,
        "verify_peer"=>True,
        "verify_peer_name"=>True,
        "allow_self_signed"=>False,
        "verify_depth"=>5,
        "cafile"=>SSL_CA_FILE,
        "disable_compression"=>True,
        "SNI_enabled"=>True,
        "ciphers"=>"ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128:AES256:HIGH:!SSLv2:!aNULL:!eNULL:!EXPORT:!DES:!MD5:!RC4:!ADH"));
    $context=stream_context_create($context_options);
    $socket=stream_socket_client("tls://".IRC_HOST_CONNECT.":".IRC_PORT,$err_no,$err_msg,30,STREAM_CLIENT_CONNECT,$context);
  }
  else
  {
    $socket=stream_socket_client("tcp://".IRC_HOST_CONNECT.":".IRC_PORT,$err_no,$err_msg,30);
  }
  if ($socket===False)
  {
    term_echo("ERROR CREATING IRC SOCKET");
    die();
  }
  stream_set_blocking($socket,0);
  return $socket;
}

#####################################################################################################

function finalize_socket()
{
  global $socket;
  if (NICKSERV_IDENTIFY==="1")
  {
    rawmsg("NickServ LOGOUT");
  }
  rawmsg("QUIT :");
  fclose($socket);
}

#####################################################################################################

function initialize_buckets()
{
  global $buckets;
  $empty=array();
  $buckets[BUCKET_EVENT_HANDLERS]=base64_encode(serialize($empty));
  $buckets[BUCKET_CONNECTION_ESTABLISHED]="0";
  $buckets[BUCKET_USERS]=base64_encode(serialize($empty));
  $buckets[BUCKET_OUTPUT_CONTROL]=base64_encode(serialize($empty));
  $buckets[BUCKET_BOT_NICK]=DEFAULT_NICK;
}

#####################################################################################################

function init()
{
  $items=parse_data(CMD_INIT);
  buckets_load($items);
  initialize_buckets();
  #handle_data(CMD_INIT."\n",False,False,True);
  process_exec_helps();
  process_exec_inits();
  process_scripts($items,ALIAS_INIT);
}

#####################################################################################################

function process_exec_helps()
{
  global $help;
  global $exec_list;
  term_echo("help:");
  $help_lines=array();
  $file_lines=array();
  for ($i=0;$i<count($help);$i++)
  {
    if (file_exists($help[$i])==True)
    {
      if (is_dir($help[$i])==True)
      {
        load_directory($help[$i],$file_lines,FILE_DIRECTIVE_HELP);
      }
      else
      {
        load_include($help[$i],$file_lines,FILE_DIRECTIVE_HELP);
      }
    }
    else
    {
      $help_lines[]=$help[$i];
    }
  }
  foreach ($file_lines as $filename => $file_help_lines)
  {
    for ($i=0;$i<count($file_help_lines);$i++)
    {
      $help_lines[]=$file_help_lines[$i];
    }
  }
  unset($file_lines);
  for ($i=0;$i<count($help_lines);$i++)
  {
    $help_parts=explode(" ",$help_lines[$i]);
    if (count($help_parts)>=2)
    {
      $alias=trim($help_parts[0]);
      if (isset($exec_list[$alias]["help"])==True)
      {
        array_shift($help_parts);
        $help_line=implode(" ",$help_parts);
        $exec_list[$alias]["help"][]=$help_line;
        term_echo("ALIAS HELP: $alias => $help_line");
      }
    }
  }
}

#####################################################################################################

function process_exec_inits()
{
  global $init;
  term_echo("init:");
  $file_lines=array();
  for ($i=0;$i<count($init);$i++)
  {
    if (file_exists($init[$i])==True)
    {
      if (is_dir($init[$i])==True)
      {
        load_directory($init[$i],$file_lines,FILE_DIRECTIVE_INIT);
      }
      else
      {
        load_include($init[$i],$file_lines,FILE_DIRECTIVE_INIT);
      }
    }
  }
  foreach ($file_lines as $filename => $commands)
  {
    for ($i=0;$i<count($commands);$i++)
    {
      term_echo("FILE INIT: $filename =>".$commands[$i]);
      handle_data(":".get_bot_nick()." ".CMD_INTERNAL." :".$commands[$i]."\n",False,False,True);
    }
  }
  unset($file_lines);
}

#####################################################################################################

function startup()
{
  global $buckets;
  $buckets[BUCKET_CONNECTION_ESTABLISHED]="1";
  process_exec_startups();
  $items=parse_data(CMD_STARTUP);
  process_scripts($items,ALIAS_STARTUP);
}

#####################################################################################################

function process_exec_startups()
{
  global $startup;
  term_echo("startup:");
  $file_lines=array();
  for ($i=0;$i<count($startup);$i++)
  {
    if (file_exists($startup[$i])==True)
    {
      if (is_dir($startup[$i])==True)
      {
        load_directory($startup[$i],$file_lines,FILE_DIRECTIVE_STARTUP);
      }
      else
      {
        load_include($startup[$i],$file_lines,FILE_DIRECTIVE_STARTUP);
      }
    }
  }
  foreach ($file_lines as $filename => $commands)
  {
    for ($i=0;$i<count($commands);$i++)
    {
      term_echo("FILE STARTUP: ".$commands[$i]);
      handle_data(":".get_bot_nick()." ".CMD_INTERNAL." :".$commands[$i]."\n",False,False,True);
    }
  }
  unset($file_lines);
}

#####################################################################################################

function get_valid_data_cmd($allow_customs=True)
{
  /*
  prefix params trailing
  "000" = <command>
  "001" = <command> :<trailing>
  "010" = <command> <params>
  "011" = <command> <params> :<trailing>
  "100" = :<prefix> <command>
  "101" = :<prefix> <command> :<trailing>
  "110" = :<prefix> <command> <params>
  "111" = :<prefix> <command> <params> :<trailing>
  ORDER MASKS IN ORDER FROM 000 TO 111: STDOUT PREFIX WILL SELECT LAST (MOST VERBOSE) MASK FOR OUTPUT TO IRC
  # = NUMERIC
  */
  $result=array(
    "#"=>array("101","110","111"),
    "USER"=>array("010","011"),
    "INVITE"=>array("111"),
    "JOIN"=>array("010","110"),
    "KICK"=>array("110","111"),
    "KILL"=>array("101"),
    "MODE"=>array("101","110","111"),
    "NICK"=>array("010","101"),
    "NOTICE"=>array("111"),
    "PART"=>array("110","111"),
    "WHOIS"=>array("010","110"),
    "PRIVMSG"=>array("011","111"),
    "QUIT"=>array("100","101"),
    "PONG"=>array("001","110","111"));
  if ($allow_customs==True)
  {
    $customs=get_valid_custom_cmd();
    $result=array_merge($result,$customs);
  }
  return $result;
}

#####################################################################################################

function get_valid_custom_cmd()
{
  $result=array(
    CMD_INIT=>array("000"),
    CMD_STARTUP=>array("000"),
    CMD_INTERNAL=>array("100","101","110","111"),
    CMD_BUCKET_GET=>array("001","101"),
    CMD_BUCKET_SET=>array("001","101"),
    CMD_BUCKET_UNSET=>array("001","101"),
    CMD_BUCKET_APPEND=>array("001","101"),
    CMD_BUCKET_LIST=>array("000","100"),
    CMD_PAUSE=>array("000"),
    CMD_UNPAUSE=>array("000"));
  return $result;
}

#####################################################################################################

function get_list($items)
{
  global $exec_list;
  global $reserved_aliases;
  $msg=" ~list ~list-auth ~lock ~unlock";
  privmsg($items["destination"],$items["nick"],$msg);
  $aliases=array_keys($exec_list);
  sort($aliases);
  $msg="";
  for ($i=0;$i<count($aliases);$i++)
  {
    $alias=$aliases[$i];
    if (($exec_list[$alias]["accounts_wildcard"]<>"@") and ($exec_list[$alias]["accounts_wildcard"]<>"+") and (count($exec_list[$alias]["accounts"])==0) and (strlen($alias)<=20) and (in_array($alias,$reserved_aliases)==False) and ((count($exec_list[$alias]["cmds"])==0) or (in_array("PRIVMSG",$exec_list[$alias]["cmds"])==True)))
    {
      if (strlen($msg.$alias)>(MAX_MSG_LENGTH-1))
      {
        privmsg($items["destination"],$items["nick"]," ".trim($msg));
        $msg=$alias;
      }
      else
      {
        $msg=$msg.$alias;
      }
      $msg=$msg." ";
    }
  }
  if (trim($msg)<>"")
  {
    privmsg($items["destination"],$items["nick"]," ".trim($msg));
  }
}

#####################################################################################################

function get_list_auth($items)
{
  global $exec_list;
  global $reserved_aliases;
  $msg=" ~quit ~rehash ~ps ~kill ~killall ~dest-override ~dest-clear ~buckets-dump ~buckets-save ~buckets-load ~buckets-flush ~buckets-list ~restart ~ignore ~unignore";
  privmsg($items["destination"],$items["nick"],$msg);
  $aliases=array_keys($exec_list);
  sort($aliases);
  $msg="";
  for ($i=0;$i<count($aliases);$i++)
  {
    $alias=$aliases[$i];
    if ((($exec_list[$alias]["accounts_wildcard"]=="@") or ($exec_list[$alias]["accounts_wildcard"]=="+") or (count($exec_list[$alias]["accounts"])>0)) and (strlen($alias)<=20) and (in_array($alias,$reserved_aliases)==False) and ((count($exec_list[$alias]["cmds"])==0) or (in_array("PRIVMSG",$exec_list[$alias]["cmds"])==True)))
    {
      if (strlen($msg.$alias)>(MAX_MSG_LENGTH-1))
      {
        privmsg($items["destination"],$items["nick"]," ".trim($msg));
        $msg=$alias;
      }
      else
      {
        $msg=$msg.$alias;
      }
      $msg=$msg." ";
    }
  }
  if (trim($msg)<>"")
  {
    privmsg($items["destination"],$items["nick"]," ".trim($msg));
  }
}

#####################################################################################################

function handle_errors($data)
{
  global $buckets;
  if (isset($buckets[BUCKET_CONNECTION_ESTABLISHED])==False)
  {
    return;
  }
  $msg=trim($data,"\n\r\0\x0B");
  $lmsg=strtolower($msg);
  if ((DEBUG_CHAN<>"") and (strpos($lmsg,DEBUG_CHAN)===False) and ($buckets[BUCKET_CONNECTION_ESTABLISHED]<>"0"))
  {
    # TODO: only output if occurs at the start of line
    if ((strpos($lmsg,"php parse error:")!==False) or (strpos($lmsg,"php warning:")!==False) or (strpos($lmsg,"php fatal error:")!==False) or (strpos($lmsg,"php notice:")!==False))
    {
      rawmsg(":".get_bot_nick()." PRIVMSG ".DEBUG_CHAN." :$msg");
    }
  }
}

#####################################################################################################

function log_items($items)
{
  if (MYSQL_LOG=="1")
  {
    if ((BOT_SCHEMA=="") or (LOG_TABLE==""))
    {
      return;
    }
    sql_insert($items,LOG_TABLE);
  }
}

#####################################################################################################

function handle_direct_stdin()
{
  global $direct_stdin;
  global $irc_pause;
  $msg=trim(fgets($direct_stdin));
  if ($msg=="")
  {
    return;
  }
  term_echo("*** DIRECT STDIN: $msg");
  $parts=explode(" ",$msg);
  $prefix=strtoupper($parts[0]);
  array_shift($parts);
  $prefix_msg=implode(" ",$parts);
  if ($prefix_msg<>"")
  {
    switch ($prefix)
    {
      case PREFIX_IRC:
        rawmsg($prefix_msg);
        return;
      case PREFIX_INTERNAL:
        handle_data(":".get_bot_nick()." ".CMD_INTERNAL." :".$prefix_msg."\n",False,False,True);
        return;
      case PREFIX_PAUSE:
        $irc_pause=True;
        return;
      case PREFIX_UNPAUSE:
        $irc_pause=False;
        return;
    }
  }
  handle_data($msg."\n",False,False,True);
}

#####################################################################################################

function handle_process($handle)
{
  global $reserved_aliases;
  global $silent_timeout_commands;
  handle_stdout($handle);
  handle_stderr($handle);
  $flag=False;
  if ($handle["pipe_stdout"]===Null)
  {
    $flag=True;
  }
  else
  {
    $meta=stream_get_meta_data($handle["pipe_stdout"]);
    if ($meta["eof"]==True)
    {
      $flag=True;
    }
  }
  if ($flag==True)
  {
    write_out_buffer_proc($handle,"","proc_end");
    free_bucket_locks($handle["pid"]);
    fclose($handle["pipe_stdin"]);
    fclose($handle["pipe_stdout"]);
    fclose($handle["pipe_stderr"]);
    proc_close($handle["process"]);
    if ($handle["alias"]<>ALIAS_ALL)
    {
      #term_echo("process terminated normally: ".$handle["command"]);
    }
    return False;
  }
  if ($handle["timeout"]>0)
  {
    if ((microtime(True)-$handle["start"])>$handle["timeout"])
    {
      write_out_buffer_proc($handle,"","proc_timeout");
      free_bucket_locks($handle["pid"]);
      kill_process($handle);
      term_echo("process timed out: ".$handle["command"]);
      $msg="process timed out: ".$handle["alias"]." ".$handle["trailing"];
      if ((in_array($handle["alias"],$reserved_aliases)==False) and (in_array($handle["cmd"],$silent_timeout_commands)==False))
      {
        #privmsg($handle["destination"],$handle["nick"],$msg);
      }
      return False;
    }
  }
  return True;
}

#####################################################################################################

function free_bucket_locks($pid)
{
  global $bucket_locks;
  foreach ($bucket_locks as $bucket_index => $pid_array)
  {
    $key=array_search($pid,$pid_array);
    if ($key!==False)
    {
      unset($pid_array[$key]);
      if (count($pid_array)==0)
      {
        unset($bucket_locks[$bucket_index]);
        term_echo("BUCKET UNLOCKED: $bucket_index BY $pid [NO LONGER LOCKED BY ANY PROCESSES]");
      }
      else
      {
        term_echo("BUCKET UNLOCKED: $bucket_index BY $pid [STILL LOCKED BY OTHER PROCESS]");
      }
    }
  }
}

#####################################################################################################

function write_out_buffer($buf)
{
  if (IFACE_ENABLE!=="1")
  {
    return;
  }
  # use "cat exec_iface" to follow (tail -f no worka for some reason)
  global $out_buffer;
  if (flock($out_buffer,LOCK_EX)==True)
  {
    $buf=str_replace(chr(13),"",$buf);
    $buf=str_replace(chr(10),"",$buf);
    fwrite($out_buffer,serialize($buf)."\n");
  }
  flock($out_buffer,LOCK_UN);
}

#####################################################################################################

function write_out_buffer_proc($handle,$buf,$type)
{
  $data=array();
  $data["type"]=$type;
  $data["buf"]=$buf;
  $data["handle"]=$handle;
  $data["time"]=microtime(True);
  write_out_buffer($data);
}

#####################################################################################################

function write_out_buffer_command($items,$command)
{
  $data=array();
  $data["type"]="command";
  $data["buf"]=$command;
  $data["items"]=$items;
  $data["time"]=microtime(True);
  write_out_buffer($data);
}

#####################################################################################################

function write_out_buffer_data($items)
{
  $data=array();
  $data["type"]="data";
  $data["buf"]=$items["trailing"];
  $data["items"]=$items;
  $data["time"]=microtime(True);
  write_out_buffer($data);
}

#####################################################################################################

function write_out_buffer_sock($buf)
{
  $data=array();
  $data["type"]="socket";
  $data["buf"]=$buf;
  $data["time"]=microtime(True);
  write_out_buffer($data);
}

#####################################################################################################

function handle_reader_stdout_command($handle,$prefix)
{
  global $exec_list;
  global $buckets;
  global $handles;
  switch ($prefix)
  {
    case PREFIX_READER_EXEC_LIST:
      foreach ($exec_list as $alias => $exec_data)
      {
        $data=array();
        $data["type"]="reader_exec_list";
        $data["buf"]=$exec_data;
        $data["time"]=microtime(True);
        write_out_buffer($data);
      }
      return;
    case PREFIX_READER_BUCKETS:
      foreach ($buckets as $index => $value)
      {
        $data=array();
        $data["type"]="reader_buckets";
        $data["buf"]=$value;
        $data["index"]=$index;
        $data["time"]=microtime(True);
        write_out_buffer($data);
      }
      return;
    case PREFIX_READER_HANDLES:
      foreach ($handles as $index => $data)
      {
        $data=array();
        $data["type"]="reader_handles";
        $data["buf"]=$handles[$index];
        $data["time"]=microtime(True);
        write_out_buffer($data);
      }
      return;
  }
}

#####################################################################################################

function handle_stdout($handle)
{
  global $exec_list;
  global $irc_pause;
  global $buckets;
  if (is_resource($handle["pipe_stdout"])==False)
  {
    return;
  }
  $read=array($handle["pipe_stdout"]);
  $write=NULL;
  $except=NULL;
  $changed=stream_select($read,$write,$except,0);
  if ($changed===False)
  {
    return;
  }
  if ($changed<=0)
  {
    return;
  }
  $buf=fgets($handle["pipe_stdout"]);
  #$buf=stream_get_line($handle["pipe_stdout"],0,"\n");
  if ($buf===False)
  {
    return;
  }
  $output_control=unserialize(base64_decode($buckets[BUCKET_OUTPUT_CONTROL]));
  if (isset($output_control[$handle["alias"]])==True)
  {
    # TODO: PROCESS OPTIONAL OUTPUT BUFFERING
  }
  write_out_buffer_proc($handle,$buf,"stdout");
  if (trim($buf)==DIRECTIVE_QUIT)
  {
    doquit();
  }
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  handle_errors($msg);
  if ($handle["auto_privmsg"]==1)
  {
    privmsg($handle["destination"],$handle["nick"],$msg);
  }
  else
  {
    $parts=explode(" ",$msg);
    $prefix=strtoupper($parts[0]);
    array_shift($parts);
    $prefix_msg=implode(" ",$parts);
    if (($prefix_msg<>"") and ($prefix_msg<>""))
    {
      switch ($prefix)
      {
        case PREFIX_IRC:
          rawmsg($prefix_msg);
          return;
        case PREFIX_EXEC_ADD:
          if (load_exec_line($prefix_msg,$msg,False)!==False)
          {
            privmsg($handle["destination"],$handle["nick"],"successfully added exec line");
          }
          else
          {
            privmsg($handle["destination"],$handle["nick"],"error adding exec line");
          }
          return;
        case PREFIX_EXEC_DEL:
          $alias=strtolower(trim($prefix_msg));
          if (isset($exec_list[$alias]["saved"])==True)
          {
            if ($exec_list[$alias]["saved"]==True)
            {
              unset($exec_list[$alias]);
              privmsg($handle["destination"],$handle["nick"],"alias \"$alias\" deleted from memory (not from file though)");
            }
            else
            {
              privmsg($handle["destination"],$handle["nick"],"alias \"$alias\" with current configuration doesn't exist in exec file");
            }
          }
          else
          {
            privmsg($handle["destination"],$handle["nick"],"alias \"$alias\" not found");
          }
          return;
        case PREFIX_EXEC_SAVE:
          $alias=strtolower(trim($prefix_msg));
          if (isset($exec_list[$alias]["saved"])==True)
          {
            if ($exec_list[$alias]["saved"]==False)
            {
              if (file_put_contents(EXEC_FILE,trim($prefix_msg),FILE_APPEND)==True)
              {
                $exec_list[$alias]["saved"]=True;
                privmsg($handle["destination"],$handle["nick"],"exec line for alias \"$alias\" successfully appended to exec file");
              }
              else
              {
                privmsg($handle["destination"],$handle["nick"],"error appending exec file");
              }
            }
            else
            {
              privmsg($handle["destination"],$handle["nick"],"alias \"$alias\" with current configuration already exists in exec file");
            }
          }
          else
          {
            privmsg($handle["destination"],$handle["nick"],"alias \"$alias\" not found");
          }
          return;
        case PREFIX_PRIVMSG:
          if (($handle["destination"]<>"") and ($handle["nick"]<>""))
          {
            privmsg($handle["destination"],$handle["nick"],$prefix_msg);
          }
          return;
        case PREFIX_BUCKET_GET:
          handle_buckets(CMD_BUCKET_GET." :".$prefix_msg."\n",$handle);
          return;
        case PREFIX_BUCKET_SET:
          handle_buckets(CMD_BUCKET_SET." :".$prefix_msg."\n",$handle);
          return;
        case PREFIX_BUCKET_UNSET:
          handle_buckets(CMD_BUCKET_UNSET." :".$prefix_msg."\n",$handle);
          return;
        case PREFIX_BUCKET_APPEND:
          handle_buckets(CMD_BUCKET_APPEND." :".$prefix_msg."\n",$handle);
          return;
        case PREFIX_INTERNAL:
          $test=trim($prefix_msg);
          if ($test[0]==":")
          {
            handle_data($test."\n");
          }
          else
          {
            $nick=$handle["nick"];
            if ($nick=="")
            {
              $nick=get_bot_nick();
            }
            if ($handle["destination"]=="")
            {
              handle_data(":$nick ".CMD_INTERNAL." :".$prefix_msg."\n");
            }
            else
            {
              handle_data(":$nick ".CMD_INTERNAL." ".$handle["destination"]." :".$prefix_msg."\n");
            }
          }
          return;
        case PREFIX_PAUSE:
          $irc_pause=True;
          return;
        case PREFIX_UNPAUSE:
          $irc_pause=False;
          return;
        case PREFIX_DELETE_HANDLER:
          $parts=explode("=>",$prefix_msg);
          if (count($parts)<2)
          {
            term_echo("*** ERROR: INVALID DELETE_HANDLER COMMAND");
            return;
          }
          $cmd=strtoupper(trim($parts[0]));
          array_shift($parts);
          $data=trim(implode("=>",$parts));
          $handlers=unserialize(base64_decode($buckets[BUCKET_EVENT_HANDLERS]));
          for ($i=0;$i<count($handlers);$i++)
          {
            $handler=unserialize($handlers[$i]);
            if (isset($handler[$cmd])==True)
            {
              if ($handler[$cmd]==$data)
              {
                unset($handler[$cmd]);
                if (count($handler)==0)
                {
                  unset($handlers[$i]);
                  $handlers=array_values($handlers);
                }
                $buckets[BUCKET_EVENT_HANDLERS]=base64_encode(serialize($handlers));
                term_echo("*** DELETE EVENT-HANDLER: $cmd => $data (SUCCESS)");
                return;
              }
            }
          }
          term_echo("*** DELETE EVENT-HANDLER: $cmd => $data (FAILED)");
          return;
      }
    }
    else
    {
      switch ($prefix)
      {
        case PREFIX_BUCKET_LIST:
          handle_buckets(CMD_BUCKET_LIST."\n",$handle);
          return;
        case PREFIX_READER_EXEC_LIST:
          handle_reader_stdout_command($handle,PREFIX_READER_EXEC_LIST);
          return;
        case PREFIX_READER_BUCKETS:
          handle_reader_stdout_command($handle,PREFIX_READER_BUCKETS);
          return;
        case PREFIX_READER_HANDLES:
          handle_reader_stdout_command($handle,PREFIX_READER_HANDLES);
          return;
      }
    }
    if (handle_buckets($msg,$handle)==False)
    {
      handle_data($buf);
    }
  }
}

#####################################################################################################

function handle_stderr($handle)
{
  if (is_resource($handle["pipe_stderr"])==False)
  {
    return;
  }
  $read=array($handle["pipe_stderr"]);
  $write=NULL;
  $except=NULL;
  $changed=stream_select($read,$write,$except,0);
  if ($changed===False)
  {
    return;
  }
  if ($changed<=0)
  {
    return;
  }
  $buf=fgets($handle["pipe_stderr"]);
  if ($buf===False)
  {
    return;
  }
  write_out_buffer_proc($handle,$buf,"stderr");
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  handle_errors("STDERR IN [".$handle["command"]."]: ".$msg);
  term_echo("STDERR IN [".$handle["command"]."]: ".$msg);
}

#####################################################################################################

function handle_stdin($handle,$data)
{
  if (is_resource($handle["pipe_stdin"])==False)
  {
    return False;
  }
  $str=base64_encode($data).PHP_EOL;
  if (fwrite($handle["pipe_stdin"],$str,strlen($str))===False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

# buckets are stored in plain text, except arrays which are stored in encoded serialized form
# sending and receiving bucket data from process stdin and stdout pipes is in encoded form (arrays are double-encoded)
# don't need to encode outgoing data in handle_buckets, since it is done by handle_stdin

function handle_buckets($data,$handle)
{
  global $buckets;
  global $bucket_locks;
  global $internal_bucket_indexes;
  global $exec_list;
  $items=parse_data($data);
  if ($items===False)
  {
    return False;
  }
  $trailing=$items["trailing"];
  switch ($items["cmd"])
  {
    case CMD_BUCKET_GET:
      $index=$trailing;
      if ($index==BUCKET_EXEC_LIST)
      {
        $exec_list_data=serialize($exec_list);
        $result=handle_stdin($handle,$exec_list_data);
        return True;
      }
      if ($index==BUCKET_ADMIN_ACCOUNTS_LIST)
      {
        $result=handle_stdin($handle,ADMIN_ACCOUNTS);
        return True;
      }
      if ($index==BUCKET_OPERATOR_ACCOUNT)
      {
        $result=handle_stdin($handle,OPERATOR_ACCOUNT);
        return True;
      }
      if ($index==BUCKET_MEMORY_USAGE)
      {
        $result=handle_stdin($handle,memory_get_usage());
        return True;
      }
      if ($index==BUCKET_OPERATOR_HOSTNAME)
      {
        $result=handle_stdin($handle,OPERATOR_HOSTNAME);
        return True;
      }
      if (substr($index,0,strlen(BUCKET_ALIAS_ELEMENT_PREFIX))==BUCKET_ALIAS_ELEMENT_PREFIX)
      {
        $parts_str=substr($index,strlen(BUCKET_ALIAS_ELEMENT_PREFIX));
        $parts=explode("_",$parts_str);
        if (count($parts)<=1)
        {
          handle_stdin($handle,"");
          return True;
        }
        $alias=array_shift($parts);
        array_values($parts);
        $key=implode("_",$parts);
        if (isset($exec_list[$alias][$key])==True)
        {
          $out=$exec_list[$alias][$key];
          if (is_array($exec_list[$alias][$key])==True)
          {
            $out=serialize($exec_list[$alias][$key]);
          }
          $result=handle_stdin($handle,$out);
          if ($result===False)
          {
            term_echo("alias element failed for pid ".$handle["pid"]);
          }
          return True;
        }
      }
      if (substr($index,0,strlen(BUCKET_PROCESS_TEMPLATE_PREFIX))==BUCKET_PROCESS_TEMPLATE_PREFIX)
      {
        $process_template=substr($index,strlen(BUCKET_PROCESS_TEMPLATE_PREFIX));
        if (isset($handle[$process_template])==True)
        {
          $out=$handle[$process_template];
          if (is_array($handle[$process_template])==True)
          {
            $out=serialize($handle[$process_template]);
          }
          $result=handle_stdin($handle,$out);
          if ($result===False)
          {
            term_echo("process template \"".$process_template."\" failed for pid ".$handle["pid"]);
          }
          return True;
        }
      }
      if (isset($buckets[$index])==True)
      {
        if (isset($bucket_locks[$index])==True)
        {
          term_echo("BUCKET_GET [$index]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: ".implode(",",$bucket_locks[$index]));
          handle_stdin($handle,"");
          return True;
        }
        $size=round(strlen($buckets[$index])/1024,1)."kb";
        $result=handle_stdin($handle,$buckets[$index]);
        if ($result===False)
        {
          term_echo("BUCKET_GET [$index]: ERROR WRITING BUCKET DATA TO STDIN ($size)");
        }
      }
      else
      {
        handle_stdin($handle,"");
      }
      return True;
    case CMD_BUCKET_SET:
      $parts=explode(" ",$trailing);
      if (count($parts)<2)
      {
        term_echo("BUCKET_SET: INVALID TRAILING: '$trailing'");
      }
      else
      {
        $index=$parts[0];
        if (in_array($index,$internal_bucket_indexes)==True)
        {
          term_echo("BUCKET_SET [$index]: BUCKET INDEX RESERVED");
          return True;
        }
        if (isset($bucket_locks[$index])==True)
        {
          term_echo("BUCKET_SET [$index]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: ".implode(",",$bucket_locks[$index]));
          return True;
        }
        unset($parts[0]);
        $trailing=implode(" ",$parts);
        $buckets[$index]=base64_decode($trailing);
      }
      return True;
    case CMD_BUCKET_UNSET:
      $index=$trailing;
      if (isset($buckets[$index])==True)
      {
        if (isset($bucket_locks[$index])==True)
        {
          term_echo("BUCKET_UNSET [$index]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: ".implode(",",$bucket_locks[$index]));
          return True;
        }
        unset($buckets[$index]);
      }
      return True;
    case CMD_BUCKET_APPEND:
      $parts=explode(" ",$trailing);
      if (count($parts)<2)
      {
        term_echo("BUCKET_APPEND: INVALID TRAILING: '$trailing'");
      }
      else
      {
        $index=$parts[0];
        if (isset($bucket_locks[$index])==True)
        {
          term_echo("BUCKET_APPEND [$index]: BUCKET INDEX LOCKED BY FOLLOWING PID LIST: ".implode(",",$bucket_locks[$index]));
          return True;
        }
        unset($parts[0]);
        $trailing=implode(" ",$parts);
        $bucket_array=array();
        if (isset($buckets[$index])==True)
        {
          $bucket_array=base64_decode($buckets[$index]);
          if ($bucket_array===False)
          {
            term_echo("BUCKET_APPEND [$index]: DECODE ERROR");
            return True;
          }
          $bucket_array=unserialize($bucket_array);
          if ($bucket_array===False)
          {
            term_echo("BUCKET_APPEND [$index]: UNSERIALIZE ERROR");
            return True;
          }
        }
        else
        {
          term_echo("BUCKET_APPEND [$index]: NEW ARRAY BUCKET CREATED");
        }
        $bucket_array[]=$trailing;
        $bucket_string=base64_encode(serialize($bucket_array));
        if ($bucket_string!==False)
        {
          $buckets[$index]=$bucket_string;
        }
        else
        {
          term_echo("BUCKET_APPEND [$index]: SERIALIZE ERROR");
        }
      }
      return True;
    case CMD_BUCKET_LIST:
      $data="";
      foreach ($buckets as $index => $value)
      {
        if ($data=="")
        {
          $data=$index;
        }
        else
        {
          $data=$data." ".$index;
        }
      }
      $result=handle_stdin($handle,$data);
      if ($result===False)
      {
        term_echo("BUCKET_LIST: ERROR WRITING TO STDIN");
      }
      return True;
  }
  return False;
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
  $data=base64_encode(serialize($buckets));
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
  $size=round(strlen($data)/1024,1);
  privmsg($items["destination"],$items["nick"],"successfully saved buckets file ($size kb)");
}

#####################################################################################################

function buckets_load($items)
{
  global $buckets;
  if (file_exists(BUCKETS_FILE)==True)
  {
    $data=file_get_contents(BUCKETS_FILE);
  }
  else
  {
    term_echo("*** BUCKETS FILE NOT FOUND");
    return;
  }
  if ($data===False)
  {
    privmsg($items["destination"],$items["nick"],"error reading buckets file");
    return;
  }
  $data=unserialize(base64_decode($data));
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
  $connected=$buckets[BUCKET_CONNECTION_ESTABLISHED];
  $buckets=array();
  initialize_buckets();
  $buckets[BUCKET_CONNECTION_ESTABLISHED]=$connected;
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
  global $irc_pause;
  if ($irc_pause==True)
  {
    usleep(200000);
    return;
  }
  $read=array($socket);
  $write=Null;
  $except=Null;
  $c=stream_select($read,$write,$except,0,200000);
  if ($c===False)
  {
    return;
  }
  if ($c<>1)
  {
    return;
  }
  $data="";
  do
  {
    $buffer=fread($socket,8);
    if (strlen($buffer)===False)
    {
      term_echo("socket read error");
      doquit();
    }
    $data.=$buffer;
  }
  while (strlen($buffer)>0);
  if (strlen($data)==0)
  {
    term_echo("connection terminated by remote host");
    doquit();
  }
  write_out_buffer_sock($data);
  if (pingpong($data)==False)
  {
    $lines=explode(PHP_EOL,$data);
    for ($i=0;$i<count($lines);$i++)
    {
      $line=$lines[$i];
      if (trim($line)<>"")
      {
        handle_data($line.PHP_EOL,True);
      }
    }
  }
}

#####################################################################################################

function has_account_list($alias)
{
  global $exec_list;
  if (isset($exec_list[$alias])==True)
  {
    if ((count($exec_list[$alias]["accounts"])>0) or ($exec_list[$alias]["accounts_wildcard"]<>""))
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function get_users($nick="")
{
  global $buckets;
  $users=array();
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize(base64_decode($buckets[BUCKET_USERS]));
  }
  if ($nick<>"")
  {
    if (isset($users[$nick]["channels"])==False)
    {
      $users[$nick]["channels"]=array();
    }
    if (isset($users[$nick]["nicks"])==False)
    {
      $users[$nick]["nicks"]=array();
    }
  }
  return $users;
}

#####################################################################################################

function set_users($users)
{
  global $buckets;
  $buckets[BUCKET_USERS]=base64_encode(serialize($users));
}

#####################################################################################################

function handle_privmsg(&$items)
{
  $nick=strtolower(trim($items["nick"]));
  if ($nick=="")
  {
    term_echo("*** USERS: handle_privmsg: empty nick");
    return;
  }
  $users=get_users($nick);
  $users[$nick]["prefix"]=trim($items["prefix"]);
  $users[$nick]["user"]=trim($items["user"]);
  $users[$nick]["hostname"]=trim($items["hostname"]);
  $users[$nick]["connected"]=True;
  set_users($users);
}

#####################################################################################################

function handle_join(&$items)
{
  $nick=strtolower(trim($items["nick"]));
  $channel=strtolower(trim($items["params"]));
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_join: empty nick or channel");
    return;
  }
  term_echo("*** USERS: handle_join: nick=$nick, channel=$channel");
  $users=get_users($nick);
  $users[$nick]["nicks"][$nick]=microtime(True);
  $users[$nick]["channels"][$channel]="";
  $users[$nick]["prefix"]=trim($items["prefix"]);
  $users[$nick]["user"]=trim($items["user"]);
  $users[$nick]["hostname"]=trim($items["hostname"]);
  $users[$nick]["connected"]=True;
  set_users($users);
}

#####################################################################################################

function handle_kick(&$items)
{
  $trailing=strtolower(trim($items["trailing"]));
  $parts=explode(" ",$trailing);
  if (count($parts)<>2)
  {
    term_echo("*** USERS: handle_kick: invalid number of parts");
    return;
  }
  $channel=$parts[0];
  $kicked_nick=$parts[1];
  if (($channel=="") or ($kicked_nick==""))
  {
    term_echo("*** USERS: handle_kick: empty channel or kicked nick");
    return;
  }
  term_echo("*** USERS: handle_kick: channel=$channel, kicked_nick=$kicked_nick");
  $users=get_users($kicked_nick);
  if (isset($users[$kicked_nick]["channels"][$channel])==True)
  {
    $users[$kicked_nick]["prefix"]=trim($items["prefix"]);
    $users[$kicked_nick]["user"]=trim($items["user"]);
    $users[$kicked_nick]["hostname"]=trim($items["hostname"]);
    unset($users[$kicked_nick]["channels"][$channel]);
    $users[$kicked_nick]["connected"]=True;
    set_users($users);
  }
  else
  {
    term_echo("*** USERS: handle_kick: bucket data not found");
  }
}

#####################################################################################################

function handle_nick(&$items)
{
  $old_nick=strtolower(trim($items["nick"]));
  $new_nick=strtolower(trim($items["trailing"]));
  if (($old_nick=="") or ($new_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_nick: old_nick=$old_nick, new_nick=$new_nick");
  $users=get_users($old_nick);
  $user=array();
  if (isset($users[$old_nick])==True)
  {
    $user=$users[$old_nick];
    unset($users[$old_nick]);
  }
  else
  {
    term_echo("*** USERS: handle_nick: bucket data not found");
  }
  $users[$new_nick]=$user;
  $users[$new_nick]["nicks"][$new_nick]=microtime(True);
  $users[$new_nick]["prefix"]=trim($items["prefix"]);
  $users[$new_nick]["user"]=trim($items["user"]);
  $users[$new_nick]["hostname"]=trim($items["hostname"]);
  $users[$new_nick]["connected"]=True;
  set_users($users);
}

#####################################################################################################

function handle_part(&$items)
{
  $nick=strtolower(trim($items["nick"]));
  $channel=strtolower(trim($items["destination"]));
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_part: empty channel or nick");
    return;
  }
  term_echo("*** USERS: handle_part: nick=$nick, channel=$channel");
  $users=get_users($nick);
  if (isset($users[$nick]["channels"][$channel])==True)
  {
    $users[$nick]["prefix"]=trim($items["prefix"]);
    $users[$nick]["user"]=trim($items["user"]);
    $users[$nick]["hostname"]=trim($items["hostname"]);
    unset($users[$nick]["channels"][$channel]);
    if (count($users[$nick]["channels"])>0)
    {
      $users[$nick]["connected"]=True;
    }
    else
    {
      $users[$nick]["connected"]=False;
    }
    set_users($users);
  }
  else
  {
    term_echo("*** USERS: handle_part: bucket data not found");
  }
}

#####################################################################################################

function handle_quit(&$items)
{
  $nick=strtolower(trim($items["nick"]));
  if ($nick=="")
  {
    term_echo("*** USERS: handle_quit: empty nick");
    return;
  }
  term_echo("*** USERS: handle_quit: nick=$nick");
  $users=get_users($nick);
  if (isset($users[$nick])==True)
  {
    $users[$nick]["prefix"]=trim($items["prefix"]);
    $users[$nick]["user"]=trim($items["user"]);
    $users[$nick]["hostname"]=trim($items["hostname"]);
    $users[$nick]["connected"]=False;
    $users[$nick]["channels"]=array();
    set_users($users);
  }
  else
  {
    term_echo("*** USERS: handle_quit: bucket data not found");
  }
}

#####################################################################################################

function handle_kill(&$items)
{
  $nick=strtolower(trim($items["params"]));
  if ($nick=="")
  {
    term_echo("*** USERS: handle_kill: empty nick");
    return;
  }
  term_echo("*** USERS: handle_kill: nick=$nick");
  $users=get_users($nick);
  if (isset($users[$nick])==True)
  {
    $users[$nick]["prefix"]=trim($items["prefix"]);
    $users[$nick]["user"]=trim($items["user"]);
    $users[$nick]["hostname"]=trim($items["hostname"]);
    $users[$nick]["connected"]=False;
    $users[$nick]["channels"]=array();
    set_users($users);
  }
  else
  {
    term_echo("*** USERS: handle_kill: bucket data not found");
  }
}

#####################################################################################################

function handle_311(&$items)
{
  # :irc.sylnt.us 311 exec crutchy ~crutchy 709-27-2-01.cust.aussiebb.net * :crutchy
  # TODO: assign prefix, user, hostname and connected
}

#####################################################################################################

function handle_302(&$items)
{
  # :irc.sylnt.us 302 crutchy :TheMightyBuzzard=+~TheMighty@Soylent/Staff/Developer/TMB crutchy=+~crutchy@119.18.0.66 chromas=-~chromas@0::1
  $trailing=strtolower(trim($items["trailing"]));
  $parts=explode(" ",$trailing);
  if (count($parts)<1)
  {
    term_echo("*** USERS: handle_302: invalid number of parts");
    return;
  }
  $users=get_users();
  for ($i=0;$i<count($parts);$i++)
  {
    $user_parts=explode("=",$parts[$i]);
    if (count($user_parts)<>2)
    {
      term_echo("*** USERS: handle_302: invalid number of user_parts");
      continue;
    }
    $nick=$user_parts[0];
    $prefix_parts=explode("@",$user_parts[1]);
    if (count($prefix_parts)<>2)
    {
      term_echo("*** USERS: handle_302: invalid number of prefix_parts");
      continue;
    }
    $hostname=$prefix_parts[1];
    term_echo("*** USERS: handle_302: nick=$nick, hostname=$hostname");
    $users[$nick]["hostname"]=$hostname;
  }
  set_users($users);
}

#####################################################################################################

function handle_319(&$items)
{
  $params=strtolower(trim($items["params"]));
  $trailing=strtolower(trim($items["trailing"]));
  $parts=explode(" ","$params $trailing");
  if (count($parts)<3)
  {
    term_echo("*** USERS: handle_319: invalid number of parts");
    return;
  }
  $subject_nick=$parts[1];
  if ($subject_nick=="")
  {
    term_echo("*** USERS: handle_319: empty subject_nick");
    return;
  }
  $users=get_users($subject_nick);
  for ($i=2;$i<count($parts);$i++)
  {
    $channel=$parts[$i];
    if ($channel=="")
    {
      term_echo("*** USERS: handle_319: empty channel");
      continue;
    }
    $user_auth="";
    $auth=$channel[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $user_auth=$auth;
      $channel=substr($channel,1);
      if ($channel=="")
      {
        term_echo("*** USERS: handle_319: empty auth channel (1)");
        continue;
      }
      $auth=$channel[0];
      if (($auth=="+") or ($auth=="@"))
      {
        $user_auth=$user_auth.$auth;
        $channel=substr($channel,1);
        if ($channel=="")
        {
          term_echo("*** USERS: handle_319: empty auth channel (2)");
          continue;
        }
      }
    }
    term_echo("*** USERS: handle_319: subject_nick=$subject_nick, channel=$channel");
    $users[$subject_nick]["channels"][$channel]=$user_auth;
    if (isset($users[$subject_nick]["nicks"][$subject_nick])==False)
    {
      $users[$subject_nick]["nicks"][$subject_nick]=microtime(True);
    }
  }
  set_users($users);
}

#####################################################################################################

function handle_330(&$items)
{
  $params=strtolower(trim($items["params"]));
  $parts=explode(" ",$params);
  if (count($parts)<>3)
  {
    term_echo("*** USERS: handle_330: invalid number of parts");
    return;
  }
  $subject_nick=$parts[1];
  if ($subject_nick=="")
  {
    term_echo("*** USERS: handle_330: empty subject_nick");
    return;
  }
  $account=$parts[2];
  if ($account=="")
  {
    term_echo("*** USERS: handle_330: empty account");
    return;
  }
  $users=get_users($subject_nick);
  $users[$subject_nick]["account"]=strtolower($account);
  $users[$subject_nick]["account_updated"]=microtime(True);
  set_users($users);
}

#####################################################################################################

function handle_353(&$items)
{
  $params=strtolower(trim($items["params"]));
  $trailing=strtolower(trim($items["trailing"]));
  $parts=explode(" ","$params $trailing");
  if (count($parts)<4)
  {
    term_echo("*** USERS: handle_353: invalid number of parts");
    return;
  }
  $channel=$parts[2];
  if ($channel=="")
  {
    term_echo("*** USERS: handle_353: empty channel");
    return;
  }
  $users=get_users();
  for ($i=3;$i<count($parts);$i++)
  {
    $nick=$parts[$i];
    if ($nick=="")
    {
      term_echo("*** USERS: handle_353: empty nick");
      continue;
    }
    $user_auth="";
    $auth=$nick[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $user_auth=$auth;
      $nick=substr($nick,1);
      if ($nick=="")
      {
        term_echo("*** USERS: handle_353: empty auth nick (1)");
        continue;
      }
      $auth=$nick[0];
      if (($auth=="+") or ($auth=="@"))
      {
        $user_auth=$user_auth.$auth;
        $nick=substr($nick,1);
        if ($nick=="")
        {
          term_echo("*** USERS: handle_353: empty auth nick (2)");
          continue;
        }
      }
    }
    term_echo("** USERS: handle_353: nick=$nick, channel=$channel");
    $users[$nick]["channels"][$channel]=$user_auth;
    if (isset($users[$nick]["nicks"][$nick])==False)
    {
      $users[$nick]["nicks"][$nick]=microtime(True);
    }
  }
  set_users($users);
}

#####################################################################################################

function handle_events(&$items)
{
  $cmd=strtoupper(trim($items["cmd"]));
  switch ($cmd)
  {
    case "PRIVMSG":
      handle_privmsg($items);
      break;
    case "JOIN":
      handle_join($items);
      break;
    case "KICK":
      handle_kick($items);
      break;
    case "KILL":
      handle_kill($items);
      break;
    case "NICK":
      handle_nick($items);
      break;
    case "PART":
      handle_part($items);
      break;
    case "QUIT":
      handle_quit($items);
      break;
    case "311":
      handle_311($items);
      break;
    case "319":
      handle_319($items);
      break;
    case "302":
      handle_302($items);
      break;
    case "330":
      handle_330($items);
      break;
    case "353":
      handle_353($items);
      break;
  }
  script_event_handlers($cmd,$items);
}

#####################################################################################################

function script_event_handlers($cmd,&$items)
{
  global $buckets;
  if (($cmd=="PRIVMSG") and ($items["nick"]==get_bot_nick()))
  {
    if (isset($buckets[BUCKET_SELF_TRIGGER_EVENTS_FLAG])==True)
    {
      term_echo("*** PRIVMSG EVENT TRIGGERED BY BOT WITH FLAG SET TO HANDLE EVENT");
    }
    else
    {
      return;
    }
  }
  $event_handlers=array();
  if (isset($buckets[BUCKET_EVENT_HANDLERS])==True)
  {
    $event_handlers=unserialize(base64_decode($buckets[BUCKET_EVENT_HANDLERS]));
  }
  if ($event_handlers===False)
  {
    return;
  }
  if (is_array($event_handlers)==False)
  {
    return;
  }
  $n=count($event_handlers);
  for ($i=0;$i<$n;$i++)
  {
    $data=unserialize($event_handlers[$i]);
    if ($data===False)
    {
      continue;
    }
    if (is_array($data)==False)
    {
      continue;
    }
    foreach ($data as $data_cmd => $value)
    {
      if ($cmd==$data_cmd)
      {
        $value=str_replace(TEMPLATE_DELIM.TEMPLATE_TRAILING.TEMPLATE_DELIM,$items["trailing"],$value);
        $value=str_replace(TEMPLATE_DELIM.TEMPLATE_NICK.TEMPLATE_DELIM,trim($items["nick"]),$value);
        $value=str_replace(TEMPLATE_DELIM.TEMPLATE_DESTINATION.TEMPLATE_DELIM,trim($items["destination"]),$value);
        $value=str_replace(TEMPLATE_DELIM.TEMPLATE_CMD.TEMPLATE_DELIM,trim($items["cmd"]),$value);
        $value=str_replace(TEMPLATE_DELIM.TEMPLATE_PARAMS.TEMPLATE_DELIM,trim($items["params"]),$value);
        handle_data("$value\n");
      }
    }
  }
}

#####################################################################################################

function handle_data($data,$is_sock=False,$auth=False,$exec=False)
{
  global $buckets;
  global $alias_locks;
  global $dest_overrides;
  global $admin_accounts;
  global $admin_data;
  global $admin_is_sock;
  global $exec_errors;
  global $exec_list;
  global $throttle_time;
  global $ignore_list;
  if ($auth==False)
  {
    echo "\033[33m".date("Y-m-d H:i:s",microtime(True))." > \033[0m$data";
    handle_errors($data);
  }
  else
  {
    term_echo("*** auth = true");
  }
  $items=parse_data($data);
  if ($items===False)
  {
    return;
  }
  write_out_buffer_data($items);
  if ($items["destination"]==DEBUG_CHAN)
  {
    return;
  }
  if (($auth==False) and ($is_sock==True))
  {
    log_items($items);
  }
  if (in_array($items["nick"],$ignore_list)==True)
  {
    return;
  }
  if ((isset($buckets[BUCKET_IGNORE_NEXT])==True) and ($items["nick"]==get_bot_nick()))
  {
    unset($buckets[BUCKET_IGNORE_NEXT]);
    return;
  }
  if (($items["prefix"]==IRC_HOST) and (strpos(strtolower($items["trailing"]),"throttled")!==False))
  {
    term_echo("*** THROTTLED BY SERVER - REFUSING ALL OUTGOING MESSAGES TO SERVER FOR ".THROTTLE_LOCKOUT_TIME." SECONDS ***");
    $throttle_time=microtime(True);
    return;
  }
  if ($items["cmd"]=="330") # is logged in as
  {
    authenticate($items);
  }
  if ($items["cmd"]=="376") # RPL_ENDOFMOTD (RFC1459)
  {
    dojoin(INIT_CHAN_LIST);
  }
  if (($items["cmd"]=="NICK") and ($items["nick"]==get_bot_nick()))
  {
    set_bot_nick(trim($items["trailing"]));
  }
  if ($items["cmd"]=="432") # Erroneous Nickname
  {
    set_bot_nick(trim($items["params"]));
  }
  if ($items["cmd"]=="043") # Nick collision
  {
    $parts=explode(" ",trim($items["params"]));
    set_bot_nick($parts[0]);
  }
  if (($items["cmd"]=="NOTICE") and ($items["nick"]=="NickServ") and ($items["trailing"]==NICKSERV_IDENTIFY_PROMPT))
  {
    if ((file_exists(PASSWORD_FILE)==True) and (NICKSERV_IDENTIFY==="1"))
    {
      rawmsg("NickServ IDENTIFY ".trim(file_get_contents(PASSWORD_FILE)),True);
    }
    startup();
  }
  $args=explode(" ",$items["trailing"]);
  if ((is_operator_alias($args[0])==True) or (is_admin_alias($args[0])==True) or (has_account_list($args[0])==True))
  {
    if (($auth==False) and ($is_sock==True))
    {
      term_echo("authenticating \"".$args[0]."\"...");
      $admin_data=$items["data"];
      $admin_is_sock=$is_sock;
      rawmsg("WHOIS ".$items["nick"]);
      return;
    }
  }
  $alias=$args[0];
  handle_events($items);
  switch ($alias)
  {
    case ALIAS_ADMIN_NICK:
      if (count($args)==2)
      {
        rawmsg(":".get_bot_nick()." NICK :".trim($args[1]));
      }
      break;
    case ALIAS_ADMIN_ALIAS_MACRO:
      $msg="";
      $macro=explode(" ",$items["trailing"]);
      array_shift($macro);
      array_values($macro);
      $macro=implode(" ",$macro);
      process_alias_config_macro($macro,$msg);
      if ($msg<>"")
      {
        privmsg($items["destination"],$items["nick"],"alias config macro: $msg");
      }
      break;
    case ALIAS_ADMIN_QUIT:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"quit");
        process_scripts($items,ALIAS_QUIT);
      }
      break;
    case ALIAS_ADMIN_PS:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"ps");
        ps($items);
      }
      break;
    case ALIAS_ADMIN_KILL:
      if (count($args)==2)
      {
        write_out_buffer_command($items,"kill");
        kill($items,$args[1]);
      }
      break;
    case ALIAS_ADMIN_KILLALL:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"killall");
        killall($items);
      }
      break;
    case ALIAS_LIST:
      if (check_nick($items,$alias)==True)
      {
        if (count($args)==1)
        {
          write_out_buffer_command($items,"list");
          get_list($items);
        }
      }
      break;
    case ALIAS_LIST_AUTH:
      if (check_nick($items,$alias)==True)
      {
        if (count($args)==1)
        {
          write_out_buffer_command($items,"listauth");
          get_list_auth($items);
        }
      }
      break;
    case ALIAS_LOCK:
      if (check_nick($items,$alias)==True)
      {
        if (count($args)==2)
        {
          write_out_buffer_command($items,"lock");
          $alias_locks[$items["nick"]][$items["destination"]]=$args[1];
          privmsg($items["destination"],$items["nick"],"alias \"".$args[1]."\" locked for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
        }
        else
        {
          privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_LOCK." <alias>");
        }
      }
      break;
    case ALIAS_UNLOCK:
      if ((check_nick($items,$alias)==True) and (isset($alias_locks[$items["nick"]][$items["destination"]])==True))
      {
        write_out_buffer_command($items,"unlock");
        privmsg($items["destination"],$items["nick"],"alias \"".$alias_locks[$items["nick"]][$items["destination"]]."\" unlocked for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
        unset($alias_locks[$items["nick"]][$items["destination"]]);
      }
      break;
    case ALIAS_ADMIN_DEST_OVERRIDE:
      if (count($args)==2)
      {
        write_out_buffer_command($items,"dest_override");
        privmsg($items["destination"],$items["nick"],"destination override \"".$args[1]."\" set for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
        $dest_overrides[$items["nick"]][$items["destination"]]=$args[1];
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_ADMIN_DEST_OVERRIDE." <dest>");
      }
      break;
    case ALIAS_ADMIN_DEST_CLEAR:
      if (isset($dest_overrides[$items["nick"]][$items["destination"]])==True)
      {
        write_out_buffer_command($items,"dest_clear");
        $override=$dest_overrides[$items["nick"]][$items["destination"]];
        unset($dest_overrides[$items["nick"]][$items["destination"]]);
        privmsg($items["destination"],$items["nick"],"destination override \"$override\" cleared for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
      }
      break;
    case ALIAS_ADMIN_IGNORE:
      if (count($args)==2)
      {
        if (in_array($args[1],$ignore_list)==False)
        {
          write_out_buffer_command($items,"ignore");
          privmsg($items["destination"],$items["nick"],get_bot_nick()." set to ignore ".$args[1]);
          $ignore_list[]=$args[1];
          if (file_put_contents(IGNORE_FILE,implode("\n",$ignore_list))===False)
          {
            privmsg($items["destination"],$items["nick"],"error saving ignore file");
          }
        }
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_ADMIN_IGNORE." <nick>");
      }
      break;
    case ALIAS_ADMIN_UNIGNORE:
      if (count($args)==2)
      {
        if (in_array($args[1],$ignore_list)==True)
        {
          $i=array_search($args[1],$ignore_list);
          if ($i!==False)
          {
            write_out_buffer_command($items,"unignore");
            privmsg($items["destination"],$items["nick"],get_bot_nick()." set to listen to ".$args[1]);
            unset($ignore_list[$i]);
            $ignore_list=array_values($ignore_list);
            if (file_put_contents(IGNORE_FILE,implode("\n",$ignore_list))===False)
            {
              privmsg($items["destination"],$items["nick"],"error saving ignore file");
            }
          }
          else
          {
            privmsg($items["destination"],$items["nick"],$args[1]." not found in ".get_bot_nick()." ignore list");
          }
        }
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_ADMIN_UNIGNORE." <nick>");
      }
      break;
    case ALIAS_ADMIN_LIST_IGNORE:
      if (count($ignore_list)>0)
      {
        write_out_buffer_command($items,"ignorelist");
        privmsg($items["destination"],$items["nick"],get_bot_nick()." ignore list: ".implode(", ",$ignore_list));
      }
      else
      {
        privmsg($items["destination"],$items["nick"],get_bot_nick()." isn't ignoring anyone");
      }
      break;
    case ALIAS_ADMIN_REHASH:
      if (count($args)==1)
      {
        if (exec_load()===False)
        {
          privmsg($items["destination"],$items["nick"],"error reloading exec file");
          doquit();
        }
        else
        {
          write_out_buffer_command($items,"rehash");
          process_exec_helps();
          process_exec_inits();
          process_exec_startups();
          $users=get_users();
          foreach ($users[get_bot_nick()]["channels"] as $channel => $timestamp)
          {
            rawmsg("NAMES $channel");
          }
          privmsg($items["destination"],$items["nick"],"successfully reloaded exec file (".count($exec_list)." aliases)");
        }
      }
      break;
    case ALIAS_ADMIN_BUCKETS_DUMP:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"buckets_dump");
        buckets_dump($items);
      }
      break;
    case ALIAS_ADMIN_BUCKETS_SAVE:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"buckets_save");
        buckets_save($items);
      }
      break;
    case ALIAS_ADMIN_BUCKETS_LOAD:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"buckets_load");
        buckets_load($items);
      }
      break;
    case ALIAS_ADMIN_BUCKETS_FLUSH:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"buckets_flush");
        buckets_flush($items);
      }
      break;
    case ALIAS_ADMIN_BUCKETS_LIST:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"buckets_list");
        buckets_list($items);
      }
      break;
    case ALIAS_INTERNAL_RESTART:
      if ((count($args)==1) and ($items["cmd"]==CMD_INTERNAL))
      {
        define("RESTART",True);
        process_scripts($items,ALIAS_QUIT);
      }
      break;
    case ALIAS_ADMIN_RESTART:
      if (count($args)==1)
      {
        write_out_buffer_command($items,"restart");
        define("RESTART",True);
        process_scripts($items,ALIAS_QUIT);
      }
      break;
    case ALIAS_ADMIN_EXEC_CONFLICTS:
      if (count($args)==1)
      {
        # TODO
      }
      break;
    case ALIAS_ADMIN_EXEC_LIST:
      if (count($args)==1)
      {
        # TODO
      }
      break;
    case ALIAS_ADMIN_EXEC_TIMERS:
      if (count($args)==1)
      {
        # TODO
      }
      break;
    case ALIAS_ADMIN_EXEC_ERRORS:
      if (count($args)==1)
      {
        $n=count($exec_errors);
        if ($n>0)
        {
          write_out_buffer_command($items,"exec_load_errors");
          privmsg($items["destination"],$items["nick"],"exec load errors:");
          $i=0;
          foreach ($exec_errors as $filename => $messages)
          {
            if ($i==($n-1))
            {
              privmsg($items["destination"],$items["nick"],"  └─".$filename);
              for ($j=0;$j<count($messages);$j++)
              {
                if ($j==(count($messages)-1))
                {
                  privmsg($items["destination"],$items["nick"],"     └─".$messages[$j]);
                }
                else
                {
                  privmsg($items["destination"],$items["nick"],"     ├─".$messages[$j]);
                }
              }
            }
            else
            {
              privmsg($items["destination"],$items["nick"],"  ├─".$filename);
              for ($j=0;$j<count($messages);$j++)
              {
                if ($j==(count($messages)-1))
                {
                  privmsg($items["destination"],$items["nick"],"  │  └─".$messages[$j]);
                }
                else
                {
                  privmsg($items["destination"],$items["nick"],"  │  ├─".$messages[$j]);
                }
              }
            }
            $i++;
          }
        }
        else
        {
          privmsg($items["destination"],$items["nick"],"no errors");
        }
      }
      break;
    default:
      process_scripts($items,""); # execute scripts occurring for a specific alias
      process_scripts($items,ALIAS_ALL); # process scripts occuring for every line (* alias)
  }
}

#####################################################################################################

function exec_load()
{
  global $exec_errors;
  global $exec_list;
  global $init;
  global $startup;
  global $help;
  global $buckets;
  $help=array();
  $startup=array();
  $init=array();
  $exec_errors=array();
  $exec_list=array();
  if (file_exists(EXEC_FILE)==False)
  {
    return False;
  }
  $data=file_get_contents(EXEC_FILE);
  if ($data===False)
  {
    return False;
  }
  $empty=array();
  $buckets[BUCKET_EVENT_HANDLERS]=base64_encode(serialize($empty));
  $data=explode("\n",$data);
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    $line_parts=explode(EXEC_DIRECTIVE_DELIM,$line);
    $directive=$line_parts[0];
    array_shift($line_parts);
    $trailing=implode(EXEC_DIRECTIVE_DELIM,$line_parts);
    switch ($directive)
    {
      case EXEC_INCLUDE:
        $file_lines=array();
        if (file_exists($trailing)==True)
        {
          if (is_dir($trailing)==True)
          {
            load_directory($trailing,$file_lines,FILE_DIRECTIVE_EXEC);
          }
          else
          {
            load_include($trailing,$file_lines,FILE_DIRECTIVE_EXEC);
          }
        }
        foreach ($file_lines as $filename => $exec_lines)
        {
          for ($j=0;$j<count($exec_lines);$j++)
          {
            $exec_record=load_exec_line($exec_lines[$j],$filename);
          }
        }
        unset($file_lines);
        break;
      case EXEC_STARTUP:
        $startup[]=$trailing;
        break;
      case EXEC_INIT:
        $init[]=$trailing;
        break;
      case EXEC_HELP:
        $help[]=$trailing;
        break;
      default:
        load_exec_line($data[$i],EXEC_FILE);
    }
  }
  return $exec_list;
}

#####################################################################################################

function load_include($filename,&$lines,$directive)
{
  global $exec_list;
  if (file_exists($filename)==False)
  {
    term_echo("load_include: \"$filename\" not found");
    return;
  }
  $data=file_get_contents($filename);
  if ($data===False)
  {
    term_echo("load_include: unable to read \"$filename\"");
    return;
  }
  $data=explode("\n",$data);
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    $dir_delim=$directive.FILE_DIRECTIVE_DELIM;
    if (substr($line,0,strlen($dir_delim))==$dir_delim)
    {
      $line=substr($line,strlen($dir_delim));
      $lines[$filename][]=$line;
    }
  }
}

#####################################################################################################

function process_alias_config_macro($macro,&$msg,$filename="")
{
  global $exec_list;
  $reserved=array("alias","timeout","repeat","auto","empty","accounts","accounts_wildcard","cmds","dests","bucket_locks","cmd","servers","saved","line","file","help","enabled");
  $reserved_arrays=array("accounts","cmds","dests","bucket_locks","servers","help");
  $parts=explode(" ",$macro);
  delete_empty_elements($parts);
  if (count($parts)<2)
  {
    $msg="needs at least an action and an alias";
    return False;
  }
  $action=strtolower(array_shift($parts));
  $alias=strtolower(array_shift($parts));
  if (count($parts)==0)
  {
    # enable/disable/add/delete alias
    switch ($action)
    {
      case "enable":
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias \"$alias\" not found";
          return False;
        }
        $exec_list[$alias]["enabled"]=True;
        $msg="alias \"$alias\" successfully enabled";
        return True;
      case "disable":
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias \"$alias\" not found";
          return False;
        }
        $exec_list[$alias]["enabled"]=False;
        $msg="alias \"$alias\" successfully disabled";
        return True;
      case "add":
        if (isset($exec_list[$alias])==True)
        {
          $msg="alias already exists";
          return False;
        }
        $record=array();
        $record["alias"]=$alias;
        $record["timeout"]=5;
        $record["repeat"]=0;
        $record["auto"]=0;
        $record["empty"]=1;
        $record["accounts"]=array();
        $record["accounts_wildcard"]="";
        $record["cmds"]=array();
        $record["dests"]=array();
        $record["bucket_locks"]=array();
        $record["cmd"]="";
        $record["servers"]=array();
        $record["saved"]=False;
        $record["line"]="";
        $record["file"]=$filename;
        $record["help"]=array();
        $record["enabled"]=False;
        $exec_list[$alias]=$record;
        $msg="alias \"$alias\" successfully added";
        return True;
      case "delete":
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias not found";
          return False;
        }
        unset($exec_list[$alias]);
        $msg="alias \"$alias\" successfully deleted";
        return True;
      default:
        $msg="invalid action";
        return False;
    }
  }
  elseif (count($parts)==1)
  {
    $key=$parts[0];
    switch ($action)
    {
      # delete element
      case "delete":
        if (in_array($key,$reserved)==True)
        {
          $msg="unable to delete reserved element \"$key\"";
          return False;
        }
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias not found";
          return False;
        }
        if (isset($exec_list[$alias][$key])==True)
        {
          $msg="element \"$key\" not found";
          return False;
        }
        unset($exec_list[$alias][$key]);
        $msg="element \"$key\" successfully deleted";
        return True;
      # rename alias
      case "rename":
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias not found";
          return False;
        }
        if ($key===$alias)
        {
          $msg="good one you idiot";
          return False;
        }
        $exec_list[$key]=$exec_list[$alias];
        $exec_list[$key]["enabled"]=False;
        unset($exec_list[$alias]);
        $msg="alias \"$alias\" successfully renamed (and disabled)";
        return True;
      default:
        $msg="invalid action";
        return False;
    }
  }
  elseif (count($parts)>1)
  {
    # add/edit element
    $key=$parts[0];
    array_shift($parts);
    $value=implode(" ",$parts);
    switch ($action)
    {
      case "add":
        if (isset($exec_list[$alias])==False)
        {
          $msg="alias not found";
          return False;
        }
        if (isset($exec_list[$alias][$key])==True)
        {
          $msg="element already exists";
          return False;
        }
        if (in_array($key,$reserved)==True)
        {
          $msg="unable to add reserved element \"$key\"";
          return False;
        }
        $exec_list[$alias][$key]=$value;
        $exec_list[$alias]["enabled"]=False;
        $msg="element successfully added (and alias disabled)";
        return True;
      case "edit":
        if (isset($exec_list[$alias][$key])==False)
        {
          $msg="element not found";
          return False;
        }
        $value_str=$value;
        if (in_array($key,$reserved_arrays)==True)
        {
          $value=explode(",",$value);
        }
        $exec_list[$alias][$key]=$value;
        $exec_list[$alias]["enabled"]=False;
        $msg="alias \"$alias\" element \"$key\" successfully updated with value \"$value_str\" (and alias disabled)";
        return True;
      default:
        $msg="invalid action";
        return False;
    }
  }
  $msg="error";
  return False;
}

#####################################################################################################

function load_exec_line($line,$filename,$saved=True)
{
  global $exec_errors;
  global $exec_list;
  $line=trim($line);
  if ($line=="")
  {
    return False;
  }
  if (substr($line,0,1)=="#")
  {
    return False;
  }
  $msg="";
  $result=process_alias_config_macro($line,$msg,$filename);
  if ($result!==False)
  {
    term_echo("EXEC ALIAS CONFIG MACRO SUCCESS: $line => $msg");
    return $result;
  }
  $parts=explode(EXEC_DELIM,$line);
  if (count($parts)<10)
  {
    $msg="not enough parameters: $line";
    term_echo($msg);
    $exec_errors[$filename][]=$msg;
    return False;
  }
  $alias=trim($parts[0]);
  $timeout=trim($parts[1]); # seconds
  $repeat=trim($parts[2]); # seconds
  $auto=trim($parts[3]); # auto privmsg (0 = no, 1 = yes)
  $empty=trim($parts[4]); # empty msg permitted (0 = no, 1 = yes)
  $accounts_wildcard="";
  $accounts=array();
  $accounts_str=trim($parts[5]);
  if ($accounts_str<>"")
  {
    if (($accounts_str=="@") or ($accounts_str=="+") or ($accounts_str=="*"))
    {
      $accounts_wildcard=$accounts_str;
    }
    else
    {
      $accounts=explode(",",$accounts_str); # comma-delimited list of NickServ accounts authorised to run script (or empty)
      if (in_array(get_bot_nick(),$accounts)==False)
      {
        $accounts[]=get_bot_nick();
      }
    }
  }
  $cmds=array();
  $cmds_str=strtoupper(trim($parts[6]));
  if ($cmds_str<>"")
  {
    $cmds=explode(",",$cmds_str);
  }
  $dests=array();
  $dests_str=strtolower(trim($parts[7]));
  if ($dests_str<>"")
  {
    $dests=explode(",",$dests_str);
  }
  $locks=array();
  $locks_str=strtoupper(trim($parts[8]));
  if ($locks_str<>"")
  {
    $locks=explode(" ",$locks_str);
  }
  for ($j=0;$j<=8;$j++)
  {
    array_shift($parts);
  }
  $cmd=trim(implode("|",$parts)); # shell command
  if (($alias=="") or (is_numeric($timeout)==False) or (is_numeric($repeat)==False) or (($auto<>"0") and ($auto<>"1")) or (($empty<>"0") and ($empty<>"1")) or ($cmd==""))
  {
    $msg="invalid parameter: $line";
    term_echo($msg);
    $exec_errors[$filename][]=$msg;
    return False;
  }
  $cmd_parts=explode(" ",$cmd);
  if (count($cmd_parts)>=2)
  {
    if (strtolower($cmd_parts[0])=="php")
    {
      $filename=$cmd_parts[1];
      if (strpos($filename,".php")!==False)
      {
        $filename=__DIR__."/".$filename;
        if (file_exists($filename)==False)
        {
          $msg="php file not found: $line";
          term_echo($msg);
          $exec_errors[$filename][]=$msg;
          return False;
        }
      }
    }
  }
  $result=array();
  $result["alias"]=$alias;
  $result["timeout"]=$timeout;
  $result["repeat"]=$repeat;
  $result["auto"]=$auto;
  $result["empty"]=$empty;
  $result["accounts"]=$accounts;
  $result["accounts_wildcard"]=$accounts_wildcard;
  $result["cmds"]=$cmds;
  $result["dests"]=$dests;
  $result["bucket_locks"]=$locks;
  $result["cmd"]=$cmd;
  $result["servers"]=array();
  $result["saved"]=$saved;
  $result["line"]=$line;
  $result["file"]=$filename;
  $result["help"]=array();
  $result["enabled"]=True;
  $exec_list[$alias]=$result;
  term_echo("SUCCESS: $line");
  return $result;
}

#####################################################################################################

function doquit()
{
  global $handles;
  global $socket;
  global $argv;
  global $buckets;
  term_echo("*** SETTING SHUTDOWN BUCKET ***");
  $buckets[BUCKET_SHUTDOWN]="1";
  $t=microtime(True);
  $shutdown_delay=10; #seconds
  while ((microtime(True)-$t)<=$shutdown_delay)
  {
    usleep(0.05e6); # 0.05 second to prevent cpu flogging
    term_echo("number of processes remaining: ".count($handles));
    for ($i=0;$i<count($handles);$i++)
    {
      if (isset($handles[$i])===False)
      {
        continue;
      }
      if (handle_process($handles[$i])==False)
      {
        unset($handles[$i]);
      }
    }
    $handles=array_values($handles);
    if (count($handles)==0)
    {
      term_echo("*** all handles closed ***");
      break;
    }
    # no handling of irc data, direct stdin or timed execs (only existing pipes and bucket commands to allow scripts to communicate with the bot normally)
  }
  $n=count($handles);
  if ($n>0)
  {
    term_echo("*** KILLING REMAINING $n HANDLE(S) ***");
    for ($i=0;$i<$n;$i++)
    {
      if (isset($handles[$i])==True) # have had a "Undefined offset: 0" notice on this line
      {
        if (is_resource($handles[$i]["process"])==True)
        {
          kill_process($handles[$i]);
        }
      }
    }
  }
  term_echo("QUITTING SCRIPT");
  finalize_socket();
  if (IFACE_ENABLE==="1")
  {
    global $out_buffer;
    fclose($out_buffer);
  }
  if (defined("RESTART")==True)
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
      rawmsg("PONG ".trim($parts[1]));
      return True;
    }
  }
  return False;
}

#####################################################################################################

function parse_data($data)
{
  global $valid_data_cmd;
  # :<prefix> <command> <params> :<trailing>
  # the only required part of the message is the command name
  if ($data=="")
  {
    return False;
  }
  $sub=trim($data,"\n\r\0\x0B");
  $result["server"]=IRC_HOST;
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
    $result["trailing"]=substr($sub,$i+2);
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
  $mask=construct_mask($result);
  $cmd=$result["cmd"];
  $param_parts=explode(" ",$result["params"]);
  if (count($param_parts)==2)
  {
    if ((substr($param_parts[0],0,1)=="#") or (substr($param_parts[0],0,1)=="&"))
    {
      $result["destination"]=$param_parts[0];
    }
  }
  elseif (count($param_parts)==1)
  {
    $result["destination"]=$result["params"];
  }
  if ($cmd=="#")
  {
    return False;
  }
  if (is_numeric($cmd)==True)
  {
    $cmd="#";
  }
  if (isset($valid_data_cmd[$cmd])==False)
  {
    return False;
  }
  if (in_array($mask,$valid_data_cmd[$cmd])==False)
  {
    return False;
  }
  return $result;
}

#####################################################################################################

function construct_mask($items)
{
  $result="000";
  if ($items["prefix"]<>"")
  {
    $result[0]="1";
  }
  if ($items["params"]<>"")
  {
    $result[1]="1";
  }
  if ($items["trailing"]<>"")
  {
    $result[2]="1";
  }
  return $result;
}

#####################################################################################################

function process_scripts($items,$reserved="")
{
  global $handles;
  global $exec_list;
  global $alias_locks;
  global $reserved_aliases;
  global $bucket_locks;
  global $events_pause;
  if ($items===False)
  {
    return;
  }
  $nick=trim($items["nick"]);
  $destination=trim($items["destination"]);
  $data=$items["data"];
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
      array_shift($parts);
      $trailing=implode(" ",$parts);
    }
    if (in_array($alias,$reserved_aliases)==True)
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
  if ($exec_list[$alias]["enabled"]!==True)
  {
    return;
  }
  if (count($exec_list[$alias]["cmds"])>0)
  {
    if (in_array(strtoupper($cmd),$exec_list[$alias]["cmds"])==False)
    {
      term_echo("cmd-restricted alias \"$alias\" triggered on non-permitted cmd \"$cmd\" by \"$nick\"");
      return;
    }
  }
  if (count($exec_list[$alias]["dests"])>0)
  {
    if (in_array(strtolower($destination),$exec_list[$alias]["dests"])==False)
    {
      term_echo("dest-restricted alias \"$alias\" triggered from non-permitted dest \"$destination\" by \"$nick\"");
      return;
    }
  }
  if (count($exec_list[$alias]["servers"])>0)
  {
    if (in_array($items["server"],$exec_list[$alias]["servers"])==False)
    {
      term_echo("server-restricted alias \"$alias\" triggered from non-permitted server \"".$items["server"]."\" by \"$nick\"");
      return;
    }
  }
  if ((check_nick($items,$alias)==False) and (in_array($alias,$reserved_aliases)==False))
  {
    return;
  }
  if (($exec_list[$alias]["empty"]==0) and ($trailing=="") and ($destination<>"") and ($nick<>""))
  {
    #privmsg($destination,$nick,"alias \"$alias\" requires additional trailing argument");
    return;
  }
  $items_serialized=base64_encode(serialize($items));
  $template=$exec_list[$alias]["cmd"];
  $start=microtime(True);
  # TODO: %%trailing|$index[|$delimiter(space default)]%%
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TRAILING.TEMPLATE_DELIM,escapeshellarg($trailing),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_NICK.TEMPLATE_DELIM,escapeshellarg($nick),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DESTINATION.TEMPLATE_DELIM,escapeshellarg($destination),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_START.TEMPLATE_DELIM,escapeshellarg(START_TIME),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_ALIAS.TEMPLATE_DELIM,escapeshellarg($alias),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DATA.TEMPLATE_DELIM,escapeshellarg($data),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_ITEMS.TEMPLATE_DELIM,escapeshellarg($items_serialized),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_CMD.TEMPLATE_DELIM,escapeshellarg($cmd),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_PARAMS.TEMPLATE_DELIM,escapeshellarg($items["params"]),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TIMESTAMP.TEMPLATE_DELIM,escapeshellarg($start),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_SERVER.TEMPLATE_DELIM,escapeshellarg($items["server"]),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_USER.TEMPLATE_DELIM,escapeshellarg($items["user"]),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_HOSTNAME.TEMPLATE_DELIM,escapeshellarg($items["hostname"]),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_PREFIX.TEMPLATE_DELIM,escapeshellarg($items["prefix"]),$template);
  $command="exec ".$template;
  $command=$template;
  $cwd=NULL;
  $env=NULL;
  $descriptorspec=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
  $process=proc_open($command,$descriptorspec,$pipes,$cwd,$env);
  $status=proc_get_status($process);
  if ($alias<>ALIAS_ALL)
  {
    term_echo("EXEC [".$status["pid"]."]: ".$command);
  }
  $locks=$exec_list[$alias]["bucket_locks"];
  for ($i=0;$i<count($locks);$i++)
  {
    $index=$locks[$i];
    if (isset($bucket_locks[$index])==False)
    {
      $bucket_locks[$index]=array();
    }
    $bucket_locks[$index][]=$status["pid"];
    term_echo("BUCKET LOCK ADDED: $index BY PID ".$status["pid"]);
  }
  $handles[]=array(
    "process"=>$process,
    "command"=>$command,
    "pid"=>$status["pid"],
    "pipe_stdin"=>$pipes[0],
    "pipe_stdout"=>$pipes[1],
    "pipe_stderr"=>$pipes[2],
    "alias"=>$alias,
    "bucket_locks"=>$locks,
    "template"=>$exec_list[$alias]["cmd"],
    "allow_empty"=>$exec_list[$alias]["empty"],
    "timeout"=>$exec_list[$alias]["timeout"],
    "repeat"=>$exec_list[$alias]["repeat"],
    "auto_privmsg"=>$exec_list[$alias]["auto"],
    "start"=>$start,
    "nick"=>$items["nick"],
    "cmd"=>$items["cmd"],
    "destination"=>$items["destination"],
    "trailing"=>$trailing,
    "exec"=>$exec_list[$alias],
    "server"=>$items["server"],
    "items"=>$items);
  write_out_buffer_proc($handles[count($handles)-1],"","proc_start");
  stream_set_blocking($pipes[1],0);
  stream_set_blocking($pipes[2],0);
}

#####################################################################################################

function check_nick($items,$alias)
{
  global $time_deltas;
  if (($items["nick"]==get_bot_nick()) or ($alias=="*"))
  {
    return True;
  }
  if (($items["cmd"]<>"PRIVMSG") and ($items["cmd"]<>"NOTICE"))
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
    $data=":exec ".CMD_INTERNAL." :$alias";
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

function is_operator_alias($alias)
{
  global $exec_list;
  global $operator_aliases;
  if (in_array($alias,$operator_aliases)==True)
  {
    return True;
  }
  if (isset($exec_list[$alias]["accounts_wildcard"])==True)
  {
    if ($exec_list[$alias]["accounts_wildcard"]=="@")
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function is_admin_alias($alias)
{
  global $exec_list;
  global $admin_aliases;
  if (in_array($alias,$admin_aliases)==True)
  {
    return True;
  }
  if (isset($exec_list[$alias]["accounts_wildcard"])==True)
  {
    if ($exec_list[$alias]["accounts_wildcard"]=="+")
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function authenticate($items)
{
  global $exec_list;
  global $admin_data;
  global $admin_is_sock;
  global $admin_accounts;
  term_echo("\033[32mdetected cmd 330: $admin_data\033[0m");
  $parts=explode(" ",$items["params"]);
  if ($admin_data<>"")
  {
    if ((count($parts)==3) and ($parts[0]==get_bot_nick()))
    {
      $nick=$parts[1];
      $account=$parts[2];
      $admin_items=parse_data($admin_data);
      $args=explode(" ",$admin_items["trailing"]);
      $alias=$args[0];
      if ($admin_items["nick"]==$nick)
      {
        if (is_operator_alias($alias)==True)
        {
          if (($account<>OPERATOR_ACCOUNT) or ($admin_items["hostname"]<>OPERATOR_HOSTNAME))
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not authorized (1)");
          }
          else
          {
            $tmp_data=$admin_data;
            $tmp_is_sock=$admin_is_sock;
            $admin_data="";
            $admin_is_sock="";
            handle_data($tmp_data,$tmp_is_sock,True);
            return;
          }
        }
        elseif (is_admin_alias($alias)==True)
        {
          if ((($account<>OPERATOR_ACCOUNT) or ($admin_items["hostname"]<>OPERATOR_HOSTNAME)) and (in_array($account,$admin_accounts)==False))
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not authorized (2)");
          }
          else
          {
            $tmp_data=$admin_data;
            $tmp_is_sock=$admin_is_sock;
            $admin_data="";
            $admin_is_sock="";
            handle_data($tmp_data,$tmp_is_sock,True);
            return;
          }
        }
        elseif (has_account_list($alias)==True)
        {
          if ((($account<>OPERATOR_ACCOUNT) or ($admin_items["hostname"]<>OPERATOR_HOSTNAME)) and (in_array($account,$exec_list[$alias]["accounts"])==False) and ($exec_list[$alias]["accounts_wildcard"]<>"*") and (in_array($account,$admin_accounts)==False))
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not authorized (3)");
          }
          else
          {
            $tmp_data=$admin_data;
            $tmp_is_sock=$admin_is_sock;
            $admin_data="";
            $admin_is_sock="";
            handle_data($tmp_data,$tmp_is_sock,True);
            return;
          }
        }
      }
    }
  }
  $admin_data="";
  $admin_is_sock="";
}

#####################################################################################################

function ps($items)
{
  global $handles;
  $n=0;
  foreach ($handles as $index => $data)
  {
    if ($data["alias"]=="*")
    {
      continue;
    }
    $n++;
    privmsg($items["destination"],$items["nick"],"[".$data["pid"]."] ".$data["command"]);
  }
  if ($n==0)
  {
    privmsg($items["destination"],$items["nick"],"no child processes currently running");
  }
}

#####################################################################################################

function killall($items)
{
  global $handles;
  if (count($handles)==0)
  {
    privmsg($items["destination"],$items["nick"],"no child processes currently running");
  }
  $messages=array();
  foreach ($handles as $index => $handle)
  {
    if ($handle["alias"]==ALIAS_ALL)
    {
      continue;
    }
    if (kill_process($handle)==True)
    {
      $messages[]="terminated pid ".$handle["pid"].": ".$handle["command"];
      unset($handles[$index]);
    }
    else
    {
      $messages[]="error terminating pid ".$handle["pid"].": ".$handle["command"];
    }
  }
  $handles=array_values($handles);
  for ($i=0;$i<count($messages);$i++)
  {
    privmsg($items["destination"],$items["nick"],$messages[$i]);
  }
}

#####################################################################################################

function kill($items,$pid)
{
  global $handles;
  foreach ($handles as $index => $handle)
  {
    if ($handle["pid"]==$pid)
    {
      if (kill_process($handle)==True)
      {
        unset($handles[$index]);
        privmsg($items["destination"],$items["nick"],"successfully terminated process with pid $pid");
      }
      else
      {
        privmsg($items["destination"],$items["nick"],"error terminating process with pid $pid");
      }
      return;
    }
  }
  privmsg($items["destination"],$items["nick"],"unable to find process with pid $pid");
}

#####################################################################################################

function kill_process($handle)
{
  write_out_buffer_proc($handle,"","proc_kill");
  $lines=explode("\n",shell_exec("ps -aF"));
  kill_recurse($handle["pid"],$lines);
  proc_close($handle["process"]);
  return True;
}

#####################################################################################################

function kill_recurse($pid,$lines)
{
  for ($i=0;$i<count($lines);$i++)
  {
    $parts=explode(" ",trim($lines[$i]));
    for ($j=0;$j<count($parts);$j++)
    {
      if (trim($parts[$j])=="")
      {
        unset($parts[$j]);
      }
    }
    $parts=array_values($parts);
    if (count($parts)<3)
    {
      continue;
    }
    $ipid=trim($parts[1]);
    $ppid=trim($parts[2]);
    if (($ppid<>$pid) or ($ipid==""))
    {
      continue;
    }
    echo "*** CHILD PROCESS FOUND: ".$lines[$i]."\n";
    kill_recurse($ipid,$lines);
  }
  echo "*** KILLING PROCESS ID $pid\n";
  posix_kill($pid,SIGKILL);
}

#####################################################################################################

function delete_empty_elements(&$array)
{
  for ($i=0;$i<count($array);$i++)
  {
    if ($array[$i]=="")
    {
      unset($array[$i]);
    }
  }
  $array=array_values($array);
}

#####################################################################################################

function load_directory($dir,&$lines,$directive)
{
  # TODO: USE scandir FUNCTION
  if ((file_exists($dir)==True) and (is_dir($dir)==True))
  {
    term_echo("load_directory: \"$dir\" found");
    $handle=opendir($dir);
    while (($file=readdir($handle))!==False)
    {
      if (($file==".") or ($file==".."))
      {
        continue;
      }
      $fullname=$dir."/".$file;
      if (is_dir($fullname)==True)
      {
        load_directory($fullname,$lines,$directive);
      }
      else
      {
        load_include($fullname,$lines,$directive);
      }
    }
    closedir($handle);
  }
  else
  {
    term_echo("load_directory: \"$dir\" not found");
  }
}

#####################################################################################################

?>
