<?php

# gpl2
# by crutchy
# 3-april-2014

define("PASSWORD",""); # obfuscate password for git push
define("NICK","bacon"); # bacon/coffee
define("CHAN","##");
define("OPERATOR_UP","+");
define("OPERATOR_DN","-");
define("KARMA_FILE","karma_db");
define("CMD_QUIT","~Q");
define("CMD_SAVE","~SAVE");
define("CMD_EXEC","~");
define("CMD_COLOR","~COLOR");
define("CMD_KARMA","~KARMA");
define("SAVE_DELAY",10);
define("COLOR_PREFIX","");
define("COLOR_SUFFIX","");
define("BAN_TIME",20); # seconds
define("DELTA_TOLERANCE",0.1); # seconds
define("MAX_NICK_LIST",10);
set_time_limit(0);
ini_set("display_errors","on");
ini_set("allow_url_fopen",1);
$rainbow_words=array("bacon","coffee","soylent");
$illegals=array(":",OPERATOR_UP,OPERATOR_DN,CMD_EXEC,"karma");
$rainbow_colors=array(4,7,8,3,12,6,13);
$fp=fsockopen("irc.sylnt.us",6667);
fputs($fp,"NICK ".NICK."\r\n");
fputs($fp,"USER ".NICK." * ".NICK." :".NICK."\r\n");
$karma=array();
$save_tick=0;
$color_fg=-1;
$color_bg=-1;
$time_deltas=array();
if (file_exists(KARMA_FILE)==True)
{
  $data=file_get_contents(KARMA_FILE);
  if ($data===False)
  {
    term_echo("Error reading file \"".KARMA_FILE."\".");
  }
  else
  {
    $lines=explode("\n",$data);
    $n=count($lines);
    for ($i=0;$i<$n;$i++)
    {
      $line=trim($lines[$i]);
      if ($line=="")
      {
        continue;
      }
      # word nick value
      $parts=explode(" ",$line);
      if (count($parts)<>3)
      {
        continue;
      }
      $word=$parts[0];
      $nick=$parts[1];
      $val=$parts[2];
      $karma[$word][$nick]=$val;
    }
  }
}
else
{
  term_echo("File \"".KARMA_FILE."\" not found.");
}
while (feof($fp)===False)
{
  $data=fgets($fp);
  if ($data===False)
  {
    continue;
  }
  $logfile=date("Ymd").CHAN.".log";
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
        term_echo("ERROR APPENDING LOG");
        return;
      }
    }
  }
  $nick="";
  $msg="";
  if (msg_nick($data,$nick,$msg)==True)
  {
    $params=explode(" ",$msg);
    switch (strtoupper($params[0]))
    {
      case CMD_KARMA: # ~karma bacon
        if (count($params)==2)
        {
          output_karma($params[1]);
        }
        break;
      case CMD_COLOR:
        if (count($params)==3)
        {
          $color_fg=$params[1];
          $color_bg=$params[2];
        }
        else
        {
          $color_fg=-1;
          $color_bg=-1;
        }
        break;
      case CMD_SAVE:
        save_db($karma);
        break;
      #case CMD_EXEC:
        #$exec=substr($msg,strlen(CMD_EXEC));
        #fputs($fp,$exec."\r\n");
        #break;
      case CMD_QUIT:
        save_db($karma);
        fputs($fp,":".NICK." QUIT\r\n");
        fclose($fp);
        term_echo("QUITTING SCRIPT");
        return;
      default:
        $karma_up=parse_karma($msg,OPERATOR_UP);
        $karma_dn=parse_karma($msg,OPERATOR_DN);
        if (($karma_up!==False) and (check_nick($nick)==True))
        {
          if (isset($karma[$karma_up][$nick])==True)
          {
            $karma[$karma_up][$nick]=$karma[$karma_up][$nick]+1;
          }
          else
          {
            $karma[$karma_up][$nick]=1;
          }
          karma_privmsg($karma,rawurldecode($karma_up),$nick,OPERATOR_UP);
        }
        if (($karma_dn!==False) and (check_nick($nick)==True))
        {
          if (isset($karma[$karma_dn][$nick])==True)
          {
            $karma[$karma_dn][$nick]=$karma[$karma_dn][$nick]-1;
          }
          else
          {
            $karma[$karma_dn][$nick]=-1;
          }
          karma_privmsg($karma,rawurldecode($karma_dn),$nick,OPERATOR_DN);
        }
    }
  }
  if (strpos($data,"End of /MOTD command")!==False)
  {
    fputs($fp,"JOIN ".CHAN."\r\n");
  }
  if (strpos($data,"You have 60 seconds to identify to your nickname before it is changed.")!==False)
  {
    fputs($fp,"NICKSERV identify ".PASSWORD."\r\n");
  }
  if ($save_tick>=SAVE_DELAY)
  {
    save_db($karma);
    $save_tick=0;
  }
  $save_tick++;
}

