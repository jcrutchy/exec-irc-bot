<?php

# gpl2
# by crutchy
# 12-july-2014

#####################################################################################################

function init()
{
  $items=parse_data("INIT");
  process_scripts($items,ALIAS_INIT);
}

#####################################################################################################

function get_valid_data_cmd()
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
    CMD_INTERNAL=>array("100","101","110","111"),
    CMD_BUCKET_GET=>array("001","101"),
    CMD_BUCKET_SET=>array("001","101"),
    CMD_BUCKET_UNSET=>array("001","101"),
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
    "QUIT"=>array("100","101"));
  return $result;
}

#####################################################################################################

function get_list($items)
{
  global $exec_list;
  $msg="~list ~list-auth ~log ~lock ~unlock";
  privmsg($items["destination"],$items["nick"],$msg);
  $msg="";
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
  $msg="~q ~reload ~dest-override ~dest-clear ~buckets-dump ~buckets-save ~buckets-load ~buckets-flush ~buckets-list ~restart";
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

function log_data($data,$dest="")
{
  global $log_chans;
  if ($dest=="")
  {
    $filename=EXEC_LOG_PATH.date("Ymd",time()).".txt";
    $line="<<".date("Y-m-d H:i:s",microtime(True)).">> ".trim($data,"\n\r\0\x0B")."\n";
    file_put_contents($filename,$line,FILE_APPEND);
  }
  else
  {
    if (isset($log_chans[$dest])==True)
    {
      if ($log_chans[$dest]=="off")
      {
        return;
      }
    }
    else
    {
      return;
    }
    if (file_exists(IRC_LOG_INDEX_FILE)==False)
    {
      file_put_contents(IRC_LOG_INDEX_FILE,IRC_INDEX_SOURCE);
    }
    if (file_exists(IRC_LOG_INDEX_FILE_HTML)==False)
    {
      file_put_contents(IRC_LOG_INDEX_FILE_HTML,IRC_INDEX_HTML_HEAD);
    }
    $contents=file_get_contents(IRC_LOG_INDEX_FILE_HTML);
    $chan_enc=urlencode($dest);
    if (strpos($contents,$dest)===False)
    {
      $line="<a href=\"index_$chan_enc.html\">$dest</a><br>\n";
      file_put_contents(IRC_LOG_INDEX_FILE_HTML,$line,FILE_APPEND);
    }
    $timestamp=date("H:i:s",microtime(True));
    $timestamp_name=date("His",microtime(True));
    $filename=IRC_LOG_PATH.$dest."_".date("Ymd",time()).".html";
    $filename_href=urlencode($dest)."_".date("Ymd",time()).".html";
    $href_caption=date("Y-m-d",time());
    $line="<a href=\"#$timestamp_name\" name=\"$timestamp_name\" class=\"time\">[$timestamp]</a> ".trim($data,"\n\r\0\x0B")."<br>\n";
    if (file_exists($filename)==False)
    {
      $chan_index_filename=IRC_LOG_PATH."index_".$dest.".html";
      if (file_exists($chan_index_filename)==False)
      {
        $head=IRC_CHAN_INDEX_HEAD;
        $head=str_replace("%%title%%","$dest | SoylentNews IRC Log",$head);
        file_put_contents($chan_index_filename,$head);
      }
      $contents=file_get_contents($chan_index_filename);
      if (strpos($contents,$filename_href)===False)
      {
        $line_chan_index="<a href=\"$filename_href\">$href_caption</a><br>\n";
        file_put_contents($chan_index_filename,$line_chan_index,FILE_APPEND);
      }
      $head=IRC_LOG_HEAD;
      $head=str_replace("%%title%%","$dest | $href_caption",$head);
      $head=str_replace("%%index_href%%","index_$chan_enc.html",$head);
      file_put_contents($filename,$head);
    }
    file_put_contents($filename,$line,FILE_APPEND);
  }
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
      term_echo("process terminated normally: ".$handle["command"]);
    }
    return False;
  }
  if ($handle["timeout"]>=0)
  {
    if ((microtime(True)-$handle["start"])>$handle["timeout"])
    {
      proc_close($handle["process"]);
      term_echo("process timed out: ".$handle["command"]);
      privmsg($handle["destination"],$handle["nick"],"process timed out: ".$handle["trailing"]);
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
        case PREFIX_INTERNAL:
          handle_data(":".$handle["nick"]." ".CMD_INTERNAL." ".$handle["destination"]." :".$prefix_msg."\n");
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
          term_echo("BUCKET_GET [$index]: SUCCESS ($size)");
        }
      }
      else
      {
        handle_stdin($handle,"\n");
        term_echo("BUCKET_GET [$index]: BUCKET NOT SET");
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
        term_echo("BUCKET_SET [$index]: SUCCESS");
      }
      return True;
    case CMD_BUCKET_UNSET:
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

