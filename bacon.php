<?php

# gpl2
# by crutchy
# 30-march-2014

# thanks to mrbluze for his guidance

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
$prefix="";
$suffix="";
$color=-1;
$verb_to=array("bonking","trolling","farting","brooming","whacking","slurping","factoring","frogging","spanking");
$noun_from=array("horse","dog","computer");
$noun_to=array("Shrodinger's cat","brown puddle","sticky mess");
main();

function main()
{
  global $fp;
  global $joined;
  global $last;
  global $subject;
  global $prefix;
  global $suffix;
  global $color;
  global $verb_to;
  global $noun_from;
  global $noun_to;
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
            $words=explode(" ",$last);
            process($words,$verb_to,"","","ing");
            process($words,$noun_to,$noun_from);
            if ($color==-1)
            {
              privmsg(implode(" ",$words));
            }
            else
            {
              privmsg($prefix.$color.implode(" ",$words).$suffix);
            }
          }
          else
          {
            privmsg("\"crunch\" by crutchy: https://github.com/crutchy-/test/blob/master/bacon.php");
          }
        }
        elseif (strtoupper(substr($msg,0,strlen("COLOR ")))=="COLOR ")
        {
          $new=substr($msg,strlen("COLOR "));
          if (($new>=0) and ($new<=15))
          {
            $color=$new;
          }
          else
          {
            $color=-1;
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
  echo "$msg\r\n";
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

function process(&$words,$to_lib,$from_lib="",$prefix="",$suffix="")
{
  for ($i=0;$i<count($words);$i++)
  {
    if (mt_rand(0,1)==1)
    {
      continue;
    }
    if ($suffix<>"")
    {
      if (substr(strtolower($words[$i]),strlen($words[$i])-strlen($suffix))==$suffix)
      {
        replace($words,$to_lib,$i);
      }
    }
    elseif ($prefix<>"")
    {
      if (substr(strtolower($words[$i]),0,strlen($prefix))==$prefix)
      {
        replace($words,$to_lib,$i);
      }
    }
    elseif (is_array($from_lib)==True)
    {
      if (in_array(strtolower($words[$i]),$from_lib)==True)
      {
        replace($words,$to_lib,$i);
      }
    }
    else
    {
      replace($words,$to_lib,$i);
    }
  }
}

function replace(&$words,$to_lib,$i)
{
  $words[$i]=$to_lib[mt_rand(0,count($to_lib)-1)];
}

?>