function output_karma($decoded_word)
{
  global $karma;
  global $rainbow_words;
  if (isset($karma[rawurlencode($decoded_word)])==False)
  {
    privmsg("\"$decoded_word\" has no karma!");
    return;
  }
  $rainbow=in_array(strtolower($decoded_word),$rainbow_words);
  $total=total_karma($decoded_word);
  $msg=$decoded_word;
  if ($rainbow==True)
  {
    $msg=rainbowize($decoded_word);
  }
  $summary="";
  $n=0;
  $sorted=$karma[rawurlencode($decoded_word)];
  arsort($sorted);
  foreach ($sorted as $nick => $value)
  {
    if ($n>=MAX_NICK_LIST)
    {
      break;
    }
    if ($summary<>"")
    {
      $summary=$summary.", ";
    }
    $summary=$summary."$nick: $value";
    $n++;
  }
  privmsg("karma of \"$msg\" is ".total_karma($decoded_word)." [$summary]");
}

function total_karma($decoded_word)
{
  global $karma;
  $result=0;
  if (isset($karma[rawurlencode($decoded_word)])==False)
  {
    return $result;
  }
  foreach ($karma[rawurlencode($decoded_word)] as $nick => $value)
  {
    $result=$result+$value;
  }
  return $result;
}

function check_nick($nick)
{
  global $time_deltas;
  if (isset($time_deltas[$nick]["time"])==False)
  {
    $time_deltas[$nick]["time"]=microtime(True);
    $time_deltas[$nick]["last_delta"]=0;
    return True;
  }
  $last_delta=$time_deltas[$nick]["last_delta"];
  $this_delta=microtime(True)-$time_deltas[$nick]["time"];
  $time_deltas[$nick]["last_delta"]=$this_delta;
  if (abs($last_delta-$this_delta)<DELTA_TOLERANCE)
  {
    $time_deltas[$nick]["last"]["ban_start"]=microtime(True);
    privmsg("NICK \"$nick\" BANNED FROM CHANGING KARMA FOR ".BAN_TIME." SECONDS");
  }
  else
  {
    if (isset($time_deltas[$nick]["last"]["ban_start"])==True)
    {
      if ((microtime(True)-$time_deltas[$nick]["last"]["ban_start"])>=BAN_TIME)
      {
        unset($time_deltas[$nick]["last"]["ban_start"]);
        privmsg("BAN CLEARED FOR NICK \"$nick\"");
      }
    }
  }
  if (isset($time_deltas[$nick]["last"]["ban_start"])==True)
  {
    return False;
  }
  return True;
}

function colored($msg,$fg,$bg)
{
  if ($bg==-1)
  {
    if ($fg==-1)
    {
      $out=$msg;
    }
    else
    {
      $out=COLOR_PREFIX.$fg.$msg.COLOR_SUFFIX;
    }
  }
  else
  {
    if ($fg==-1)
    {
      $out=COLOR_PREFIX."0,".$bg.$msg.COLOR_SUFFIX;
    }
    else
    {
      $out=COLOR_PREFIX.$fg.",".$bg.$msg.COLOR_SUFFIX;
    }
  }
  return $out;
}

function privmsg($msg)
{
  global $fp;
  fputs($fp,":".NICK." PRIVMSG ".CHAN." :$msg\r\n");
  term_echo($msg);
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

function parse_karma($msg,$operator)
{
  global $illegals;
  $parts=explode($operator,$msg);
  $karma=$parts[0];
  if ($karma=="")
  {
    return False;
  }
  if (count($parts)<2)
  {
    return False;
  }
  if ($parts[1]<>"")
  {
    if ($parts[1][0]<>" ")
    {
      return False; # word+*
    }
  }
  if (count($parts)>2)
  {
    if ($parts[1]=="")
    {
      return False; # word++
    }
  }
  if ((strpos($karma," ")===False) and (in_array(strtolower($karma),$illegals)==False))
  {
    return rawurlencode(trim($karma));
  }
  return False;
}

function rainbowize($msg)
{
  global $rainbow_colors;
  $offset=mt_rand(1,count($rainbow_colors));
  $out="";
  for ($i=0;$i<strlen($msg);$i++)
  {
    $out=$out.colored($msg[$i],0,$rainbow_colors[($i+$offset)%count($rainbow_colors)]);
  }
  return $out;
}

function karma_privmsg(&$karma,$decoded_word,$nick,$operator)
{
  global $color_fg;
  global $color_bg;
  global $rainbow_words;
  $rainbow=in_array(strtolower($decoded_word),$rainbow_words);
  $total=total_karma($decoded_word);
  if ($rainbow==True)
  {
    privmsg(colored("karma",0,-1)." - ".rainbowize($decoded_word).": $total ($operator)");
  }
  else
  {
    if (($color_fg==-1) and ($color_bg==-1))
    {
      privmsg(colored("karma",0,-1)." - $decoded_word: $total ($operator)");
    }
    else
    {
      privmsg(colored("karma - $decoded_word: $total ($operator)",$color_fg,$color_bg));
    }
  }
}

function save_db(&$karma)
{
  # word nick value
  $data="";
  foreach ($karma as $word => $nicks)
  {
    foreach ($nicks as $nick => $value)
    {
      $data=$data."$word $nick $value\n";
    }
  }
  if (strlen($data)==0)
  {
    term_echo("No karma data. File \"".KARMA_FILE."\" not saved.");
    return;
  }
  if (file_put_contents(KARMA_FILE,$data)===False)
  {
    term_echo("Error saving file \"".KARMA_FILE."\".");
  }
  else
  {
    term_echo("Successfully saved file \"".KARMA_FILE."\".");
  }
}

function term_echo($msg)
{
  echo "\033[1;31m$msg\033[0m\r\n";
}

?>
