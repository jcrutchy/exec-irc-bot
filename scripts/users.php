<?php

# gpl2
# by crutchy
# 23-aug-2014

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
  case "DUMP":
    $nicks=get_array_bucket(BUCKET_NICKS);
    var_dump($nicks);
    break;
  case "COUNT":
    $nicks=get_array_bucket(BUCKET_NICKS);
    privmsg("*** ".count($nicks)." nicks registered");
    break;
  case "353": # trailing = <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
    handle_354($trailing);
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
