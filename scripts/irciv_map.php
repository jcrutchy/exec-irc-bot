<?php

# gpl2
# by crutchy
# 02-may-2014

# irciv_map.php

#####################################################################################################

ini_set("display_errors","on");
require_once("irciv_lib.php");

define("CMD_GENERATE","generate");
define("CMD_DUMP","dump");
define("CMD_IMAGE","image");

$nick=$argv[1];
$trailing=$argv[2];
$dest=$argv[3];

$admin_nicks=array("crutchy");
if (in_array($nick,$admin_nicks)==False)
{
  return;
}

$parts=explode(" ",$trailing);

$cmd=$parts[0];

$coords=irciv__get_bucket("map");
if ($coords=="")
{
  irciv__term_echo("map coords bucket contains no data");
}
else
{
  $coords=map_unzip($coords);
}

$cols=1024;
$rows=1024;

$ocean_char="O";
$land_char="L";

switch ($cmd)
{
  case CMD_GENERATE:
    $landmass_count=300;
    $landmass_size=1000;
    $land_spread=200;
    $coords=map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char);
    irciv__privmsg("map coords generated for channel \"$dest\"");
    break;
  case CMD_DUMP:
    map_dump($coords,$cols,$rows,$dest);
    return;
  case CMD_IMAGE:
    map_gif($coords,$dest,2);
    return;
}

$coords=map_zip($coords);
irciv__term_echo("coords: ".round(strlen($coords)/1024,1)."kb");
irciv__set_bucket("map",$coords);

#####################################################################################################

function map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char)
{
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = Up
     1 = Right
     2 = Down
     3 = Left */
  $count=$rows*$cols;
  $coords=str_repeat($ocean_char,$count);
  $prev=microtime(True);
  for ($i=0;$i<$landmass_count;$i++)
  {
    $n=0;
    $x=mt_rand(0,$cols-1);
    $y=mt_rand(0,$rows-1);
    $coords[map_coord($cols,$x,$y)]=$land_char;
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
      if ($coords[map_coord($cols,$x1,$y1)]<>$land_char)
      {
        $coords[map_coord($cols,$x1,$y1)]=$land_char;
        $n++;
      }
      if (mt_rand(0,$land_spread)==0) # higher upper limit makes landmass more spread out
      {
        $x1=$x;
        $y1=$y;
      }
    }
    $delta=microtime(True)-$prev;
    irciv__term_echo("processed landmass %i: ".round($delta,3)." sec / $landmass_count landmasses");
    $prev=microtime(True);
  }
  # fill in any isolated inland 1x1 lakes
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if ($coords[$i]==$ocean_char)
      {
        $n=0;
        for ($j=0;$j<=3;$j++)
        {
          $x1=$x+$dir_x[$j];
          $y1=$y+$dir_y[$j];
          if (($x1>=0) and ($y1>=0) and ($x1<$cols) and ($y1<$rows))
          {
            if ($coords[map_coord($cols,$x1,$y1)]==$land_char)
            {
              $n++;
            }
          }
        }
        if ($n==4)
        {
          $coords[$i]=$land_char;
        }
      }
    }
  }
  return $coords;
}

#####################################################################################################

function map_dump($coords,$cols,$rows,$filename)
{
  $data="";
  for ($i=0;$i<$rows;$i++)
  {
    $data=$data.substr($coords,$i*$cols,$cols)."\n";
  }
  $data=trim($data);
  if (file_put_contents($filename,$data)!==False)
  {
    irciv__privmsg("successfully saved map file to \"$filename\" (".round(strlen($coords)/1024,1)."kb)");
  }
  else
  {
    irciv__privmsg("error saving map file to \"$filename\"");
  }
}

#####################################################################################################

function map_zip($coords)
{
  # replace consecutive characters with one character followed by the number of repetitions
  # or maybe use gzcompress but escape the control characters (prolly easier)
  return $coords;
}

#####################################################################################################

function map_unzip($coords)
{
  return $coords;
}

#####################################################################################################

function map_gif($coords,$filename,$scale=2)
{
  global $cols;
  global $rows;
  global $land_char;
  #ob_clean();
  $w=$cols*$scale;
  $h=$rows*$scale;
  $buffer=imagecreatetruecolor($w,$h);
  $color_ocean=imagecolorallocate($buffer,142,163,234); # blue
  $color_land=imagecolorallocate($buffer,90,132,72); # green
  imagefill($buffer,0,0,$color_ocean);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if ($coords[$i]==$land_char)
      {
        imagefilledrectangle($buffer,$x*$scale,$y*$scale,($x+1)*$scale,($y+1)*$scale,$color_land);
      }
    }
  }
  #header('Content-Type: image/gif');
  imagegif($buffer,$filename.".gif");
  imagedestroy($buffer);
}

#####################################################################################################

?>
