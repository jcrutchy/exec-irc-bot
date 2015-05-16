<?php

#####################################################################################################

/*
exec:~title-internal|30|0|0|0||INTERNAL|||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
exec:~title|30|0|0|0|||||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
exec:~sizeof|30|0|0|0|*||#journals,#test,#Soylent,#,#exec,#dev||php scripts/title.php %%trailing%% %%alias%% %%dest%% %%nick%%
init:~title-internal register-events
*/

#####################################################################################################

require_once("lib.php");
require_once("title_lib.php");

$trailing=trim($argv[1]);
$alias=trim($argv[2]);
$dest=$argv[3];
$nick=$argv[4];

$bucket=get_bucket("<exec_title_$dest>");

if ($alias=="~title-internal")
{
  $parts=explode(" ",$trailing);
  $action=strtolower($parts[0]);
  array_shift($parts);
  switch ($action)
  {
    case "register-events":
      register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~title-internal event-privmsg %%nick%% %%dest%% %%trailing%%");
      return;
    case "event-privmsg":
      # trailing = <nick> <channel> <trailing>
      $nick=strtolower($parts[0]);
      $channel=strtolower($parts[1]);
      array_shift($parts);
      array_shift($parts);
      $trailing=trim(implode(" ",$parts));
      if ($bucket=="on")
      {
        title_privmsg($trailing,$channel);
      }
      break;
  }
}
else
{
  if (strtolower($trailing)=="on")
  {
    if ($bucket=="on")
    {
      privmsg("  titles already enabled for ".chr(3)."10$dest");
    }
    else
    {
      set_bucket("<exec_title_$dest>","on");
      privmsg("  titles enabled for ".chr(3)."10$dest");
    }
  }
  elseif (strtolower($trailing)=="off")
  {
    if ($bucket=="")
    {
      privmsg("  titles already disabled for ".chr(3)."10$dest");
    }
    else
    {
      unset_bucket("<exec_title_$dest>");
      privmsg("  titles disabled for ".chr(3)."10$dest");
    }
  }
  else
  {
    $msg=get_title($trailing,$alias);
    if ($msg!==False)
    {
      privmsg($msg);
    }
  }
}

#####################################################################################################

?>
