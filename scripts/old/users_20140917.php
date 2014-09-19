<?php

# gpl2
# by crutchy
# 10-sep-2014

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
  case "names":
    #rawmsg("NAMES $trailing");
    break;
  case "nicks":
    list_nicks($trailing);
    break;
  case "channels":
    #list_channels($trailing);
    break;
  case "count":
    #count_nicks($trailing);
    break;
  case "353": # trailing = <calling_nick> = <channel> <nick1> <+nick2> <@nick3>
    handle_353($trailing);
    break;
  case "330": # trailing = <calling_nick> <subject_nick> <account>
    #handle_330($trailing);
    break;
  case "319": # trailing = <calling_nick> <subject_nick> <chan1> <+chan2> <@chan3>
    #handle_319($trailing);
    break;
  case "join": # trailing = <channel>
    #handle_join($nick,$trailing);
    break;
  case "kick": # trailing = <channel> <kicked_nick>
    #handle_kick($trailing);
    break;
  case "nick": # trailing = <new_nick>
    #handle_nick($nick,$trailing);
    break;
  case "part": # trailing = <channel>
    #handle_part($nick,$trailing);
    break;
  case "quit":
    #handle_quit($nick);
    break;
}

#####################################################################################################

?>
