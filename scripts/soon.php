<?php

#####################################################################################################

# "soon" translates pseudo-code to php (thanks to arti for the name idea)

#####################################################################################################

/*
exec:~soon|30|0|0|1|*||||php scripts/soon.php %%trailing%% %%dest%% %%nick%% %%cmd%%
init:~soon register-events
*/

#####################################################################################################

require_once("lib.php");
#require_once("soon_lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$cmd=$argv[4];

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~soon %%trailing%%");
  return;
}

if ($cmd=="INTERNAL")
{
  # PRIVMSG event triggered
}
else
{
  # manually triggered
  require_once("soon_lib.php");
  map_pseudo_code("loop 10 msg \"hello\"","loop n code","for (\$i=1;\$i<=n;\$i++) { code }");
  return;

  /*$parts=explode(" ",$trailing);
  $action=strtolower($parts[0]);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
  $translations=load_translations();
  if ($translations===False)
  {
    privmsg("  error loading translations file");
    return;
  }
  switch ($action)
  {
    case "translate":
      $translated=translate($trailing,$translations);
      if (($trailing===False) or ($trailing==""))
      {
        privmsg("  error translating code");
      }
      else
      {
        $params=array();
        $params["content"]=$source_body;
        $response=wpost("paste.my.to","/",80,ICEWEASEL_UA,$params);
        privmsg("  ".exec_get_header($response,"location"));
      }
      break;
  }*/
}

#####################################################################################################

?>
