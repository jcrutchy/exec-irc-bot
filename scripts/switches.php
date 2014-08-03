<?php

# gpl2
# by crutchy
# 3-aug-2014

#####################################################################################################

require_once("lib.php");

#####################################################################################################

function handle_switch($alias,$dest,$nick,$trailing,$channels_bucket,$switch_alias,$internal_alias,&$msg)
{
  $channels=get_bucket($channels_bucket);
  if ($channels<>"")
  {
    $channels=unserialize($channels);
    if ($channels===False)
    {
      $channels=array();
      save_channels($channels,$channels_bucket);
    }
  }
  else
  {
    $channels=array();
    save_channels($channels,$channels_bucket);
  }
  if ($alias==$switch_alias)
  {
    switch (strtolower($trailing))
    {
      case "on":
        if (in_array($dest,$channels)==False)
        {
          $channels[]=$dest;
          save_channels($channels,$channels_bucket);
          return 1;
        }
        else
        {
          return 2;
        }
        break;
      case "off":
        if (channel_off($channels,$dest,$channels_bucket)==True)
        {
          return 3;
        }
        else
        {
          return 4;
        }
        break;
    }
  }
  elseif ($alias==$internal_alias)
  {
    $parts=explode(" ",$trailing);
    $command=strtolower($parts[0]);
    array_shift($parts);
    $msg=implode(" ",$parts);
    switch ($command)
    {
      case "kick":
        if (count($parts)==2)
        {
          if ($parts[1]==NICK_EXEC)
          {
            channel_off($channels,$parts[0],$channels_bucket);
            return 5;
          }
        }
        break;
      case "part":
        if ($nick==NICK_EXEC)
        {
          channel_off($channels,$msg,$channels_bucket);
          return 6;
        }
        break;
      case "privmsg":
        if ((in_array($dest,$channels)==True) and ($nick<>NICK_EXEC))
        {
          return 7;
        }
        return 8;
      case "join":
        if ((in_array($dest,$channels)==True) and ($nick<>NICK_EXEC))
        {
          return 9;
        }
        return 10;
    }
  }
  return 0;
}

#####################################################################################################

function channel_off(&$channels,$chan,$channels_bucket)
{
  $i=array_search($chan,$channels);
  if ($i!==False)
  {
    unset($channels[$i]);
    $channels=array_values($channels);
    save_channels($channels,$channels_bucket);
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function save_channels($channels,$channels_bucket)
{
  $channels=serialize($channels);
  set_bucket($channels_bucket,$channels);
}

#####################################################################################################

?>