function handle_data($data,$is_sock=False,$auth=False,$exec=False)
{
  global $log_chans;
  global $alias_locks;
  global $dest_overrides;
  global $admin_accounts;
  global $admin_data;
  global $admin_is_sock;
  global $admin_aliases;
  global $exec_list;
  global $throttle_flag;
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
    if (($auth==False) and ($is_sock==True) and ($items["destination"]<>"") and ($items["nick"]<>"") and (trim($items["trailing"])<>"") and (substr($items["destination"],0,1)=="#") and (strpos($items["destination"]," ")===False))
    {
      $log_msg="&lt;".$items["nick"]."&gt; ".$items["trailing"];
      log_data($log_msg,$items["destination"]);
    }
    if (($items["prefix"]==IRC_HOST) and (strpos(strtolower($items["trailing"]),"throttled due to flooding")!==False))
    {
      $throttle_flag=True;
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
    }
    $args=explode(" ",$items["trailing"]);
    if ((in_array($args[0],$admin_aliases)==True) or (has_account_list($args[0])==True))
    {
      if ($auth==False)
      {
        term_echo("authenticating \"".$args[0]."\"...");
        $admin_data=$items["data"];
        $admin_is_sock=$is_sock;
        rawmsg("WHOIS ".$items["nick"]);
        return;
      }
    }
    $alias=$args[0];
    switch ($alias)
    {
      case ALIAS_ADMIN_QUIT:
        if (count($args)==1)
        {
          process_scripts($items,ALIAS_QUIT);
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
              $log_chans[$dest]=$state;
              privmsg($dest,$items["nick"],"logging for ".chr(3)."8".$dest.chr(3)." is $state");
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
      case ALIAS_ADMIN_RELOAD:
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

function rawmsg($msg,$obfuscate=False)
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
      continue;
    }
    if (substr($line,0,1)=="#")
    {
      continue;
    }
    $parts=explode(EXEC_DELIM,$line);
    if (count($parts)<9)
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
      if (in_array(NICK,$accounts)==False)
      {
        $accounts[]=NICK;
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
    for ($j=0;$j<=7;$j++)
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
    $exec_list[$alias]["cmds"]=$cmds;
    $exec_list[$alias]["dests"]=$dests;
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
  $n=count($handles);
  for ($i=0;$i<$n;$i++)
  {
    if (isset($handles[$i])==True) # have had a "Undefined offset: 0" notice on this line
    {
      if (is_resource($handles[$i]["process"])==True)
      {
        proc_close($handles[$i]["process"]);
      }
    }
  }
  rawmsg("NickServ LOGOUT");
  rawmsg("QUIT");
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

function term_echo($msg)
{
  echo "\033[33m".date("Y-m-d H:i:s",microtime(True))." > \033[31m$msg\033[0m\n";
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
  $mask=construct_mask($result);
  $cmd=$result["cmd"];
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
  term_echo("PRIVMSG: ".$msg);
}

#####################################################################################################

function process_scripts($items,$reserved="")
{
  global $handles;
  global $exec_list;
  global $alias_locks;
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
    term_echo("EXEC: ".$command);
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
        if (has_account_list($alias)==True)
        {
          if ((in_array($account,$exec_list[$alias]["accounts"])==False) and (in_array($account,$admin_accounts)==False) and ($account<>NICK))
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not in exec line account list");
            $admin_data="";
            $admin_is_sock="";
          }
          else
          {
            $tmp_data=$admin_data;
            $tmp_is_sock=$admin_is_sock;
            $admin_data="";
            $admin_is_sock="";
            handle_data($tmp_data,$tmp_is_sock,True);
          }
        }
        else
        {
          if (in_array($account,$admin_accounts)==False)
          {
            term_echo("authentication failure: \"$account\" attempted to run \"$alias\" but is not in admin account list");
            $admin_data="";
            $admin_is_sock="";
          }
          else
          {
            $tmp_data=$admin_data;
            $tmp_is_sock=$admin_is_sock;
            $admin_data="";
            $admin_is_sock="";
            handle_data($tmp_data,$tmp_is_sock,True);
          }
        }
      }
      else
      {
        $admin_data="";
        $admin_is_sock="";
      }
    }
    else
    {
      $admin_data="";
      $admin_is_sock="";
    }
  }
}

#####################################################################################################

?>
