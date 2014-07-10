<?php

# gpl2
# by crutchy
# 10-july-2014

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$parts=explode(" ",$trailing);
$cmd=strtoupper($parts[0]);
array_shift($parts);
$trailing=implode(" ",$parts);
unset($parts);

switch ($cmd)
{
  case "JOIN":

    break;
  case "KICK":

    break;
  case "NICK":

    break;
  case "PART":

    break;
  case "QUIT":

    break;
  case "319":

    break;
  case "330":

    break;
  case "353":

    break;
}

#####################################################################################################

/*function nickserv_rename()
{
    if ((count($parts)==3) and (($nick==NICK_EXEC) or ($alias==$admin_alias)))
    {
      $old=$parts[1];
      $new=$parts[2];
      if ((isset($players[$old])==True) and (isset($players[$new])==False))
      {
        $player_data=$players[$old];
        $players[$new]=$player_data;
        unset($players[$old]);
        $update_players=True;
        privmsg_player_game_chans($old,"player \"$old\" renamed to \"$new\"");
        $chan_list=get_bucket($old."_channel_list");
        if ($chan_list<>"")
        {
          set_bucket($new."_channel_list",$chan_list);
        }
        irciv_term_echo("PLAYER \"$old\" RENAMED TO \"$new\"");
      }
      else
      {
        if (isset($players[$old])==True)
        {
          privmsg_player_game_chans($old,"error renaming player \"$old\" to \"$new\"");
        }
      }
    }
    else
    {
      if ($nick<>NICK_EXEC)
      {
        irciv_term_echo("ACTION_RENAME: only exec can perform logins");
      }
      else
      {
        irciv_term_echo("ACTION_RENAME: invalid login message");
      }
    }
    break;
}*/

#####################################################################################################

/*function nickserv_login($params)
{
      $parts=explode(" ",$params);
      if ((count($parts)==3) and ($parts[0]==NICK_EXEC))
      {
        $nick=$parts[1];
        $account=$parts[2];
        if ($nick<>NICK_EXEC)
        {
          $player_channel_list=explode(" ",get_bucket($nick."_channel_list"));
          for ($i=0;$i<count($game_chans);$i++)
          {
            if (in_array($game_chans[$i],$player_channel_list)==True)
            {
              echo "/INTERNAL ~civ login $nick $account\n";

    if ((count($parts)==3) and ($nick==NICK_EXEC))
    {
      $player=$parts[1];
      $account=$parts[2];
      if (isset($players[$player])==False)
      {
        $player_id=get_new_player_id();
        $players[$player]["account"]=$account;
        $players[$player]["player_id"]=$player_id;
        player_init($player);
        privmsg_player_game_chans($player,"login: welcome new player \"$player\"");
      }
      else
      {
        privmsg_player_game_chans($player,"login: welcome back \"$player\"");
      }
      $players[$player]["login_time"]=microtime(True);
      $players[$player]["logged_in"]=True;
      $update_players=True;
      irciv_term_echo("PLAYER \"$player\" LOGIN");
    }

              break;
            }
          }
        }
      }
}*/

#####################################################################################################

/*function nickserv_validate_logins()
{
  global $players;
  global $start;
  foreach ($players as $nick => $data)
  {
    if (isset($players[$nick]["login_time"])==True)
    {
      if ($players[$nick]["login_time"]<$start)
      {
        $players[$nick]["logged_in"]=False;
      }
    }
  }
}*/

#####################################################################################################

/*function nickserv_is_logged_in($nick)
{
  global $players;
  if (isset($players[$nick]["logged_in"])==False)
  {
    return False;
  }
  if ($players[$nick]["logged_in"]==False)
  {
    return False;
  }
  else
  {
    return True;
  }
}*/

#####################################################################################################

/*function set_chan_list($params,$trailing)
{
  $parts=explode(" ",$params);
  if (count($parts)==2)
  {
    $chans=explode(" ",$trailing);
    for ($i=0;$i<count($chans);$i++)
    {
      if ((substr($chans[$i],0,1)=="+") or (substr($chans[$i],0,1)=="@"))
      {
        $chans[$i]=substr($chans[$i],1);
      }
    }
    $chan_list=implode(" ",$chans);
    set_bucket($parts[1]."_channel_list",$chan_list);
  }
}*/

#####################################################################################################

?>
