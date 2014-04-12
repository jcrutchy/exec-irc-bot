<?php

# gpl2
# by crutchy
# 9-april-2014

$pwd=file_get_contents("test");
define("NICK","bacon");
define("PASSWORD",$pwd);
unset($pwd);
define("LOG_FILE","log");
define("CMD_QUIT","~Q");
#define("CHAN_LIST","#test,##");
define("CHAN_LIST","#~");
define("CHAN_TERM","#~");
set_time_limit(0);
ini_set("display_errors","on");
stream_set_blocking(STDIN,False);
$fp=fsockopen("irc.sylnt.us",6667);
stream_set_blocking($fp,False);
fputs($fp,"NICK ".NICK."\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\n");
$handles=array();
while (feof($fp)===False)
{
  $in=fgets(STDIN);
  if ($in!==False)
  {
    $tin=trim($in);
    if (strtoupper($tin)==CMD_QUIT)
    {
      doquit($fp);
      return;
    }
    else
    {
      /*$cmd=substr($tin,0,strpos($tin," "));
      $msg=substr($tin,strpos($tin," "));
      if (($cmd<>"") and ($msg<>""))
      {
        $n=count($handles);
        for ($i=0;$i<$n;$i++)
        {
          if ($handles[$i]["command"]==$cmd)
          {
            fwrite($handles[$i]["pipe_stdin"],$msg);
          }
        }
        privmsg(CHAN_TERM,$tin);
      }*/
    }
  }
  $n=count($handles);
  for ($i=0;$i<$n;$i++)
  {
    while (feof($handles[$i]["pipe_stdout"])==False)
    {
      $buf=fgets($handles[$i]["pipe_stdout"]);
      if ($buf!==False)
      {
        privmsg(CHAN_TERM,rtrim($buf));
      }
    }
    $proc_info=proc_get_status($handles[$i]["process"]);
    if ($proc_info["running"]==False)
    {
      $return_value=proc_close($handles[$i]["process"]);
    }
    unset($handles[$i]);
  }
  $handles=array_values($handles);
  $data=fgets($fp);
  if ($data===False)
  {
    continue;
  }
  if (pingpong($fp,$data)==True)
  {
    continue;
  }
  echo $data;
  $items=parse_data($data);
  if ($items!==False)
  {
    append_log($items);
    $params=explode(" ",$items["msg"]);
    switch (strtoupper($params[0]))
    {
      case CMD_QUIT:
        doquit($fp);
        return;
      default:
        process_scripts($items);
    }
  }
  if (strpos($data,"End of /MOTD command")!==False)
  {
    fputs($fp,"JOIN ".CHAN_LIST."\n");
  }
  if (strpos($data,"You have 60 seconds to identify to your nickname before it is changed.")!==False)
  {
    fputs($fp,"NICKSERV identify ".PASSWORD."\n");
  }
}

function doquit($fp)
{
  global $handles;
  $n=count($handles);
  for ($i=0;$i<$n;$i++)
  {
    $proc_info=proc_get_status($handles[$i]["process"]);
    if ($proc_info["running"]==False)
    {
      $return_value=proc_close($handles[$i]["process"]);
    }
  }
  fputs($fp,": QUIT\n");
  fclose($fp);
  term_echo("QUITTING SCRIPT");
}

function pingpong($fp,$data)
{
  $parts=explode(" ",$data);
  if (count($parts)>1)
  {
    if ($parts[0]=="PING")
    {
      fputs($fp,"PONG ".$parts[1]."\n");
      return True;
    }
  }
  return False;
}

function append_log($items)
{
  $data=serialize($items);
  if ($data===False)
  {
    term_echo("Error serializing log items.");
    return;
  }
  if (file_put_contents(LOG_FILE,$data."\n",FILE_APPEND)===False)
  {
    term_echo("Error appending log file \"".LOG_FILE."\".");
  }
}

function term_echo($msg)
{
  echo "\033[1;31m$msg\033[0m\n";
}

function parse_data($data)
{
  # :nick!addr PRIVMSG chan :msg
  $result=array();
  if ($data=="")
  {
    return False;
  }
  if ($data[0]<>":")
  {
    return False;
  }
  $i=strpos($data," :");
  $result["msg"]=trim(substr($data,$i+2));
  if ($result["msg"]=="")
  {
    return False;
  }
  $sub=substr($data,1,$i-1);
  $i=strpos($sub,"!");
  $result["nick"]=substr($sub,0,$i);
  if (($result["nick"]=="") or ($result["nick"]==NICK))
  {
    return False;
  }
  $sub=substr($sub,$i+1);
  $i=strpos($sub," ");
  $result["addr"]=substr($sub,0,$i);
  if ($result["addr"]=="")
  {
    return False;
  }
  $sub=substr($sub,$i+1);
  $i=strpos($sub," ");
  $cmd=substr($sub,0,$i);
  if ($cmd<>"PRIVMSG")
  {
    return False;
  }
  $result["chan"]=substr($sub,$i+1);
  if ($result["chan"]=="")
  {
    return False;
  }
  $result["microtime"]=microtime(True);
  $result["time"]=date("Y-m-d H:i:s",$result["microtime"]);
  return $result;
}

function privmsg($chan,$msg)
{
  global $fp;
  if ($chan=="")
  {
    term_echo("Channel not specified.");
    return;
  }
  if ($msg=="")
  {
    term_echo("No text to send.");
    return;
  }
  fputs($fp,":".NICK." PRIVMSG $chan :$msg\r\n");
  term_echo($msg);
}

function process_scripts($items)
{
  global $handles;
  $cwd=NULL;
  $env=NULL;
  $descriptorspec=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
  $process=proc_open("exec ".$items["msg"],$descriptorspec,$pipes,$cwd,$env);
  stream_set_blocking($pipes[0],0);
  $handles[]=array("process"=>$process,"command"=>$items["msg"],"pipe_stdin"=>$pipes[0],"pipe_stdout"=>$pipes[1],"pipe_stderr"=>$pipes[2]);
}

?>
