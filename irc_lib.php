<?php

# gpl2
# by crutchy

#####################################################################################################

function init()
{
  $items=parse_data("INIT");
  process_scripts($items,ALIAS_INIT);
}

#####################################################################################################

function startup()
{
  $items=parse_data("STARTUP");
  process_scripts($items,ALIAS_STARTUP);
}

#####################################################################################################

function get_valid_data_cmd($allow_customs=True)
{
  /*
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
    "INVITE"=>array("111"),
    "JOIN"=>array("110"),
    "KICK"=>array("110","111"),
    "KILL"=>array("101"),
    "MODE"=>array("101","110","111"),
    "NICK"=>array("101"),
    "NOTICE"=>array("111"),
    "PART"=>array("110","111"),
    "PRIVMSG"=>array("111"),
    "QUIT"=>array("100","101"),
    "PONG"=>array("110","111"));
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
    "INIT"=>array("000"),
    "STARTUP"=>array("000"),
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
  $msg=" ~list ~list-auth ~log ~lock ~unlock";
  privmsg($items["destination"],$items["nick"],$msg);
  $msg="";
  foreach ($exec_list as $alias => $data)
  {
    if ((count($data["accounts"])==0) and (strlen($alias)<=20) and (in_array($alias,$reserved_aliases)==False) and ((count($data["cmds"])==0) or (in_array("PRIVMSG",$data["cmds"])==True)))
    {
      if ($msg<>"")
      {
        $msg=$msg." ";
      }
      $msg=$msg.$alias;
    }
  }
  privmsg($items["destination"],$items["nick"]," ".$msg);
}

#####################################################################################################

function get_list_auth($items)
{
  global $exec_list;
  global $reserved_aliases;
  $msg=" ~q ~rehash ~ps ~kill ~killall ~dest-override ~dest-clear ~buckets-dump ~buckets-save ~buckets-load ~buckets-flush ~buckets-list ~restart ~ignore ~unignore";
  privmsg($items["destination"],$items["nick"],$msg);
  $msg="";
  foreach ($exec_list as $alias => $data)
  {
    if ((count($data["accounts"])>0) and (strlen($alias)<=20) and (in_array($alias,$reserved_aliases)==False) and ((count($data["cmds"])==0) or (in_array("PRIVMSG",$data["cmds"])==True)))
    {
      if ($msg<>"")
      {
        $msg=$msg." ";
      }
      $msg=$msg.$alias;
    }
  }
  privmsg($items["destination"],$items["nick"]," ".$msg);
}

#####################################################################################################

function log_data($data)
{
  $filename=EXEC_LOG_PATH.date("Ymd",time()).".txt";
  $line="<<".date("Y-m-d H:i:s",microtime(True)).">> ".trim($data,"\n\r\0\x0B")."\n";
  file_put_contents($filename,$line,FILE_APPEND);
}

#####################################################################################################

function log_items($items)
{
  global $buckets;
  $dest=$items["destination"];
  if (isset($buckets[BUCKET_LOGGED_CHANS])==False)
  {
    $buckets[BUCKET_LOGGED_CHANS]=serialize(array());
  }
  $logged_chans=unserialize($buckets[BUCKET_LOGGED_CHANS]);
  if (isset($logged_chans[$dest])==True)
  {
    if ($logged_chans[$dest]<>"on")
    {
      return;
    }
  }
  else
  {
    return;
  }
  process_scripts($items,ALIAS_LOG_ITEMS);
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
        $nick=NICK;
        handle_data(":$nick ".CMD_INTERNAL." :".$prefix_msg."\n",False,False,True);
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
    fclose($handle["pipe_stdin"]);
    fclose($handle["pipe_stdout"]);
    fclose($handle["pipe_stderr"]);
    proc_close($handle["process"]);
    if (($handle["alias"]<>ALIAS_ALL) and ($handle["alias"]<>ALIAS_LOG_ITEMS))
    {
      #term_echo("process terminated normally: ".$handle["command"]);
    }
    return False;
  }
  if ($handle["timeout"]>0)
  {
    if ((microtime(True)-$handle["start"])>$handle["timeout"])
    {
      kill_process($handle);
      term_echo("process timed out: ".$handle["command"]);
      if ((in_array($handle["alias"],$reserved_aliases)==False) and (in_array($handle["cmd"],$silent_timeout_commands)==False))
      {
        privmsg($handle["destination"],$handle["nick"],"process timed out: ".$handle["alias"]." ".$handle["trailing"]);
      }
      return False;
    }
  }
  return True;
}

#####################################################################################################

function handle_stdout($handle)
{
  global $irc_pause;
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
  if (trim($buf)==DIRECTIVE_QUIT)
  {
    doquit();
  }
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  log_data($msg);
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
    if ($prefix_msg<>"")
    {
      switch ($prefix)
      {
        case PREFIX_IRC:
          rawmsg($prefix_msg);
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
          $nick=$handle["nick"];
          if ($nick=="")
          {
            $nick=NICK;
          }
          if ($handle["destination"]=="")
          {
            handle_data(":$nick ".CMD_INTERNAL." :".$prefix_msg."\n");
          }
          else
          {
            handle_data(":$nick ".CMD_INTERNAL." ".$handle["destination"]." :".$prefix_msg."\n");
          }
          return;
        case PREFIX_PAUSE:
          $irc_pause=True;
          return;
        case PREFIX_UNPAUSE:
          $irc_pause=False;
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
  $msg=$buf;
  if (substr($msg,strlen($msg)-1)=="\n")
  {
    $msg=substr($msg,0,strlen($msg)-1);
  }
  log_data($msg);
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
    case CMD_BUCKET_GET:
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
          #term_echo("BUCKET_GET [$index]: SUCCESS ($size)");
        }
      }
      else
      {
        handle_stdin($handle,"\n");
        #term_echo("BUCKET_GET [$index]: BUCKET NOT SET");
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
        unset($parts[0]);
        $trailing=implode(" ",$parts);
        $buckets[$index]=$trailing;
        #term_echo("BUCKET_SET [$index]: SUCCESS");
      }
      return True;
    case CMD_BUCKET_UNSET:
      $index=$trailing;
      if (isset($buckets[$index])==True)
      {
        unset($buckets[$index]);
        #term_echo("BUCKET_UNSET [$index]: SUCCESS");
      }
      else
      {
        #term_echo("BUCKET_UNSET [$index]: BUCKET NOT SET");
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
        unset($parts[0]);
        $trailing=implode(" ",$parts);
        $bucket_array=array();
        if (isset($buckets[$index])==True)
        {
          $bucket_array=unserialize($buckets[$index]);
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
        $bucket_string=serialize($bucket_array);
        if ($bucket_string!==False)
        {
          $buckets[$index]=$bucket_string;
          #term_echo("BUCKET_APPEND [$index]: SUCCESS");
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
  global $irc_pause;
  if ($irc_pause==True)
  {
    return;
  }
  if (is_resource($socket)==False)
  {
    return;
  }
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
    if ((count($exec_list[$alias]["accounts"])>0) or ($exec_list[$alias]["accounts_wildcard"]<>""))
    {
      return True;
    }
  }
  return False;
}

#####################################################################################################

function handle_join($nick,$channel)
{
  global $buckets;
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_join: empty nick or channel");
    return;
  }
  term_echo("*** USERS: handle_join: nick=$nick, channel=$channel");
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  $users[$nick]["channels"][$channel]=microtime(True);
  $buckets[BUCKET_USERS]=serialize($users);
}

#####################################################################################################

function handle_kick($trailing) # <channel> <kicked_nick>
{
  global $buckets;
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
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  if (isset($users[$kicked_nick]["channels"][$channel])==True)
  {
    unset($users[$kicked_nick]["channels"][$channel]);
    $buckets[BUCKET_USERS]=serialize($users);
  }
  else
  {
    term_echo("*** USERS: handle_kick: bucket data not found");
  }
}

#####################################################################################################

function handle_nick($old_nick,$new_nick)
{
  global $buckets;
  if (($old_nick=="") or ($new_nick==""))
  {
    return;
  }
  term_echo("*** USERS: handle_nick: old_nick=$old_nick, new_nick=$new_nick");
  $user=array();
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
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
  $buckets[BUCKET_USERS]=serialize($users);
}

#####################################################################################################

function handle_part($nick,$channel)
{
  global $buckets;
  if (($nick=="") or ($channel==""))
  {
    term_echo("*** USERS: handle_part: empty channel or nick");
    return;
  }
  term_echo("*** USERS: handle_part: nick=$nick, channel=$channel");
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  if (isset($users[$nick]["channels"][$channel])==True)
  {
    unset($users[$nick]["channels"][$channel]);
    $buckets[BUCKET_USERS]=serialize($users);
  }
  else
  {
    term_echo("*** USERS: handle_part: bucket data not found");
  }
}

#####################################################################################################

function handle_quit($nick)
{
  global $buckets;
  if ($nick=="")
  {
    term_echo("*** USERS: handle_quit: empty nick");
    return;
  }
  term_echo("*** USERS: handle_quit: nick=$nick");
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  if (isset($users[$nick])==True)
  {
    unset($users[$nick]);
    $buckets[BUCKET_USERS]=serialize($users);
  }
  else
  {
    term_echo("*** USERS: handle_quit: bucket data not found");
  }
}

#####################################################################################################

function handle_319($trailing) # <calling_nick> <subject_nick> <chan1> <+chan2> <@chan3>
{
  global $buckets;
  $parts=explode(" ",$trailing);
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
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  for ($i=2;$i<count($parts);$i++)
  {
    $channel=$parts[$i];
    if ($channel=="")
    {
      term_echo("*** USERS: handle_319: empty channel");
      continue;
    }
    $auth=$channel[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $channel=substr($channel,1);
      if ($channel=="")
      {
        term_echo("*** USERS: handle_319: empty auth channel");
        continue;
      }
    }
    term_echo("*** USERS: handle_319: subject_nick=$subject_nick, channel=$channel");
    $users[$subject_nick]["channels"][$channel]=microtime(True);
  }
  $buckets[BUCKET_USERS]=serialize($users);
}

#####################################################################################################

function handle_330($trailing) # <calling_nick> <subject_nick> <account>
{
  global $buckets;
  $parts=explode(" ",$trailing);
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
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  $users[$subject_nick]["account"]=$account;
  $users[$subject_nick]["account_updated"]=microtime(True);
  $buckets[BUCKET_USERS]=serialize($users);
}

#####################################################################################################

function handle_353($trailing) # <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
{
  global $buckets;
  $parts=explode(" ",$trailing);
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
  if (isset($buckets[BUCKET_USERS])==True)
  {
    $users=unserialize($buckets[BUCKET_USERS]);
  }
  else
  {
    $users=array();
  }
  for ($i=3;$i<count($parts);$i++)
  {
    $nick=$parts[$i];
    if ($nick=="")
    {
      term_echo("*** USERS: handle_353: empty nick");
      continue;
    }
    $auth=$nick[0];
    if (($auth=="+") or ($auth=="@"))
    {
      $nick=substr($nick,1);
      if ($nick=="")
      {
        term_echo("*** USERS: handle_353: empty auth nick");
        continue;
      }
    }
    $users[$nick]["channels"][$channel]=microtime(True);
  }
  $buckets[BUCKET_USERS]=serialize($users);
}

#####################################################################################################

function handle_events(&$items)
{
  $cmd=strtoupper(trim($items["cmd"]));
  $nick=strtolower(trim($items["nick"]));
  $params=strtolower(trim($items["params"]));
  $trailing=strtolower(trim($items["trailing"]));
  switch ($cmd)
  {
    case "JOIN":
      # :exec!~exec@709-27-2-01.cust.aussiebb.net JOIN #
      handle_join($nick,$params);
      script_event_handlers($cmd,$items);
      break;
    case "KICK":
      # :NCommander!~mcasadeva@Soylent/Staff/Sysop/mcasadevall KICK #staff exec :gravel test
      # :exec!~exec@709-27-2-01.cust.aussiebb.net KICK #comments Loggie :commanded by crutchy
      handle_kick($trailing);
      script_event_handlers($cmd,$items);
      break;
    case "KILL":
      script_event_handlers($cmd,$items);
      break;
    case "NICK":
      # :Landon_!~Landon@Soylent/Staff/IRC/Landon NICK :Landon
      handle_nick($nick,$trailing);
      script_event_handlers($cmd,$items);
      break;
    case "PART":
      # :Drop!~Drop___@via1-vhat2-0-3-jppz214.perr.cable.virginm.net PART #Soylent :Leaving
      handle_part($nick,$trailing);
      script_event_handlers($cmd,$items);
      break;
    case "QUIT":
      handle_quit($nick);
      script_event_handlers($cmd,$items);
      break;
    case "319":
      # :irc.sylnt.us 319 exec crutchy :#wiki +#test #sublight #help @#exec #derp @#civ @#1 @#0 ## @#/ @#> @#~ @#
      handle_319("$params $trailing");
      script_event_handlers($cmd,$items);
      break;
    case "330":
      # :irc.sylnt.us 330 exec crutchy_ crutchy :is logged in as
      handle_330($params);
      script_event_handlers($cmd,$items);
      break;
    case "353":
      # :irc.sylnt.us 353 exec = #civ :exec @crutchy chromas arti
      handle_353("$params $trailing");
      script_event_handlers($cmd,$items);
      break;
  }
}

#####################################################################################################

function script_event_handlers($cmd,&$items)
{
  global $buckets;
  $event_handlers=array();
  if (isset($buckets[BUCKET_EVENT_HANDLERS])==True)
  {
    $event_handlers=unserialize($buckets[BUCKET_EVENT_HANDLERS]);
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
  global $admin_aliases;
  global $exec_list;
  global $throttle_time;
  global $ignore_list;
  if ($auth==False)
  {
    echo "\033[33m".date("Y-m-d H:i:s",microtime(True))." > \033[0m$data";
    log_data($data);
  }
  else
  {
    term_echo("*** auth = true");
  }
  $items=parse_data($data);
  if ($items!==False)
  {
    if (($auth==False) and ($is_sock==True))
    {
      log_items($items);
    }
    if (in_array($items["nick"],$ignore_list)==True)
    {
      return;
    }
    if ((isset($buckets[BUCKET_IGNORE_NEXT])==True) and ($items["nick"]==NICK))
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
    if ($items["cmd"]==330) # is logged in as
    {
      authenticate($items);
    }
    if ($items["cmd"]==376) # RPL_ENDOFMOTD (RFC1459)
    {
      dojoin(INIT_CHAN_LIST);
    }
    if (($items["cmd"]=="NOTICE") and ($items["nick"]=="NickServ") and ($items["trailing"]=="You have 60 seconds to identify to your nickname before it is changed."))
    {
      rawmsg("NickServ IDENTIFY ".trim(file_get_contents(PASSWORD_FILE)),True);
      startup();
    }
    $args=explode(" ",$items["trailing"]);
    if ((in_array($args[0],$admin_aliases)==True) or (has_account_list($args[0])==True))
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
      case ALIAS_ADMIN_QUIT:
        if (count($args)==1)
        {
          process_scripts($items,ALIAS_QUIT);
        }
        break;
      case ALIAS_ADMIN_PS:
        if (count($args)==1)
        {
          ps($items);
        }
        break;
      case ALIAS_ADMIN_KILL:
        if (count($args)==2)
        {
          kill($items,$args[1]);
        }
        break;
      case ALIAS_ADMIN_KILLALL:
        if (count($args)==1)
        {
          killall($items);
        }
        break;
      case ALIAS_LIST:
        if (check_nick($items,$alias)==True)
        {
          if (count($args)==1)
          {
            get_list($items);
          }
        }
        break;
      case ALIAS_LIST_AUTH:
        if (check_nick($items,$alias)==True)
        {
          if (count($args)==1)
          {
            get_list_auth($items);
          }
        }
        break;
      case ALIAS_LOCK:
        if (check_nick($items,$alias)==True)
        {
          if (count($args)==2)
          {
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
          privmsg($items["destination"],$items["nick"],"alias \"".$alias_locks[$items["nick"]][$items["destination"]]."\" unlocked for nick \"".$items["nick"]."\" in \"".$items["destination"]."\"");
          unset($alias_locks[$items["nick"]][$items["destination"]]);
        }
        break;
      case ALIAS_LOG:
        if (check_nick($items,$alias)==True)
        {
          if (count($args)==2)
          {
            $state=strtolower($args[1]);
            $dest=$items["destination"];
            if (($state=="on") or ($state=="off"))
            {
              $bucket_chans=unserialize($buckets[BUCKET_LOGGED_CHANS]);
              $bucket_chans[$dest]=$state;
              $buckets[BUCKET_LOGGED_CHANS]=serialize($bucket_chans);
              if ($state=="on")
              {
                privmsg($dest,$items["nick"],"logging enabled for ".chr(3)."8".$dest.chr(3));
                privmsg($dest,$items["nick"],IRC_LOG_URL);
              }
              else
              {
                privmsg($dest,$items["nick"],"logging disabled for ".chr(3)."8".$dest.chr(3));
              }
            }
            else
            {
              privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_LOG." on|off");
            }
          }
          else
          {
            privmsg($items["destination"],$items["nick"],"syntax: ".ALIAS_LOG." on|off");
          }
        }
        break;
      case ALIAS_ADMIN_DEST_OVERRIDE:
        if (count($args)==2)
        {
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
            privmsg($items["destination"],$items["nick"],NICK." set to ignore ".$args[1]);
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
              privmsg($items["destination"],$items["nick"],NICK." set to listen to ".$args[1]);
              unset($ignore_list[$i]);
              $ignore_list=array_values($ignore_list);
              if (file_put_contents(IGNORE_FILE,implode("\n",$ignore_list))===False)
              {
                privmsg($items["destination"],$items["nick"],"error saving ignore file");
              }
            }
            else
            {
              privmsg($items["destination"],$items["nick"],$args[1]." not found in ".NICK." ignore list");
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
          privmsg($items["destination"],$items["nick"],NICK." ignore list: ".implode(", ",$ignore_list));
        }
        else
        {
          privmsg($items["destination"],$items["nick"],NICK." isn't ignoring anyone");
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
            privmsg($items["destination"],$items["nick"],"successfully reloaded exec file (".count($exec_list)." aliases)");
          }
        }
        break;
      case ALIAS_ADMIN_BUCKETS_DUMP:
        if (count($args)==1)
        {
          buckets_dump($items);
        }
        break;
      case ALIAS_ADMIN_BUCKETS_SAVE:
        if (count($args)==1)
        {
          buckets_save($items);
        }
        break;
      case ALIAS_ADMIN_BUCKETS_LOAD:
        if (count($args)==1)
        {
          buckets_load($items);
        }
        break;
      case ALIAS_ADMIN_BUCKETS_FLUSH:
        if (count($args)==1)
        {
          buckets_flush($items);
        }
        break;
      case ALIAS_ADMIN_BUCKETS_LIST:
        if (count($args)==1)
        {
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
          define("RESTART",True);
          process_scripts($items,ALIAS_QUIT);
        }
        break;
      default:
        process_scripts($items); # execute scripts occurring for a specific alias
        process_scripts($items,ALIAS_ALL); # process scripts occuring for every line (* alias)
    }
  }
}

#####################################################################################################

function exec_load()
{
  global $exec_list;
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
  $data=explode("\n",$data);
  for ($i=0;$i<count($data);$i++)
  {
    $line=trim($data[$i]);
    if ($line=="")
    {
      term_echo("EXEC LINE ERROR 1: $line");
      continue;
    }
    if (substr($line,0,1)=="#")
    {
      term_echo("EXEC LINE ERROR 2: $line");
      continue;
    }
    $parts=explode(EXEC_DELIM,$line);
    if (count($parts)<10)
    {
      term_echo("EXEC LINE ERROR 3: $line");
      continue;
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
      if ($accounts_str=="*")
      {
        $accounts_wildcard="*";
      }
      else
      {
        $accounts=explode(",",$accounts_str); # comma-delimited list of NickServ accounts authorised to run script (or empty)
        if (in_array(NICK,$accounts)==False)
        {
          $accounts[]=NICK;
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
    $reserved=trim($parts[8]); # reserved for later use (0 = no, 1 = yes)
    for ($j=0;$j<=8;$j++)
    {
      array_shift($parts);
    }
    $cmd=trim(implode("|",$parts)); # shell command
    if (($alias=="") or (is_numeric($timeout)==False) or (is_numeric($repeat)==False) or (($auto<>"0") and ($auto<>"1")) or (($empty<>"0") and ($empty<>"1")) or (($reserved<>"0") and ($reserved<>"1")) or ($cmd==""))
    {
      term_echo("EXEC LINE ERROR 4: $line");
      continue;
    }

    /*$cmd_parts=explode(" ",$cmd);
    if (count($cmd_parts)*/

    $exec_list[$alias]["timeout"]=$timeout;
    $exec_list[$alias]["repeat"]=$repeat;
    $exec_list[$alias]["auto"]=$auto;
    $exec_list[$alias]["empty"]=$empty;
    $exec_list[$alias]["accounts"]=$accounts;
    $exec_list[$alias]["accounts_wildcard"]=$accounts_wildcard;
    $exec_list[$alias]["cmds"]=$cmds;
    $exec_list[$alias]["dests"]=$dests;
    $exec_list[$alias]["reserved"]=$reserved;
    $exec_list[$alias]["cmd"]=$cmd;
  }
  return $exec_list;
}

