<?php

# gpl2
# by crutchy
# 30-march-2014

# thanks to mrbluze for his guidance

# todo: add collective noun substitution
# todo: add ability to append arrays from within irc
# todo: if nothing is substituted, replace random letters within string (not a single letter) with something like 'bacon' and allow setting of 'bacon' from within irc
# todo: no duplicate substitutions

define("NICK","crunch");
define("CHAN","#test");
define("TRIGGER","~");
define("CMD_COLOR","COLOR");
define("CMD_SUBST","SUBST");
define("ABOUT","\"crunch\" by crutchy: https://github.com/crutchy-/test/blob/master/bacon.php");
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
$noun_from=array("horse","dog","computer","array","table","tabletop","timezone");
$noun_to=array("washing machine","Shrodinger's cat","brown puddle","sticky mess","stool");
$subject="a";
$enabled=1;
while ($data=fgets($fp))
{
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
        $cmd_msg="";
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
            privmsg(ABOUT);
          }
        }
        elseif (iscmd($msg,$cmd_msg,CMD_COLOR)==True)
        {
          if (($cmd_msg>=0) and ($cmd_msg<=15))
          {
            $color=$cmd_msg;
          }
          else
          {
            $color=-1;
          }
        }
        elseif (iscmd($msg,$cmd_msg,CMD_SUBST)==True)
        {
          if ($cmd_msg<>"")
          {
            $subject=$cmd_msg;
          }
        }
        else
        {
          privmsg(ABOUT);
        }
      }
      else
      {
        if ($msg<>"")
        {
          $words=explode(" ",$msg);
          process($words,$verb_to,"","","ing");
          process($words,$noun_to,$noun_from);
          $new_msg=implode(" ",$words);
          if ($new_msg<>$msg)
          {
            privmsg($new_msg);
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
}

function privmsg($msg)
{
  global $fp;
  global $prefix;
  global $suffix;
  global $color;
  if ($color==-1)
  {
    $out=$msg;
  }
  else
  {
    $out=$prefix.$color.$msg.$suffix;
  }
  fputs($fp,":".NICK." PRIVMSG ".CHAN." :$out\r\n");
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

function iscmd($msg,&$cmd_msg,$cmd)
{
  if (strtoupper(substr($msg,0,strlen($cmd)+1))==($cmd." "))
  {
    $cmd_msg=substr($msg,strlen($cmd)+1);
    return True;
  }
  $cmd_msg="";
  return False;
}

function process(&$words,&$to_lib,$from_lib="",$prefix="",$suffix="")
{
  for ($i=0;$i<count($words);$i++)
  {
    if (mt_rand(0,10)==1)
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
  reset_lib($to_lib);
}

function replace(&$words,&$to_lib,$i)
{
  do
  {
    $j=mt_rand(0,count($to_lib)-1);
    check_all_used($to_lib);
  }
  while ($to_lib[$j][0]=="!");
  $words[$i]=$to_lib[$j];
  $to_lib[$j]="!".$to_lib[$j];
}

function reset_lib(&$lib)
{
  for ($i=0;$i<count($lib);$i++)
  {
    if ($lib[$i][0]=="!")
    {
      $lib[$i]=substr($lib[$i],1);
    }
  }
}

function check_all_used(&$lib)
{
  for ($i=0;$i<count($lib);$i++)
  {
    if ($lib[$i][0]<>"!")
    {
      return;
    }
  }
  reset_lib($lib);
}

?>
