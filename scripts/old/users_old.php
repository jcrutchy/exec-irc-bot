<?php

# gpl2
# by crutchy
# 10-aug-2014

#####################################################################################################

# WHO #Soylent %ctnf,152
# :irc.sylnt.us 354 crutchy 152 #Soylent crutchy H
# :irc.sylnt.us 354 crutchy 152 #Soylent mechanicjay G+
# :irc.sylnt.us 354 crutchy 152 #Soylent stderr H+
# :irc.sylnt.us 354 crutchy 152 #Soylent kobach H*+
# :irc.sylnt.us 354 crutchy 152 #Soylent xlefay G*
# :irc.sylnt.us 354 crutchy 152 #Soylent juggler H*@
# :irc.sylnt.us 354 crutchy 152 #Soylent mrcoolbp H@+
# :irc.sylnt.us 354 crutchy 152 #Soylent paulej72 H*+

# TO GET LAG TIME:
# PING LAG1405543897449782
# :irc.sylnt.us PONG irc.sylnt.us :LAG1405543897449782

# ISON paulej72 TheMightyBuzzard Subsentient monopoly arti chromas KonomiNetbook Konomi xlefay Bytram
# :irc.sylnt.us 303 crutchy :paulej72 TheMightyBuzzard monopoly arti chromas KonomiNetbook Konomi xlefay

# LIST >0,<10000
# :irc.sylnt.us 322 crutchy # 8 :exec's home base and proving ground. testing of other bots and general chit chat welcome :-)

require_once("users_lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);
$dest=trim($argv[3]);
$alias=trim($argv[4]);

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$cmd=strtoupper($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));
unset($parts);

$channels=get_array_bucket(BUCKET_CHANNELS);
$users=get_array_bucket(BUCKET_USERS);

