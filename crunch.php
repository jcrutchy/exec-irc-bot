<?php

# gpl2
# by crutchy
# 27-march-2014

define("NICK","crunch");
define("CHAN","##");
define("TRIGGER","~");

set_time_limit(0);
ini_set("display_errors","on");
$joined=0;
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK ".NICK."\r\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\r\n");
main();

function main()
{
  global $fp;
  global $joined;
  $logfile=date("Ymd").CHAN.".log";
  $data=fgets($fp);
  if ($data!==False)
  {
    $parts=explode(" ",$data);
    if (count($parts)>1)
    {
      if ($parts[0]=="PING")
      {
        fputs($fp,"PONG ".$parts[1]."\r\n");
      }
      else
      {
        echo $data;
        if (file_put_contents($logfile,$data,FILE_APPEND)===False)
        {
          echo "ERROR APPENDING LOG\r\n";
          return;
        }
      }
    }
    $nick="";
    $msg="";
    if (msg_nick($data,$nick,$msg)==True)
    {
      if (strtoupper(substr($msg,0,strlen(TRIGGER)))==TRIGGER)
      {
        $msg=substr($msg,strlen(TRIGGER));
        if ((strtoupper($msg)=="QUIT") or (strtoupper($msg)=="Q"))
        {
          fputs($fp,":".NICK." QUIT\r\n");
          fclose($fp);
          echo "QUITTING SCRIPT\r\n";
          return;
        }
        elseif (strtoupper(substr($msg,0,strlen("FIND ")))=="FIND ")
        {
          $msg=substr($msg,strlen("FIND "));
          $i=strpos($msg," ");
          if ($i!==False)
          {
            privmsg(find($logfile,substr($msg,0,$i),substr($msg,$i+1)));
          }
          else
          {
            privmsg(find($logfile,$msg));
          }
        }
        elseif ($msg=="")
        {
          privmsg("\"crunch\" by crutchy: https://github.com/crutchy-/test/blob/master/crunch.php");
        }
      }
    }
    if (($joined==0) and (strpos($data,"End of /MOTD command")!==False))
    {
      $joined=1;
      fputs($fp,"JOIN ".CHAN."\r\n");
    }
  }
  main();
}

function privmsg($msg)
{
  global $fp;
  fputs($fp,":".NICK." PRIVMSG ".CHAN." :$msg\r\n");
}

function msg_nick($data,&$nick,&$msg)
{
  $parts=explode(" ",$data);
  if (count($parts)>1)
  {
    if ((trim($parts[1])=="PRIVMSG") and (count($parts)>3))
    {
      $pieces1=explode("!",$parts[0]);
      $pieces2=explode("PRIVMSG ".CHAN." :",$data);
      if ((count($pieces1)>1) and (count($pieces2)==2))
      {
        $nick=substr($pieces1[0],1);
        $msg=trim($pieces2[1]);
        return True;
      }
    }
  }
  $nick="";
  $msg="";
  return False;
}

function find($logfile,$nick,$query="")
{
  if (file_exists($logfile)===True)
  {
    $data=file_get_contents($logfile);
    if ($data!==False)
    {
      $lines=explode("\n",$data);
      $n=count($lines);
      for ($i=$n-1;$i>=0;$i--)
      {
        $test_nick="";
        $test_msg="";
        if (msg_nick($lines[$i],$test_nick,$test_msg)==True)
        {
          if ((strtoupper($test_nick)==strtoupper($nick)) and (strtoupper($test_msg)<>(TRIGGER."FIND ".strtoupper($nick)." ".strtoupper($query))))
          {
            if (($query=="") or (($query<>"") and (strpos(strtoupper($test_msg),strtoupper($query))!==False)))
            {
              return "$nick: $test_msg";
            }
          }
        }
      }
    }
    $date_arr=date_parse_from_format("Ymd",substr($logfile,0,8));
    $d=mktime(0,0,0,$date_arr["month"],$date_arr["day"],$date_arr["year"]);
    return find(date("Ymd",$d-24*60*60).CHAN.".log",$nick,$query);
  }
  else
  {
    return "Quote containing \"$query\" by $nick not found in recorded history.";
  }
}

?>
