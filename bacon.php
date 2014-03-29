<?php

# gpl2
# by crutchy
# 29-march-2014

define("NICK","crunch");
define("CHAN","##");
define("TRIGGER","~");

set_time_limit(0);
ini_set("display_errors","on");
$joined=0;
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK ".NICK."\r\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\r\n");
$last="";
$subject="a";
main();

function main()
{
  global $fp;
  global $joined;
  global $last;
  global $subject;
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
      }
    }
    $nick="";
    $msg="";
    if (msg_nick($data,$nick,$msg)==True)
    {
      if (strtoupper(substr($msg,0,strlen(TRIGGER)))==TRIGGER)
      {
        $msg=substr($msg,strlen(TRIGGER));
        if (strtoupper($msg)=="Q")
        {
          fputs($fp,":".NICK." QUIT\r\n");
          fclose($fp);
          echo "QUITTING SCRIPT\r\n";
          return;
        }
        elseif ($msg=="")
        {
          if ($last<>"")
          {
            privmsg(str_replace($subject,"bacon",$last));
          }
          else
          {
            privmsg("\"crunch\" by crutchy: https://github.com/crutchy-/test/blob/master/bacon.php");
          }
        }
        elseif (strtoupper(substr($msg,0,strlen("SUBST ")))=="SUBST ")
        {
          $new=substr($msg,strlen("SUBST "));
          if ($new<>"")
          {
            $subject=$new;
          }
        }
      }
    }
    if ((strpos($msg,TRIGGER)===False) and ($nick<>NICK))
    {
      $last=$msg;
    }
    else
    {
      $last="";
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

?>
