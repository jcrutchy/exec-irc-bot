<?php

#####################################################################################################

/*
exec:~remind|9999|0|0|0|||||php scripts/remind.php %%trailing%% %%nick%% %%dest%% %%alias%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];
$nick=$argv[2];
$dest=$argv[3];
$alias=$argv[4];

$syntax="syntax: ~remind value min|m|sec|s [optional msg to display]";

$parts=explode(" ",$trailing);
if (count($parts)<2)
{
  privmsg("error: incorrect number of parameters");
  privmsg($syntax);
  return;
}

$value=$parts[0];
$unit=$parts[1];
array_shift($parts);
array_shift($parts);
$msg=implode(" ",$parts);

if ((exec_is_integer($value)==False) or ($value=="") or ($value<0) or ($value>99990))
{
  privmsg("error: value must be a positive integer");
  privmsg($syntax);
  return;
}

switch (strtolower($unit))
{
  case "min":
  case "m":
    $value=$value*60;
    break;
  case "sec":
  case "s":
    break;
  default:
    privmsg("error: invalid unit");
    privmsg($syntax);
    return;
}

privmsg("*** reminder set for $nick: $msg");

sleep($value);
if ($msg<>"")
{
  privmsg("*** reminder for $nick: $msg");
}
else
{
  privmsg("*** reminder for $nick");
}

#####################################################################################################

?>
