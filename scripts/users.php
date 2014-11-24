<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~users|10|0|0|0|crutchy|||0|php scripts/users.php %%trailing%% %%nick%% %%dest%% %%alias%%
init:~bucket <<EXEC_USERS>> unset
*/

#####################################################################################################

require_once("lib.php");

$trailing=strtolower(trim($argv[1]));
$nick=strtolower(trim($argv[2]));
$dest=strtolower(trim($argv[3]));
$alias=trim($argv[4]);

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$cmd=$parts[0];
array_shift($parts);
$trailing=trim(implode(" ",$parts));

switch ($cmd)
{
  case "nicks":
    $channel=strtolower(trim($trailing));
    $nicks=users_get_nicks($channel);
    notice($nick,implode(" ",$nicks));
    break;
  case "channels":
    $subject_nick=strtolower(trim($trailing));
    $account=users_get_account($subject_nick);
    $channels=users_get_channels($subject_nick);
    notice($nick,implode(" ",$channels));
    break;
  case "all-channels":
    $channels=users_get_all_channels();
    notice($nick,implode(" ",$channels));
    break;
  case "count":
    $channel=strtolower(trim($trailing));
    $n=users_count_nicks($channel);
    notice($nick,"nicks in $channel: $n");
    break;
  case "data":
    $subject_nick=strtolower(trim($trailing));
    $user=users_get_data($subject_nick);
    if (isset($user["channels"])==True)
    {
      notice($nick,"channels: ".implode(" ",array_keys($user["channels"])));
    }
    if (isset($user["nicks"])==True)
    {
      notice($nick,"nicks: ".implode(" ",array_keys($user["nicks"])));
    }
    if (isset($user["account"])==True)
    {
      notice($nick,"account: ".$user["account"]);
    }
    if (isset($user["prefix"])==True)
    {
      notice($nick,"prefix: ".$user["prefix"]);
    }
    if (isset($user["user"])==True)
    {
      notice($nick,"user: ".$user["user"]);
    }
    if (isset($user["hostname"])==True)
    {
      notice($nick,"hostname: ".$user["hostname"]);
    }
    if (isset($user["connected"])==True)
    {
      if ($user["connected"]==True)
      {
        notice($nick,"connected: yes");
      }
      else
      {
        notice($nick,"connected: no");
      }
    }
    var_dump($user);
    break;
  case "account":
    $subject_nick=strtolower(trim($trailing));
    $account=users_get_account($subject_nick);
    if ($account!==False)
    {
      notice($nick,"account for $subject_nick: $account");
    }
    break;
}

#####################################################################################################

?>
