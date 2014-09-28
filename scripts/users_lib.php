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

function users_get_channels($nick)
{

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

?>
