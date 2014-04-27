<?php

# gpl2
# by crutchy
# 27-april-2014

# map.php

$cols=100;
$rows=100;
echo map_dump(map_generate());

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function map_generate()
{
  global $cols;
  global $rows;
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = Up
     1 = Right
     2 = Down
     3 = Left */
  $count=$rows*$cols;
  $coords=str_repeat("O",$count);
  $landmass_count=20;
  $landmass_size=150;
  for ($i=0;$i<$landmass_count;$i++)
  {
    $n=0;
    $x=mt_rand(0,$cols-1);
    $y=mt_rand(0,$rows-1);
    $coords[map_coord($cols,$x,$y)]="L";
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
      if ($coords[map_coord($cols,$x1,$y1)]<>"L")
      {
        $coords[map_coord($cols,$x1,$y1)]="L";
        $n++;
      }
      if (mt_rand(0,100)==0) # higher upper limit makes landmass more spread out
      {
        $x1=$x;
        $y1=$y;
      }
    }
  }
  # fill in any isolated inland 1x1 lakes
  for ($i=0;$i<$count;$i++)
  {
    if ($coords[$i]=="S")
    {
      $n=0;
      for ($j=0;$j<=3;$j++)
      {
        if ($coords[map_coord($cols,$x+$dir_x[$j],$y+$dir_y[$j])]=="L")
        {
          $n++;
        }
      }
      if ($n==4)
      {
        $coords[$i]="L";
      }
    }
  }
  return $coords;
}

#####################################################################################################

function map_dump($coords)
{
  global $cols;
  global $rows;
  $result="";
  for ($i=0;$i<$rows;$i++)
  {
    $result=$result.substr($coords,$i*$cols,$cols)."\n";
  }
  return $result;
}

#####################################################################################################

?>
