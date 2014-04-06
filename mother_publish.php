<?php

# gpl2
# by crutchy
# 6-april-2014

define("PASSWORD",""); # obfuscate password for git push
define("NICK","mother"); # mother/bacon/coffee
define("LOG_FILE","log");
define("CMD_QUIT","~Q");
#define("CHAN_LIST","#test,##");
define("CHAN_LIST","#test");
set_time_limit(0);
ini_set("display_errors","on");
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK ".NICK."\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\n");
while (feof($fp)===False)
{
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
        fputs($fp,": QUIT\n");
        fclose($fp);
        term_echo("QUITTING SCRIPT");
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
    fputs($fp,"NICKSERV identify ".PASSWORD."\r\n");
  }
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
  if (file_put_contents(LOG_FILE,$data,FILE_APPEND)===False)
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
  if ($result["nick"]=="")
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
  $data=serialize($items);
  if ($data===False)
  {
    term_echo("Error serializing data.");
    return;
  }
  $response=exec("php mother_repeat.php irc ".escapeshellarg($data));
  privmsg($items["chan"],$response);
}

?>
