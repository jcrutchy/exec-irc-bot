<?php

# gpl2
# by crutchy
# 12-aug-2014

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
  case "REBUILD":
    term_echo("*** RE-BUILDING CHANNEL/NICK REGISTER ***");
    users_rebuild();
    break;
  case "LIST-CHANNELS":
    $channels=get_array_bucket(BUCKET_CHANNELS);
    privmsg("*** channels: ".implode(", ",$channels));
    break;
  case "LIST-NICKS":
    $nicks=get_array_bucket(BUCKET_NICKS);
    #privmsg("*** nicks: ".implode(", ",$nicks));
    var_dump($nicks);
    break;
  case "LIST-ACCOUNTS":
    $accounts=get_array_bucket(BUCKET_ACCOUNTS);
    #privmsg("*** accounts: ".implode(", ",$accounts));
    var_dump($accounts);
    break;
  case "COUNT-CHANNELS":
    $channels=get_array_bucket(BUCKET_CHANNELS);
    privmsg("*** ".count($channels)." channels registered");
    break;
  case "COUNT-NICKS":
    $nicks=get_array_bucket(BUCKET_NICKS);
    privmsg("*** ".count($nicks)." nicks registered");
    break;
  case "322": # <calling_nick> <channel> <nick_count>
    handle_322($trailing);
    break;
  case "354": # <calling_nick> 152 <channel> <nick> <mode_info>
    handle_354($trailing);
    break;
  case "330": # <calling_nick> <nick> <account>
    handle_330($trailing);
    break;
}

#####################################################################################################

?>
