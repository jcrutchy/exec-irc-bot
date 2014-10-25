<?php

# gpl2
# by crutchy

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

# ~x o myscript
# ~x c
# ~x l

# ~x r L5



# could try a git-like branch thing, but try to keep it simple to start with

$scripts=get_array_bucket("<<LIVE_SCRIPTS>>");
$script_data=array();
$script_lines=array();
$script_name=get_bucket("SCRIPT_FILE_OPEN_".$nick."_".$dest);
if ($script_name<>"")
{
  $script_data=&$scripts[$script_name];
  if (isset($scripts[$script_name])==True)
  {
    $script_lines=explode("\n",$script_data["code"]);
  }
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($action)
{
  case "register-events":
    register_event_handler("PRIVMSG",":".NICK_EXEC." INTERNAL :~x event-privmsg %%nick%% %%dest%% %%trailing%%");
    break;
  case "event-privmsg":
    term_echo("*** LIVE SCRIPTING PRIVMSG EVENT ***");
    foreach ($scripts as $script_name => $data)
    {
      $code=$data["code"];
    }
    break;
  case "o":
    $script_name=$trailing;
    $scripts[$script_name]=array();
    set_bucket("SCRIPT_FILE_OPEN_".$nick."_".$dest,$trailing);
    privmsg("script \"$script_name\" opened for editing by $nick in $dest");
    break;
  case "c":
    $script_name=get_bucket("SCRIPT_FILE_OPEN_".$nick."_".$dest);
    if ($script_name<>"")
    {
      unset_bucket("SCRIPT_FILE_OPEN_".$nick."_".$dest);
      privmsg("script \"$script_name\" closed by $nick in $dest");
    }
    else
    {
      privmsg("no scripts opened for editing by $nick in $dest");
    }
    break;
  case "l":
    $script_name=get_bucket("SCRIPT_FILE_OPEN_".$nick."_".$dest);
    if ($script_name<>"")
    {
      $n=count($script_lines);
      for ($i=0;$i<$n;$i++)
      {
        privmsg($script_lines[$i]);
      }
    }
    else
    {
      privmsg("no scripts opened for editing by $nick in $dest");
    }
    break;
  case "m": # modify line
    # ~x m L5 while (True) { privmsg("flooding++"); }
    break;
  case "r": # remove line
    break;
  case "i": # insert line
    # ~x i L5 while (True) { privmsg("flooding++"); }
    break;
  case "a": # append line
    # ~x a while (True) { privmsg("flooding++"); }
    if ($script_name<>"")
    {
      $script_lines[]=$trailing;
      privmsg("script line appended");
    }
    else
    {
      privmsg("no scripts opened for editing by $nick in $dest");
    }
    break;
}

if ($script_name<>"")
{
  $script_data["code"]=implode("\n",$script_lines);
  set_array_bucket($scripts,"<<LIVE_SCRIPTS>>");
}

#####################################################################################################

?>