#####################################################################################################

function doquit()
{
  global $handles;
  global $socket;
  global $argv;
  sleep(3);
  $n=count($handles);
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
  rawmsg("NickServ LOGOUT");
  rawmsg("QUIT :dafuq");
  fclose($socket);
  term_echo("QUITTING SCRIPT");
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
      if (isset($exec_list[$alias])==False)
      {
        return;
      }
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
  if ((check_nick($items,$alias)==False) and (in_array($alias,$reserved_aliases)==False))
  {
    return;
  }
  if (($exec_list[$alias]["empty"]==0) and ($trailing=="") and ($destination<>"") and ($nick<>""))
  {
    privmsg($destination,$nick,"alias \"$alias\" requires additional trailing argument");
    return;
  }
  $template=$exec_list[$alias]["cmd"];
  $start=microtime(True);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TRAILING.TEMPLATE_DELIM,escapeshellarg($trailing),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_NICK.TEMPLATE_DELIM,escapeshellarg($nick),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DESTINATION.TEMPLATE_DELIM,escapeshellarg($destination),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_START.TEMPLATE_DELIM,escapeshellarg(START_TIME),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_ALIAS.TEMPLATE_DELIM,escapeshellarg($alias),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_DATA.TEMPLATE_DELIM,escapeshellarg($data),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_CMD.TEMPLATE_DELIM,escapeshellarg($cmd),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_PARAMS.TEMPLATE_DELIM,escapeshellarg($items["params"]),$template);
  $template=str_replace(TEMPLATE_DELIM.TEMPLATE_TIMESTAMP.TEMPLATE_DELIM,escapeshellarg($start),$template);
  $command="exec ".$template;
  $command=$template;
  $cwd=NULL;
  $env=NULL;
  $descriptorspec=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
  if (($alias<>ALIAS_ALL) and ($alias<>ALIAS_LOG_ITEMS))
  {
    term_echo("EXEC: ".$command);
  }
  $process=proc_open($command,$descriptorspec,$pipes,$cwd,$env);
  $status=proc_get_status($process);
  $handles[]=array(
    "process"=>$process,
    "command"=>$command,
    "pid"=>$status["pid"],
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
    "cmd"=>$items["cmd"],
    "destination"=>$items["destination"],
    "trailing"=>$trailing);
  stream_set_blocking($pipes[1],0);
  stream_set_blocking($pipes[2],0);
}

