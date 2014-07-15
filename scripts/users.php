<?php

# gpl2
# by crutchy
# 15-july-2014

#####################################################################################################

require_once("users_lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

term_echo("users.php started");

$parts=explode(" ",$trailing);
$cmd=strtoupper($parts[0]);
array_shift($parts);
$trailing=implode(" ",$parts);
unset($parts);

$users=array();

switch ($cmd)
{
  case "JOIN":
    # $nick = "joining_nick"
    # $trailing = "chan"
    $chan=strtolower($trailing);
    if (isset($users[$nick])==False)
    {
      $users[$nick]["channels"]=array();
    }
    if (in_array($trailing,$users[$nick]["channels"])==False)
    {
      $users[$nick]["channels"][]=$chan;
      on_join($nick,$chan);
      whois($nick);
    }
    break;
  case "KICK":
    # $nick = "op_nick"
    # $trailing = "chan kicked_nick"
    $parts=explode(" ",$trailing);
    if (count($parts)==2)
    {
      $chan=$parts[0];
      $kicked_nick=$parts[1];
      if (isset($users[$kicked_nick])==True)
      {
        $i=array_search($chan,$users[$kicked_nick]["channels"]);
        if ($i!==False)
        {
          unset($users[$kicked_nick]["channels"][$i]);
          on_kick($nick,$kicked_nick,$chan);
        }
      }
    }
    break;
  case "NICK":
    # $nick = "old"
    # $trailing = "new"
    if (isset($users[$nick])==True)
    {
      $tmp=$users[$nick];
      unset($users[$nick]);
      $users[$trailing]=$tmp;
      for ($i=0;$i<count($users[$trailing]["channels"]);$i++)
      {
        on_nick_chan($nick,$trailing,$users[$trailing]["channels"][$i]);
      }
      on_nick($nick,$trailing);
    }
    break;
  case "PART":
    # $nick = "parting_nick"
    # $trailing = "channel"
    if (isset($users[$nick])==True)
    {
      $i=array_search($trailing,$users[$nick]["channels"]);
      if ($i!==False)
      {
        unset($users[$nick]["channels"][$i]);
        on_part($nick,$trailing);
      }
    }
    break;
  case "QUIT":
    # $nick = "quitting_nick"
    if (isset($users[$nick])==True)
    {
      for ($i=0;$i<count($users[$nick]["channels"]);$i++)
      {
        on_quit_chan($nick,$users[$nick]["channels"][$i]);
      }
      unset($users[$nick]);
      on_quit($nick);
    }
    break;
  case "319":
    # $trailing = "whois_call_nick whois_subject_nick space_delimited_chanlist"
    break;
  case "330":
    # $trailing = "whois_call_nick whois_subject_nick whois_subject_account"
    break;
  case "353":
    # $trailing = "exec = chan space_delimited_nick_list"
    break;
}

#####################################################################################################

?>
