<?php

#####################################################################################################

/*
exec:~users|10|0|0|0|crutchy||||php scripts/users.php %%trailing%% %%nick%% %%dest%% %%alias%%
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
  case "hostname":
    privmsg(users_get_hostname(trim($trailing)));
    break;
  case "nicks":
    $channel=strtolower(trim($trailing));
    $nicks=users_get_nicks($channel);
    privmsg(implode(" ",$nicks));
    break;
  case "channels":
    $subject_nick=strtolower(trim($trailing));
    $account=users_get_account($subject_nick);
    $channels=users_get_channels($subject_nick);
    privmsg(implode(" ",$channels));
    break;
  case "all-channels":
    $channels=users_get_all_channels();
    privmsg(implode(" ",$channels));
    break;
  case "count":
    $channel=strtolower(trim($trailing));
    $n=users_count_nicks($channel);
    privmsg("nicks in $channel: $n");
    break;
  case "data":
    $subject_nick=strtolower(trim($trailing));
    $account=users_get_account($subject_nick);
    $user=users_get_data($subject_nick);
    if (isset($user["channels"])==True)
    {
      $channels=array_keys($user["channels"]);
      for ($i=0;$i<count($channels);$i++)
      {
        $channels[$i]=$user["channels"][$channels[$i]].$channels[$i];
      }
      privmsg("channels: ".implode(" ",$channels));
    }
    if (isset($user["nicks"])==True)
    {
      privmsg("nicks: ".implode(" ",array_keys($user["nicks"])));
    }
    if (isset($user["account"])==True)
    {
      privmsg("account: ".$user["account"]);
    }
    if (isset($user["prefix"])==True)
    {
      privmsg("prefix: ".$user["prefix"]);
    }
    if (isset($user["user"])==True)
    {
      privmsg("user: ".$user["user"]);
    }
    if (isset($user["hostname"])==True)
    {
      privmsg("hostname: ".$user["hostname"]);
    }
    if (isset($user["connected"])==True)
    {
      if ($user["connected"]==True)
      {
        privmsg("connected: yes");
      }
      else
      {
        privmsg("connected: no");
      }
    }
    var_dump($user);
    break;
  case "account":
    $subject_nick=strtolower(trim($trailing));
    $account=users_get_account($subject_nick);
    if ($account!==False)
    {
      privmsg("account for $subject_nick: $account");
    }
    break;
}

#####################################################################################################

?>
