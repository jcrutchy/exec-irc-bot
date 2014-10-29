<?php

# gpl2
# by crutchy

# could try a git-like branch thing, but try to keep it simple to start with

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

$scripts=get_array_bucket("<<LIVE_SCRIPTS>>");
$script_data=array();
$script_lines=array();
$script_name=get_bucket("LOADED_SCRIPT_".$nick."_".$dest);
if ($script_name<>"")
{
  if (isset($scripts[$script_name]["code"])==True)
  {
    $script_lines=explode("\n",trim(base64_decode($scripts[$script_name]["code"])));
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
    term_echo("*** LIVE SCRIPTING PRIVMSG EVENT ***");
    # trailing = crutchy # test
    $parts=explode(" ",$trailing);
    if (count($parts)>2)
    {
      $nick=$parts[0];
      $dest=$parts[1];
      array_shift($parts);
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
    }
    foreach ($scripts as $script_name => $data)
    {
      $code=trim(base64_decode($data["code"]));
      $code=implode(" ",explode("\n",$code));
      term_echo("*** LIVE SCRIPT: ".$code);
      eval($code);
    }
    return;
  case "o": # open script
    # ~x o myscript
    if ($trailing=="")
    {
      privmsg("error: script name not specified");
      break;
    }
    $script_name=$trailing;
    $scripts[$script_name]=array();
    set_bucket("LOADED_SCRIPT_".$nick."_".$dest,$trailing);
    $data_changed=True;
    privmsg("script \"$script_name\" opened for editing by $nick in $dest");
    break;
  case "c":
    $script_name=get_bucket("LOADED_SCRIPT_".$nick."_".$dest);
    if ($script_name<>"")
    {
      unset_bucket("LOADED_SCRIPT_".$nick."_".$dest);
      privmsg("script \"$script_name\" closed by $nick in $dest");
    }
    else
    {
      privmsg("error: no scripts opened for editing by $nick in $dest");
    }
    break;
  case "l":
    $script_name=get_bucket("LOADED_SCRIPT_".$nick."_".$dest);
    if ($script_name<>"")
    {
      $n=count($script_lines);
      for ($i=0;$i<$n;$i++)
      {
        $L=$i+1;
        privmsg("[L$L] ".$script_lines[$i]);
      }
    }
    else
    {
      privmsg("error: no scripts opened for editing by $nick in $dest");
    }
    break;
  case "m": # modify line
    # ~x m L5 while (True) { privmsg("flooding++"); }
    break;
  case "r": # remove line
    # ~x r 5
    if ($trailing=="")
    {
      privmsg("error: line number not specified");
      break;
    }
    $i=$trailing;
    unset($script_lines[$i]);
    $data_changed=True;
    privmsg("script line appended");
    break;
  case "i": # insert line
    # ~x i L5 while (True) { privmsg("flooding++"); }
    break;
  case "a": # append line
    # ~x a while (True) { privmsg("flooding++"); }
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
  $scripts[$script_name]["code"]=base64_encode(trim(implode("\n",$script_lines)));
  set_array_bucket($scripts,"<<LIVE_SCRIPTS>>");
}

#####################################################################################################

?>
