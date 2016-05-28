<?php

#####################################################################################################

/*
exec:add ~inventory-internal
exec:edit ~inventory-internal cmds INTERNAL
exec:edit ~inventory-internal cmd php scripts/inventory.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~inventory-internal
init:~inventory-internal register-events
*/

#####################################################################################################

require_once("lib.php");

$trailing=trim($argv[1]);
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

if ($trailing=="")
{
  return;
}

if ($trailing=="register-events")
{
  register_event_handler("PRIVMSG",":%%nick%% INTERNAL %%dest%% :~inventory-internal %%trailing%%");
  return;
}

$last_timestamp=get_bucket("<<INVENTORY_TIMESTAMP>>");
if ($last_timestamp<>"")
{
  if ($timestamp-$last_timestamp)<mt_rand(3,8))
  {
    privmsg("please wait a few seconds before trying again");
    return;
  }
}

set_bucket("<<INVENTORY_TIMESTAMP>>",$timestamp);

$fn=DATA_PATH."exec_inventory_data";

if ($trailing=="~inventory")
{
  if (file_exists($fn)==True)
  {
    output_ixio_paste(file_get_contents($fn));
  }
  else
  {
    privmsg("inventory file not found");
  }
  return;
}

$verbs=array(
  "gives",
  "hands",
  "puts",
  "inserts",
  "shoves");
  
$prepositions=array(
  "in",
  "to",
  "into",
  "toward",
  "on",
  "over",
  "at");
  
$bot_nick=get_bot_nick();

$parts=explode(" ",$trailing);
delete_empty_elements($parts);

# ACTION shoves food in exec

if (count($parts)<5)
{
  return;
}

$token=strtoupper(array_shift($parts));
if ($token<>(chr(1)."ACTION"))
{
  return;
}
$token=strtolower(array_shift($parts));
if (in_array($token,$verbs)==False)
{
  return;
}
$token=array_pop($parts);
if ($token<>($bot_nick.chr(1)))
{
  return;
}
$token=strtolower(array_pop($parts));
if (in_array($token,$prepositions)==False)
{
  return;
}
$item=trim(implode(" ",$parts));
if ($item=="")
{
  return;
}

if (file_exists($fn)==True)
{
  $inventory=json_decode(file_get_contents($fn),True);
  if ($inventory==Null)
  {
    privmsg("error reading inventory file. inventory reset");
    $inventory=array();
  }
}
else
{
  $inventory=array();
}

if (in_array($item,$inventory)==True)
{
  privmsg("item already exists in inventory");
  return;
}

$inventory[]=$item;

if (file_put_contents($fn,json_encode($inventory,JSON_PRETTY_PRINT))===False)
{
  privmsg("error writing inventory file");
  return;
}

privmsg(chr(1)."ACTION is now carrying ".$item.chr(1));

#####################################################################################################

?>
