<?php

#####################################################################################################

/*
exec:~sed-internal|10|0|0|1||INTERNAL|||php scripts/sed.php %%trailing%% %%nick%% %%dest%% %%alias%% %%cmd%%
exec:~sed|10|0|0|0|||||php scripts/sed.php %%trailing%% %%nick%% %%dest%% %%alias%% %%cmd%%
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
    if (shell_sed($msg,$nick,$dest)==True)
    {
      return;
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

function shell_sed($trailing,$nick,$dest)
{
  # [nick[:|,|>|.] ]sed_cmd
  global $delims;
  $trailing=trim($trailing);
  if ($trailing=="")
  {
    return False;
  }
  $parts=explode("/",$trailing);
  if (count($parts)<3)
  {
    return False;
  }
  $last=strtolower($parts[count($parts)-1]);
  if (strpos($last,"e")!==False)
  {
    return False;
  }
  $parts=explode(" ",$trailing);
  $sed_nick="";
  if (count($parts)>1)
  {
    $break=False;
    for ($i=0;$i<count($delims);$i++)
    {
      if (strpos($parts[0],$delims[$i])==True)
      {
        $break=True;
        break;
      }
    }
    if ($break==False)
    {
      $sed_nick=$parts[0];
      if (strpos(":,>.",substr($sed_nick,strlen($sed_nick)-1))!==False)
      {
        $sed_nick=substr($sed_nick,0,strlen($sed_nick)-1);
      }
      array_shift($parts);
    }
  }
  if ($sed_nick=="")
  {
    $sed_nick=$nick;
  }
  $sed_cmd=implode(" ",$parts);
  if (strlen($sed_cmd)<5)
  {
    return False;
  }
  if (strtolower($sed_cmd[0])<>"s")
  {
    return False;
  }
  if (in_array($sed_cmd[1],$delims)==False)
  {
    return False;
  }
  $index="last_".strtolower($sed_nick)."_".strtolower($dest);
  $last=get_bucket($index);
  if ($last=="")
  {
    return False;
  }
  $action_delim=chr(1)."ACTION ";
  if (strtoupper(substr($last,0,strlen($action_delim)))==$action_delim)
  {
    $last=trim(substr($last,strlen($action_delim)),chr(1));
  }
  $command="echo ".escapeshellarg($last)." | sed -e ".escapeshellarg($sed_cmd);
  var_dump($command);
  $cwd=Null;
  $env=Null;
  $descriptorspec=array(0=>array("pipe","r"),1=>array("pipe","w"),2=>array("pipe","w"));
  $process=proc_open($command,$descriptorspec,$pipes,$cwd,$env);
  $result=trim(stream_get_contents($pipes[1]));
  $result_lines=explode("\n",$result);
  var_dump($result_lines);
  if (count($result_lines)>1)
  {
    return False;
  }
  fclose($pipes[1]);
  $stderr=trim(stream_get_contents($pipes[2]));
  fclose($pipes[2]);
  proc_close($process);
  if ($stderr<>"")
  {
    term_echo($stderr);
    return True;
  }
  if (($result==$last) or ($result==$sed_cmd))
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
  }
  return True;
}

#####################################################################################################

?>
