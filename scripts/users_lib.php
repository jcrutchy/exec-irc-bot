<?php

#####################################################################################################

define("BUCKET_USERS","<<EXEC_USERS>>");

#####################################################################################################

function users_get_nicks($channel)
{
  $nicks=array();
  $users=get_array_bucket(BUCKET_USERS);
  foreach ($users as $nick => $data)
  {
    if (isset($data["channels"][$channel])==True)
    {
      $nicks[]=$nick;
    }
    /*if ((isset($data["connected"])==True) and (isset($data["channels"][$channel])==True))
    {
      if ($data["connected"]==True)
      {
        $nicks[]=$nick;
      }
    }*/
  }
  sort($nicks);
  return $nicks;
}

#####################################################################################################

function users_get_data($nick)
{
  $result=array();
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick])==True)
  {
    $result=$users[$nick];
  }
  return $result;
}

#####################################################################################################

function users_get_nick($hostname)
{
  $users=get_array_bucket(BUCKET_USERS);
  foreach ($users as $nick => $data)
  {
    if (isset($data["hostname"])==True)
    {
      if ($data["hostname"]==$hostname)
      {
        return $nick;
      }
    }
  }
  return False;
}

#####################################################################################################

function users_get_hostname($nick)
{
  $nick=strtolower(trim($nick));
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick])==True)
  {
    if (isset($users[$nick]["hostname"])==True)
    {
      # TODO: expiry
      return $users[$nick]["hostname"];
    }
  }
  $start=microtime(True);
  rawmsg("USERHOST $nick");
  do
  {
    $users=get_array_bucket(BUCKET_USERS);
    if (isset($users[$nick])==True)
    {
      if (isset($users[$nick]["hostname"])==True)
      {
        return $users[$nick]["hostname"];
      }
    }
    else
    {
      break;
    }
    usleep(0.2*1e6);
  }
  while ((microtime(True)-$start)<5.0);
  return "";
}

#####################################################################################################

function users_get_account($nick)
{
  $nick=strtolower(trim($nick));
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick])==True)
  {
    if (isset($users[$nick]["account"])==True)
    {
      # TODO: expiry
      return $users[$nick]["account"];
    }
  }
  $start=microtime(True);
  rawmsg("WHOIS $nick");
  do
  {
    $users=get_array_bucket(BUCKET_USERS);
    if (isset($users[$nick])==True)
    {
      if (isset($users[$nick]["account"])==True)
      {
        return $users[$nick]["account"];
      }
    }
    else
    {
      break;
    }
    usleep(0.2*1e6);
  }
  while ((microtime(True)-$start)<5.0);
  return "";
}

#####################################################################################################

function users_get_channels($nick)
{
  $channels=array();
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick]["channels"])==True)
  {
    foreach ($users[$nick]["channels"] as $channel => $data)
    {
      $channels[]=$channel;
    }
  }
  sort($channels);
  return $channels;
}

#####################################################################################################

function users_get_all_channels()
{
  $channels=array();
  $users=get_array_bucket(BUCKET_USERS);
  foreach ($users as $nick => $nick_data)
  {
    foreach ($nick_data["channels"] as $channel => $chan_data)
    {
      if (in_array($channel,$channels)==False)
      {
        $channels[]=$channel;
      }
    }
  }
  sort($channels);
  return $channels;
}

#####################################################################################################

function users_chan_exists($channel)
{
  $channels=users_get_all_channels();
  if (in_array($channel,$channels)==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function users_count_nicks($channel)
{
  $nicks=array();
  $users=get_array_bucket(BUCKET_USERS);
  foreach ($users as $nick => $data)
  {
    if (isset($data["channels"][$channel])==True)
    {
      $nicks[]=$nick;
    }
  }
  return count($nicks);
}

#####################################################################################################

function users_nick_exists($nick)
{
  $result=array();
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick])==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function users_broadcast($msg)
{
  $channels=users_get_channels(get_bot_nick());
  for ($i=0;$i<count($channels);$i++)
  {
    pm($channels[$i],chr(3)."13".$msg);
  }
}

#####################################################################################################

?>
