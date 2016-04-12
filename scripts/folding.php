<?php

#####################################################################################################

/*
exec:~fah|10|3600|0|1|||||php scripts/folding.php %%trailing%% %%dest%% %%nick%% %%alias%% %%cmd%% %%server%%
*/

#####################################################################################################

require_once("lib.php");
require_once("wget_lib.php");

$trailing=$argv[1];
$dest=$argv[2];
$nick=$argv[3];
$alias=$argv[4];
$cmd=$argv[5];
$server=$argv[6];

$current_rank=quick_wget("http://folding.extremeoverclocking.com/xml/team_summary.php?t=230319 <Rank> <> </Rank>");
if ($current_rank===False)
{
  return;
}

$prev_rank=get_bucket("~fah.prev_rank");

if ($prev_rank==$current_rank)
{
  return;
}

if ($current_rank=="")
{
  return;
}

set_bucket("~fah.prev_rank",$current_rank);
pm("#folding","current rank for team SoylentNews is ".$current_rank);

#####################################################################################################

?>
