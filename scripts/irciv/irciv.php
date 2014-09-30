<?php

# gpl2
# by crutchy

#####################################################################################################

date_default_timezone_set("UTC");

require_once("irciv_lib.php");

$nick=strtolower(trim($argv[1]));
$trailing=trim($argv[2]);
$dest=strtolower(trim($argv[3]));
$start=trim($argv[4]);
$alias=strtolower(trim($argv[5]));
$cmd=strtoupper(trim($argv[6]));

if (($cmd=="INTERNAL") and ($nick==NICK_EXEC) and ($trailing=="startup"))
{
  civ_startup();
}

if ($trailing=="")
{
  irciv_privmsg("http://sylnt.us/irciv");
  return;
}

$parts=explode(" ",$trailing);
$action=strtolower($parts[0]);

$user=get_array_bucket("irciv_user_$nick");
$last_game_chan="";
if (substr($dest,0,1)=="#")
{
  $last_game_chan=$dest;
}
elseif (isset($user["last_game_chan"])==True)
{
  $last_game_chan=$user["last_game_chan"];
}

switch ($action)
{
  case "join":
    irciv_term_echo("join: $trailing");
    return;
}

#####################################################################################################

function civ_startup()
{
  register_event_handler("JOIN",":".NICK_EXEC." INTERNAL :~civ join %%nick%% %%params%%");
}

#####################################################################################################

?>
