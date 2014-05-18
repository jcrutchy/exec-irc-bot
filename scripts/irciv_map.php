<?php

# gpl2
# by crutchy
# 5-may-2014

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

$data["cols"]=128;
$data["rows"]=64;
$coords=str_repeat(TERRAIN_OCEAN,$data["cols"]*$data["rows"]);

$parts=explode(" ",$trailing);

$cmd=$parts[0];

$map_loaded=False;

$coords_bucket=irciv_get_bucket("map_coords");
$data_bucket=irciv_get_bucket("map_data");
if (($coords_bucket<>"") and ($data_bucket<>""))
{
  $coords=map_unzip($coords_bucket);
  $data=unserialize($data_bucket);
  $map_loaded=True;
}
else
{
  irciv_term_echo("map coords and/or data bucket(s) not found");
}

switch ($cmd)
{
  case CMD_GENERATE:
    if ($map_loaded==True)
    {
      return;
    }
    $landmass_count=50;
    $landmass_size=80;
    if (($landmass_count*$landmass_size)>=(0.8*$data["cols"]*$data["rows"]))
    {
      irciv_privmsg("landmass parameter error in generating map for channel \"$dest\"");
      return;
    }
    $land_spread=100;
    $coords=map_generate($data,$landmass_count,$landmass_size,$land_spread,TERRAIN_OCEAN,TERRAIN_LAND);
    irciv_privmsg("map coords generated for channel \"$dest\"");
    break;
  case CMD_DUMP:
    map_dump($coords,$data,$dest);
    return;
  case CMD_IMAGE:
    map_img($coords,$data,$dest,"","","png");
    irciv_privmsg("saved map image file to \"$dest.png\"");
    return;
}

$coords=map_zip($coords);
$data=serialize($data);
irciv_term_echo("coords: ".round(strlen($coords)/1024,1)."kb");
irciv_set_bucket("map_coords",$coords);
irciv_set_bucket("map_data",$data);

#####################################################################################################

function map_generate($data,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char)
{
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = up
     1 = right
     2 = down
     3 = left */
  $cols=$data["cols"];
  $rows=$data["rows"];
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
    #irciv_term_echo("processed landmass $i: ".round($delta,3)." sec / $landmass_count landmasses");
    $prev=microtime(True);
  }
  irciv_term_echo("processed all landmasses");
  # fill in any isolated inland 1x1 lakes
  for ($y=0;$y<$rows;$y++)
  {
    #irciv_term_echo("1x1 lake fixer: processing row $y / $rows");
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

function map_dump($coords,$data,$filename)
{
  $cols=$data["cols"];
  $rows=$data["rows"];
  $out="";
  for ($i=0;$i<$rows;$i++)
  {
    $out=$out.substr($coords,$i*$cols,$cols)."\n";
  }
  $out=trim($out);
  if (file_put_contents($filename,$out)!==False)
  {
    irciv_privmsg("successfully saved map file to \"$filename\" (".round(strlen($coords)/1024,1)."kb)");
  }
  else
  {
    irciv_privmsg("error saving map file to \"$filename\"");
  }
}

#####################################################################################################

?>
