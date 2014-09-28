<?php

# gpl2
# by crutchy

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
    $nicks=users_get_nicks($trailing);
    privmsg(implode(" ",$nicks));
    break;
  case "channels":
    users_get_channels($trailing);
    break;
  case "count":
    $n=users_count_nicks($trailing);
    privmsg("nicks in $trailing: $n");
    break;
  case "data":
    $user=users_get_data($trailing);
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
}

#####################################################################################################

?>
