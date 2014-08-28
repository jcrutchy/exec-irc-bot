<?php

# gpl2
# by crutchy
# 28-aug-2014

#####################################################################################################

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

switch ($cmd)
{
  case "ADMIN-ACCOUNT":
    $account=get_account($trailing);
    if ($account!==False)
    {
      privmsg("account for \"$trailing\" is \"$account\"");
    }
    else
    {
      privmsg("account for \"$trailing\" not set");
    }
  case "ADMIN-USERS":
    $nicks=get_channel_users($trailing);
    if ($nicks!==False)
    {
      notice($nick,"[".count($nicks)."] ".implode(" ",$nicks));
    }
  case "ADMIN-USER":
    $user=get_user($trailing);
    var_dump($user);
  case "ADMIN-SAVE-ALL":
    save_all();
  case "353": # trailing = <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
    handle_353($trailing);
    break;
  case "330": # trailing = <calling_nick> <nick> <account>
    handle_330($trailing);
    break;
  case "JOIN": # trailing = <channel>
    handle_join($nick,$trailing);
    break;
  case "KICK": # trailing = <channel> <kicked_nick>
    handle_kick($nick,$trailing);
    break;
  case "NICK": # trailing = <new_nick>
    handle_nick($nick,$trailing);
    break;
  case "PART": # trailing = <channel>
    handle_part($nick,$trailing);
    break;
  case "QUIT":
    handle_quit($nick);
    break;
}

#####################################################################################################

?>