switch ($cmd)
{
  case "ADMIN":
    if ($trailing=="list-users")
    {
      var_dump($users);
      privmsg("users: ".implode(", ",array_keys($users)));
      return;
    }
    if ($trailing=="list-channels")
    {
      var_dump($channels);
      privmsg("channels: ".implode(", ",array_keys($channels)));
      return;
    }
    $parts=explode(" ",$trailing);
    if (count($parts)==2)
    {
      $subject=$parts[1];
      switch ($parts[0])
      {
        case "user":
          if (isset($users[$subject])==True)
          {
            var_dump($users[$subject]);
            if (isset($users[$subject]["channels"])==True)
            {
              privmsg("[$subject].channels: ".implode(", ",$users[$subject]["channels"]));
            }
            if (isset($users[$subject]["account"])==True)
            {
              privmsg("[$subject].account: ".$users[$subject]["account"]);
            }
          }
          else
          {
            privmsg("$subject not registered");
          }
          break;
        case "channel":
          var_dump($channels[$parts[1]]);
          break;
      }
    }
    break;
  case "JOIN":
    # $nick = "joining_nick"
    # $trailing = "chan"
    $nick=strtolower($nick);
    $chan=strtolower($trailing);
    if (isset($users[$nick])==False)
    {
      $users[$nick]["channels"]=array();
    }
    if (isset($channels[$chan])==False)
    {
      $channels[$chan]["nicks"]=array();
    }
    if (in_array($trailing,$users[$nick]["channels"])==False)
    {
      $users[$nick]["channels"][]=$chan;
    }
    if (in_array($trailing,$channels[$chan]["nicks"])==False)
    {
      $users[$nick]["channels"][]=$chan;
    }
    on_join($nick,$chan);
    whois($nick);
    break;
  case "KICK":
    # $nick = "op_nick"
    # $trailing = "chan kicked_nick"
    $op_nick=strtolower($nick);
    $parts=explode(" ",strtolower($trailing));
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
          on_kick($op_nick,$kicked_nick,$chan);
        }
      }
    }
    break;
  case "NICK":
    # $nick = "old"
    # $trailing = "new"
    $old_nick=strtolower($nick);
    $new_nick=strtolower($trailing);
    if (isset($users[$old_nick])==True)
    {
      $tmp=$users[$old_nick];
      unset($users[$old_nick]);
      $users[$new_nick]=$tmp;
      for ($i=0;$i<count($users[$new_nick]["channels"]);$i++)
      {
        on_nick_chan($old_nick,$new_nick,$users[$new_nick]["channels"][$i]);
      }
      on_nick($old_nick,$new_nick);
    }
    break;
  case "PART":
    # $nick = "parting_nick"
    # $trailing = "channel"
    $parting_nick=strtolower($nick);
    if (isset($users[$parting_nick])==True)
    {
      $chan=strtolower($trailing);
      $i=array_search($chan,$users[$parting_nick]["channels"]);
      if ($i!==False)
      {
        unset($users[$parting_nick]["channels"][$i]);
        on_part($parting_nick,$chan);
      }
    }
    break;
  case "QUIT":
    # $nick = "quitting_nick"
    $nick=strtolower($nick);
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
    # #wiki +#test #sublight #help @#exec #derp @#civ @#1 @#0 ## @#/ @#> @#~ @#
    $parts=explode(" ",strtolower($trailing));
    if (count($parts)>=3)
    {
      $whois_subject_nick=$parts[1];
      if (isset($users[$whois_subject_nick])==False)
      {
        $users[$whois_subject_nick]["channels"]=array();
      }
      array_shift($parts);
      array_shift($parts);
      for ($i=0;$i<count($parts);$i++)
      {
        $loop_chan=$parts[$i];
        if (isset($channels[$loop_chan])==False)
        {
          $channels[$loop_chan]["nicks"]=array();
        }
        if ((substr($loop_chan,0,1)=="+") or (substr($loop_chan,0,1)=="@"))
        {
          $loop_chan=substr($loop_chan,1);
        }
        if (in_array($loop_chan,$users[$whois_subject_nick]["channels"])==False)
        {
          $users[$whois_subject_nick]["channels"][]=$loop_chan;
          on_nick_chan_list_add($whois_subject_nick,$loop_chan);
        }
      }
      on_nick_chan_list($whois_subject_nick);
    }
    break;
  case "330":
    # $trailing = "whois_call_nick whois_subject_nick whois_subject_account"
    $parts=explode(" ",strtolower($trailing));
    if (count($parts)==3)
    {
      $whois_subject_nick=$parts[1];
      $whois_subject_account=$parts[2];
      if (isset($users[$whois_subject_nick])==False)
      {
        $users[$whois_subject_nick]["channels"]=array();
      }
      $users[$whois_subject_nick]["account"]=$whois_subject_account;
      on_nickserv_account($whois_subject_nick,$whois_subject_account);
    }
    break;
  case "353":
    # $trailing = "exec = chan space_delimited_nick_list"
    # exec @crutchy chromas arti
    $parts=explode(" ",strtolower($trailing));
    if (count($parts)>=4)
    {
      if (($parts[0]==NICK_EXEC) and ($parts[1]=="="))
      {
        $chan=$parts[2];
        array_shift($parts);
        array_shift($parts);
        array_shift($parts);
        for ($i=0;$i<count($parts);$i++)
        {
          $loop_nick=$parts[$i];
          if ((substr($loop_nick,0,1)=="+") or (substr($loop_nick,0,1)=="@"))
          {
            $loop_nick=substr($loop_nick,1);
          }
          if (isset($users[$loop_nick])==False)
          {
            $users[$loop_nick]["channels"]=array();
          }
          if (in_array($chan,$users[$loop_nick]["channels"])==False)
          {
            $users[$loop_nick]["channels"][]=$chan;
            on_chan_nick_list_add($chan,$loop_nick);
          }
          whois($loop_nick);
          sleep(1);
        }
        on_chan_nick_list($chan);
      }
    }
    break;
  case "354":
    # $trailing = "exec 152 cha"
    # crutchy 152 #Soylent mrcoolbp H@+

    break;
  case "322":
    # $trailing = "exec chan_name num_users"

    break;
}

set_array_bucket($channels,BUCKET_CHANNELS);
set_array_bucket($users,BUCKET_USERS);

#####################################################################################################

?>
