<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~sed-internal|10|0|0|1||INTERNAL||0|php scripts/sed.php %%trailing%% %%nick%% %%dest%% %%alias%% %%cmd%%
exec:~sed|10|0|0|0||||0|php scripts/sed.php %%trailing%% %%nick%% %%dest%% %%alias%% %%cmd%%
#exec:~sedbot-ii|0|0|0|1|crutchy|||0|ii -s irc.sylnt.us -p 6667 -n SedBot2 -f SedBot2
#exec:~sedbot-cmd|0|0|0|1|crutchy|||0|echo %%trailing%% > ~/irc/irc.sylnt.us/in
#exec:~sedbot-awk1|0|0|0|1|crutchy|||0|tail -Fn1 /home/jared/irc/irc.sylnt.us/#soylent/out | /home/jared/sedbot.awk outfile=/home/jared/irc/irc.sylnt.us/#soylent/in
#exec:~sedbot-awk2|0|0|0|1|crutchy|||0|tail -Fn1 /home/jared/irc/irc.sylnt.us/##/out | /home/jared/sedbot.awk outfile=/home/jared/irc/irc.sylnt.us/##/in
#exec:~sedbot-awk3|0|0|0|1|crutchy|||0|tail -Fn1 /home/jared/irc/irc.sylnt.us/#test/out | /home/jared/sedbot.awk outfile=/home/jared/irc/irc.sylnt.us/#test/in
#exec:~sedbot-tail0|0|0|0|1|crutchy|||0|tail -Fn1 ~/irc/irc.sylnt.us/out
#exec:~sedbot-tail1|0|0|0|1|crutchy|||0|tail -Fn1 ~/irc/irc.sylnt.us/#soylent/out
#exec:~sedbot-tail2|0|0|0|1|crutchy|||0|tail -Fn1 ~/irc/irc.sylnt.us/##/out
#exec:~sedbot-tail3|0|0|0|1|crutchy|||0|tail -Fn1 ~/irc/irc.sylnt.us/#test/out
#exec:~sedbot-talk1|0|0|0|0|crutchy|||0|echo %%trailing%% > ~/irc/irc.sylnt.us/#soylent/in
#exec:~sedbot-talk2|0|0|0|0|crutchy|||0|echo %%trailing%% > ~/irc/irc.sylnt.us/##/in
#exec:~sedbot-talk3|0|0|0|0|crutchy|||0|echo %%trailing%% > ~/irc/irc.sylnt.us/#test/in
*/

#####################################################################################################

/*
<crutchy> c++
<Bender> karma - c: 0
<crutchy> s/c+/p/
<exec> <crutchy> p++
<crutchy> that's weird

jared@debian:~$ echo "so i think i'm going C++ for awhile" | sed -e "s/C++/pascal/"
so i think i'm going pascal for awhile

jared@debian:~$ echo "so i think i'm going C++ for awhile" | sed -e "s/C+/pascal/"
so i think i'm going pascal+ for awhile

<crutchy> C++
<Bender> karma - c: 26
<crutchy> s/C\+\+/pascal/
<exec> <crutchy> pascal
<crutchy> works if i escape them manually at least
*/

#####################################################################################################

require_once("lib.php");
require_once("switches.php");

$trailing=rtrim($argv[1]);
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];

$delims=array("/","#"); # cannot be alphanumeric or \

$msg="";
$flag=handle_switch($alias,$dest,$nick,$trailing,"<<EXEC_SED_CHANNELS>>","~sed","~sed-internal",$msg);
switch ($flag)
{
  case 0:
    return;
  case 1:
    privmsg("sed enabled for ".chr(3)."10$dest");
    return;
  case 2:
    privmsg("sed already enabled for ".chr(3)."10$dest");
    return;
  case 3:
    privmsg("sed disabled for ".chr(3)."10$dest");
    return;
  case 4:
    privmsg("sed already disabled for ".chr(3)."10$dest");
    return;
  case 5:
    # bot was kicked from channel
    return;
  case 6:
    # bot parted channel
    return;
  case 7:
    for ($i=0;$i<count($delims);$i++)
    {
      #if (sed($msg,$nick,$dest,$delims[$i])==True)
      if (shell_sed($msg,$nick,$dest,$delims[$i])==True)
      {
        return;
      }
    }
    break;
  case 8:
    # privmsg
    break;
  case 9:
    return;
  case 10:
    return;
}
set_bucket("last_".strtolower($nick)."_".strtolower($dest),$msg);

#####################################################################################################

