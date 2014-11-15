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
    privmsg(implode(" ",$nicks));
    break;
  case "channels":
    $nick=strtolower(trim($trailing));
    $account=users_get_account($nick);
    $channels=users_get_channels($nick);
    privmsg(implode(" ",$channels));
    break;
  case "count":
    $channel=strtolower(trim($trailing));
    $n=users_count_nicks($channel);
    privmsg("nicks in $channel: $n");
    break;
  case "data":
    $nick=strtolower(trim($trailing));
    $user=users_get_data($nick);
    if (isset($user["channels"])==True)
    {
      privmsg("channels: ".implode(" ",array_keys($user["channels"])));
    }
    if (isset($user["account"])==True)
    {
      privmsg("account: ".$user["account"]);
    }
    var_dump($user);
    break;
  case "account":
    $nick=strtolower(trim($trailing));
    $account=users_get_account($nick);
    if ($account!==False)
    {
      privmsg("account for $nick: $account");
    }
    break;
}

#####################################################################################################

?>
