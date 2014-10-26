<?php

# gpl2
# by crutchy

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

function users_get_account($nick)
{
  $nick=strtolower(trim($nick));
  $users=get_array_bucket(BUCKET_USERS);
  if (isset($users[$nick])==True)
  {
    if (isset($users[$nick]["account"])==True)
    {
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
    sleep(1);
  }
  while ((microtime(True)-$start)<5.0);
  #return False;
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

?>
