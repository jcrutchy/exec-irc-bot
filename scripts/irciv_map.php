<?php

# gpl2
# by crutchy
# 27-april-2014

# irciv_map.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

define("CMD_GENERATE","generate");
define("CMD_DUMP","dump");

$cols=80;
$rows=80;

irciv__term_echo("civ-map running...");

$bucket["civ"]["maps"]=array();
get_bucket();
$maps=&$bucket["civ"]["maps"];

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

$admin_nicks=array("crutchy");
if (in_array($nick,$admin_nicks)==False)
{
  return;
}

# civ-map generate
# civ-map dump

$parts=explode(" ",$trailing);

$cmd=$parts[0];

switch ($cmd)
{
  case CMD_GENERATE:
    map_generate($dest);
    break;
  case CMD_DUMP:
    map_dump($dest);
    break;
}

set_bucket();

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function map_generate($chan)
{
  global $maps;
  global $cols;
  global $rows;
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = Up
     1 = Right
     2 = Down
     3 = Left */
  $count=$rows*$cols;
  $maps[$chan]=array();
  $maps[$chan]["coords"]=str_repeat("O",$count);
  $landmass_count=6;
  $landmass_size=70;
  for ($i=0;$i<$landmass_count;$i++)
  {
    $n=0;
    $x=mt_rand(0,$cols-1);
    $y=mt_rand(0,$rows-1);
    $maps[$chan]["coords"][map_coord($cols,$x,$y)]="L";
    $n++;
    $x1=$x;
    $y1=$y;
    $d=mt_rand(0,3);
    while ($n<$landmass_size)
    {
      do
      {
        do
        {
          $d1=mt_rand(0,3);
        }
        while ($d1==$d);
        $d=$d1;
        $x2=$x1+$dir_x[$d];
        $y2=$y1+$dir_y[$d];
      }
      while (($x2<0) or ($y2<0) or ($x2>=$cols) or ($y2>=$rows));
      $x1=$x2;
      $y1=$y2;
      if ($maps[$chan]["coords"][map_coord($cols,$x1,$y1)]<>"L")
      {
        $maps[$chan]["coords"][map_coord($cols,$x1,$y1)]="L";
        $n++;
      }
      if (mt_rand(0,200)==0) # higher upper limit makes landmass more spread out
      {
        $x1=$x;
        $y1=$y;
      }
    }
  }
  # fill in any isolated inland 1x1 lakes
  for ($i=0;$i<$count;$i++)
  {
    if ($maps[$chan]["coords"][$i]=="S")
    {
      $n=0;
      for ($j=0;$j<=3;$j++)
      {
        if ($maps[$chan]["coords"][map_coord($cols,$x+$dir_x[$j],$y+$dir_y[$j])]=="L")
        {
          $n++;
        }
      }
      if ($n==4)
      {
        $maps[$chan]["coords"][$i]="L";
      }
    }
  }
}

#####################################################################################################

function map_dump($chan)
{
  global $maps;
  global $cols;
  global $rows;
  if (isset($maps[$chan]["coords"])==False)
  {
    return;
  }
  irciv__term_echo("############ BEGIN MAP DUMP ############");
  for ($i=0;$i<$rows;$i++)
  {
    irciv__term_echo(substr($maps[$chan]["coords"],$i*$cols,$cols));
  }
  irciv__term_echo("########################################");
}

#####################################################################################################

?>