function shell_sed($trailing,$nick,$dest,$delim="/")
{
  return False;
  #$nick1
  /*$replnick=$nick;
  $command=$trailing;
  $parts=explode(" ",$trailing);
  $sed_nick=strtolower(trim($parts[0]));
  array_shift($parts);
  $sed_trailing=implode(" ",$parts);
  $sed_nick_last=substr($sed_nick,strlen($sed_nick)-1);
  $suffix_chars=":,";
  if (strpos($suffix_chars,$sed_nick_last)!==False)
  {
    $sed_nick=trim(substr($sed_nick,0,strlen($sed_nick)-1));
  }
  $sed_trailing_first=strtolower(substr($sed_trailing,0,1));
  if (($sed_trailing_first=="s") and ($sed_nick<>""))
  {
    $replnick=$sed_nick;
    $command=$sed_trailing;
  }

  $index="last_".strtolower($replnick)."_".strtolower($dest);
  $subject=get_bucket($index);
  if ($subject=="")
  {
    privmsg("last message by \"$nick\" not found");
    return False;
  }
  $subject=escapeshellarg($subject);
  $command=escapeshellarg($trailing);
  $cmd="echo $subject | sed -e $command 2>&1";
  pm("#debug",$cmd);
  $result=shell_exec($cmd);
  $result=trim($result);
  pm("#debug",$result);
  $errstr="sed: -e expression";
  if (substr($result,0,strlen($errstr))==$errstr)
  {
    return False;
  }
  if ($result=="")
  {
    return False;
  }
  privmsg("<$nick> $result");
  return True;*/
}

#####################################################################################################

function sed($trailing,$nick,$dest,$delim="/")
{
  # [nick[:|,|>|.] ]s/pattern/replace[/[g]]
  $replace_all=False;
  if (substr(strtolower($trailing),strlen($trailing)-2)==($delim."g"))
  {
    $trailing=substr($trailing,0,strlen($trailing)-2);
    $replace_all=True;
  }
  if (substr($trailing,strlen($trailing)-1)==$delim)
  {
    $trailing=substr($trailing,0,strlen($trailing)-1);
  }
  # [nick[:|,|>|.] ]s/pattern/replace
  $trailing=str_replace("\\\\","\nB\n",$trailing);
  $trailing=str_replace("\/","\nF\n",$trailing);
  $parts=explode($delim,$trailing);
  if (count($parts)==3)
  {
    $start=ltrim($parts[0]);
    if (trim($start)=="")
    {
      return False;
    }
    $start_arr=explode(" ",$start);
    $sed_nick="";
    if (count($start_arr)==1)
    {
      if (strtolower($start_arr[0])<>"s")
      {
        return False;
      }
    }
    elseif (count($start_arr)==2)
    {
      if (strtolower($start_arr[1])=="s")
      {
        $sed_nick=$start_arr[0];
        if (strpos(":,>.",substr($sed_nick,strlen($sed_nick)-1))!==False)
        {
          $sed_nick=substr($sed_nick,0,strlen($sed_nick)-1);
        }
      }
      else
      {
        return False;
      }
    }
    else
    {
      return False;
    }
    $pattern=$parts[1];
    if ($pattern=="")
    {
      sed_help();
      return False;
    }
    $replace=$parts[2];
    if ($sed_nick=="")
    {
      $sed_nick=$nick;
    }
    $index="last_".strtolower($sed_nick)."_".strtolower($dest);
    $last=get_bucket($index);
    if ($last=="")
    {
      privmsg("last message by \"$sed_nick\" not found");
      return False;
    }
    $action_delim=chr(1)."ACTION ";
    if (strtoupper(substr($last,0,strlen($action_delim)))==$action_delim)
    {
      $last=trim(substr($last,strlen($action_delim)),chr(1));
    }
    $pattern=str_replace("\nB\n","\\",$pattern);
    $replace=str_replace("\nB\n","\\",$replace);
    $pattern=str_replace("\nF\n","/",$pattern);
    $replace=str_replace("\nF\n","/",$replace);
    $pattern=str_replace("\\\\","\nDB\n",$pattern);
    $pattern=str_replace("/","\/",$pattern);
    $pattern=str_replace("\nDB\n","\\\\",$pattern);
    term_echo("*** SED: NICK: $nick");
    term_echo("*** SED: SUBJECT: $last");
    term_echo("*** SED: PATTERN: $pattern");
    term_echo("*** SED: REPLACE: $replace");
    if ($replace_all==True)
    {
      $result=preg_replace($delim.$pattern.$delim,$replace,$last);
    }
    else
    {
      $result=preg_replace($delim.$pattern.$delim,$replace,$last,1);
    }
    if ($result==$last)
    {
      $result="";
    }
    if ($result<>"")
    {
      if ($nick==$sed_nick)
      {
        privmsg("<$sed_nick> $result");
      }
      else
      {
        privmsg("<$nick> <$sed_nick> $result");
      }
      return True;
    }
    else
    {
      sed_help();
    }
  }
  return False;
}

#####################################################################################################

function sed_help()
{
  #privmsg("syntax: ".chr(3)."8[nick[:|,|>|.] ]s/pattern/replace[/[g]]");
}

#####################################################################################################

?>
