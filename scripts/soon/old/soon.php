<?php

#####################################################################################################

# "soon" is intended to be a sort of macro processor that can translate pseudo-code to php

#####################################################################################################

/*
exec:~soon|30|0|0|1|@||||php scripts/soon.php %%trailing%% %%dest%% %%nick%% %%cmd%%
init:~soon register-events
*/

#####################################################################################################

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$cmd=$argv[4];

if ($dest<>"#irciv")
{
  return;
}

ini_set("display_errors","on");

require_once("lib.php");
require_once("soon_lib.php");
require_once("soon_test.php");

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~soon %%trailing%%");
  return;
}

if ($cmd=="INTERNAL")
{
  # PRIVMSG event triggered 
  return;
}
else
{
  # manually triggered
  $translations=load_translations();
  #var_dump($translations);
  #translate($translations,"hellox3");
  run_tests();
  #privmsg($code);
  return;

  /*$parts=explode(" ",$trailing);
  $action=strtolower($parts[0]);
  array_shift($parts);
  $trailing=trim(implode(" ",$parts));
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