#####################################################################################################

function check_nick($items,$alias)
{
  global $time_deltas;
  if (($items["nick"]==NICK) or ($alias=="*"))
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

function authenticate($items)
{
  global $exec_list;
  global $admin_data;
  global $admin_is_sock;
  global $admin_accounts;
  global $admin_aliases;
  term_echo("\033[32mdetected cmd 330: $admin_data\033[0m");
  $parts=explode(" ",$items["params"]);
  if ($admin_data<>"")
  {
    if ((count($parts)==3) and ($parts[0]==NICK))
    {
      $nick=$parts[1];
      $account=$parts[2];
      $admin_items=parse_data($admin_data);
      $args=explode(" ",$admin_items["trailing"]);
      $alias=$args[0];
      if ($admin_items["nick"]==$nick)
      {
        if (in_array($alias,$admin_aliases)==True)
        {
          if (in_array($account,$admin_accounts)==False)
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not in admin account list");
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
          if ((in_array($account,$exec_list[$alias]["accounts"])==False) and ($exec_list[$alias]["accounts_wildcard"]<>"*") and (in_array($account,$admin_accounts)==False))
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is in neither exec line account list nor admin account list");
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
  #fclose($handle["pipe_stdin"]);
  #fclose($handle["pipe_stdout"]);
  #fclose($handle["pipe_stderr"]);
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

?>
