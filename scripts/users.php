<?php

# gpl2
# by crutchy
# 10-aug-2014

#####################################################################################################

require_once("users_lib.php");

$trailing=trim($argv[1]);
$nick=trim($argv[2]);
$dest=trim($argv[3]);
$alias=trim($argv[4]);

$channels=get_array_bucket(BUCKET_CHANNELS);
$nicks=get_array_bucket(BUCKET_NICKS);

$parts=explode(" ",$trailing);
delete_empty_elements($parts);
$cmd=strtoupper($parts[0]);
array_shift($parts);
$trailing=trim(implode(" ",$parts));
unset($parts);

switch ($cmd)
{
  case "REBUILD":
    privmsg("rebuilding channel and nick register...");
    users_rebuild();
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

set_array_bucket($channels,BUCKET_CHANNELS);
set_array_bucket($nicks,BUCKET_NICKS);

#####################################################################################################

?>
