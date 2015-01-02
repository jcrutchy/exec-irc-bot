<?php

#####################################################################################################

/*
exec:~x|120|0|0|1|@||||php scripts/scripting.php %%trailing%% %%dest%% %%nick%% %%start%% %%alias%% %%cmd%% %%data%% %%params%% %%timestamp%%
init:~x register-events
*/

#####################################################################################################

# could try a git-like branch thing, but try to keep it simple to start with

/*
whilst the code is currently limited to php, it could potentially be adapted to execute code in
other languages using their command-line interpreters. it also doesn't have to be limited to code.
it could potentially be used to collaborate on lines of non-executing text (such as for
collaborative editing of SN submissions). with some tweaks it could be possible to collaborate on
code as well, with other users being able to add/edit/delete lines but requiring the operator to
re-enable the script for changes to come into effect. 
*/

#####################################################################################################

ini_set("display_errors","on");
require_once("lib.php");

$trailing=$argv[1];
$dest=strtolower(trim($argv[2]));
$nick=strtolower(trim($argv[3]));
$start=$argv[4];
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));
$data=$argv[7];
$params=$argv[8];
$timestamp=$argv[9];

$global_execute=get_bucket("LIVE_SCRIPT_GLOBAL_EXECUTE");
$scripts=get_array_bucket("<<LIVE_SCRIPTS>>");
$code="";
$script_data=array();
$script_lines=array();
$script_name=get_bucket("LOADED_SCRIPT_".$nick."_".$dest);
if ($script_name<>"")
{
  if (isset($scripts[$script_name]["code"])==True)
  {
    $code=base64_decode($scripts[$script_name]["code"]);
    if ($code<>"")
    {
      $script_lines=explode("\n",$code);
    }
  }
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

$data_changed=False;

switch ($action)
{
  case "register-events":
    register_event_handler("PRIVMSG",":".NICK_EXEC." INTERNAL :~x event-privmsg %%nick%% %%dest%% %%trailing%%");
    break;
  case "event-privmsg":
    # trailing = crutchy # test
    if ($global_execute<>"enabled")
    {
      return;
    }
    $parts=explode(" ",$trailing);
    if (count($parts)>2)
    {
      $nick=$parts[0];
      $dest=$parts[1];
      array_shift($parts);
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
    }
    if ($dest==NICK_EXEC)
    {
      return;
    }
    foreach ($scripts as $script_name => $data)
    {
      if (isset($data["enabled"])==False)
      {
        continue;
      }
      if ($data["enabled"]<>True)
      {
        continue;
      }
      $code=trim(base64_decode($data["code"]));
      if ($code=="")
      {
        continue;
      }
      $code=implode(" ",explode("\n",$code));
      term_echo("*** LIVE SCRIPT: ".$code);
      eval($code);
    }
    return;
  case "import":

    break;
  case "export":

    break;
  case "global":
    if (($trailing=="on") or ($trailing=="off"))
    {
      if ($trailing=="on")
      {
        set_bucket("LIVE_SCRIPT_GLOBAL_EXECUTE","enabled");
        privmsg("live script global exec flag set");
      }
      else
      {
        unset_bucket("LIVE_SCRIPT_GLOBAL_EXECUTE");
        privmsg("live script global exec flag cleared");
      }
    }
    break;
  case "kill":
    unset_bucket("LIVE_SCRIPT_GLOBAL_EXECUTE");
    term_echo("*** LIVE SCRIPTING KILLED: live script global exec flag cleared with kill command");
    break;
  case "enable":
    if ($trailing=="")
    {
      privmsg("error: script name not specified");
      break;
    }
    if (isset($scripts[$trailing])==False)
    {
      privmsg("error: script named \"$trailing\" not found");
      break;
    }
    $scripts[$trailing]["enabled"]=True;
    $data_changed=True;
    privmsg("script \"$trailing\" enabled");
    break;
  case "disable":
    if ($trailing=="")
    {
      privmsg("error: script name not specified");
      break;
    }
    if (isset($scripts[$trailing])==False)
    {
      privmsg("error: script named \"$trailing\" not found");
      break;
    }
    if (isset($scripts[$trailing]["enabled"])==True)
    {
      unset($scripts[$trailing]["enabled"]);
      $data_changed=True;
      privmsg("script \"$trailing\" disabled");
    }
    else
    {
      privmsg("script \"$trailing\" already disabled");
    }
    break;
  case "delete-script":
    # ~x delete-script myscript
    if ($trailing=="")
    {
      privmsg("error: script name not specified");
      break;
    }
    if (isset($scripts[$trailing])==False)
    {
      privmsg("error: script named \"$trailing\" not found");
      break;
    }
    unset($scripts[$trailing]);
    set_array_bucket($scripts,"<<LIVE_SCRIPTS>>");
    privmsg("deleted script \"$trailing\"");
    return;
  case "open": # open script
    # ~x open myscript
    if ($trailing=="")
    {
      privmsg("error: script name not specified");
      break;
    }
    $script_name=$trailing;
    set_bucket("LOADED_SCRIPT_".$nick."_".$dest,$trailing);
    if (isset($scripts[$script_name])==False)
    {
      $scripts[$script_name]=array();
      $data_changed=True;
    }
    privmsg("script \"$script_name\" opened for editing by $nick in $dest");
    break;
  case "close": # close currently open script
    # ~x close
    if ($script_name<>"")
    {
      unset_bucket("LOADED_SCRIPT_".$nick."_".$dest);
      privmsg("script \"$script_name\" closed by $nick in $dest");
    }
    else
    {
      privmsg("error: no script opened for editing by $nick in $dest");
    }
    break;
  case "code": # outputs lines of code for currently open script
    # ~x code
    if ($script_name<>"")
    {
      $n=count($script_lines);
      if ($n==0)
      {
        privmsg("no lines in \"$script_name\" script");
      }
      else
      {
        for ($i=0;$i<$n;$i++)
        {
          $L=$i+1;
          privmsg("[L$L] ".$script_lines[$i]);
        }
      }
    }
    else
    {
      privmsg("error: no script opened for editing by $nick in $dest");
    }
    return;
  case "list":
    $n=count($scripts);
    if ($n==0)
    {
      privmsg("no scripts available");
      return;
    }
    privmsg("scripts available:");
    $i=0;
    foreach ($scripts as $script_name => $data)
    {
      $msg=$script_name;
      if (isset($data["enabled"])==True)
      {
        $msg=$msg." [enabled]";
      }
      if ($i==($n-1))
      {
        privmsg("└─".$msg);
      }
      else
      {
        privmsg("├─".$msg);
      }
      $i++;
    }
    return;
  case "replace": # replace text
    # ~x replace [L]5 old text|new text
    $line_no=$parts[0];
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    if ($line_no=="")
    {
      privmsg("error: line number not specified");
      break;
    }
    if (strtoupper($line_no[0])=="L")
    {
      $line_no=substr($line_no,1);
    }
    if (exec_is_integer($line_no)==False)
    {
      privmsg("error: invalid line number");
      break;
    }
    if (isset($script_lines[$line_no-1])==False)
    {
      privmsg("error: line number not found");
      break;
    }
    $parts=explode("|",$trailing);
    if (count($parts)<2)
    {
      privmsg("error: invalid replace syntax");
      break;
    }
    $old_text=$parts[0];
    if (count($parts)<2)
    {
      privmsg("error: nothing to replace");
      break;
    }
    array_shift($parts);
    $new_text=implode("|",$parts);
    $n=0;
    $script_lines[$line_no-1]=str_replace($old_text,$new_text,$script_lines[$line_no-1],$n);
    if ($n>0)
    {
      $data_changed=True;
      privmsg("$n replacements made");
    }
    break;
  case "delete-line":
    # ~x delete-line [L]5
    if ($trailing=="")
    {
      privmsg("error: line number not specified");
      break;
    }
    if (strtoupper($trailing[0])=="L")
    {
      $trailing=substr($trailing,1);
    }
    if (exec_is_integer($trailing)==False)
    {
      privmsg("error: invalid line number");
      break;
    }
    if (isset($script_lines[$trailing-1])==False)
    {
      privmsg("error: line number not found");
      break;
    }
    unset($script_lines[$trailing-1]);
    $script_lines=array_values($script_lines);
    $data_changed=True;
    privmsg("script line removed");
    break;
  case "insert":
    # ~x insert [L]5 while (True) { privmsg("flooding++"); }
    $line_no=$parts[0];
    array_shift($parts);
    $trailing=trim(implode(" ",$parts));
    if ($line_no=="")
    {
      privmsg("error: line number not specified");
      break;
    }
    if (strtoupper($line_no[0])=="L")
    {
      $line_no=substr($line_no,1);
    }
    if (exec_is_integer($line_no)==False)
    {
      privmsg("error: invalid line number");
      break;
    }
    if (isset($script_lines[$line_no-1])==False)
    {
      privmsg("error: line number not found");
      break;
    }
    if ($trailing=="")
    {
      privmsg("error: no code to insert");
      break;
    }
    array_splice($script_lines,$line_no-1,0,$trailing);
    $data_changed=True;
    privmsg("script line inserted");
    break;
  case "add":
    # ~x add while (True) { privmsg("flooding++"); }
    if ($script_name=="")
    {
      privmsg("error: no scripts opened for editing by $nick in $dest");
      break;
    }
    if ($trailing=="")
    {
      privmsg("error: nothing to append");
      break;
    }
    $script_lines[]=$trailing;
    $data_changed=True;
    privmsg("script line appended");
    break;
}

if (($data_changed==True) and ($script_name<>""))
{
  $scripts[$script_name]["code"]=base64_encode(implode("\n",$script_lines));
  set_array_bucket($scripts,"<<LIVE_SCRIPTS>>");
}

#####################################################################################################

?>
