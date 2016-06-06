<?php

generate_maze(100,100);

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function generate_maze($cols,$rows)
{
  $wall_char="X";
  $open_char="O";
  $path_char="P";
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = up
     1 = right
     2 = down
     3 = left */
  $count=$rows*$cols;
  $coords=str_repeat($open_char,$count);
  $branch=array();
  $x=mt_rand(0,$cols-1);
  $y=mt_rand(0,$rows-1);
  do
  {
    #echo "$x,$y".PHP_EOL;
    $c=map_coord($cols,$x,$y);
    if (($coords[$c]==$wall_char) or ($coords[$c]==$path_char))
    {
      if (count($branch)>0)
      {
        $c=array_shift($branch);
        $x=$c["x"];
        $y=$c["y"];
        continue;
      }
      else
      {
        break;
      }
    }
    $coords[$c]=$path_char;
    $allow="0000";
    if ($x>0)
    {
      if (($coords[map_coord($cols,$x-1,$y)]<>$wall_char) and ($coords[map_coord($cols,$x-1,$y)]<>$path_char))
      {
        $allow[3]="1";
      }
    }
    if ($x<($cols-1))
    {
      if (($coords[map_coord($cols,$x+1,$y)]<>$wall_char) and ($coords[map_coord($cols,$x+1,$y)]<>$path_char))
      {
        $allow[1]="1";
      }
    }
    if ($y>0)
    {
      if (($coords[map_coord($cols,$x,$y-1)]<>$wall_char) and ($coords[map_coord($cols,$x,$y-1)]<>$path_char))
      {
        $allow[0]="1";
      }
    }
    if ($y<($rows-1))
    {
      if (($coords[map_coord($cols,$x,$y+1)]<>$wall_char) and ($coords[map_coord($cols,$x,$y+1)]<>$path_char))
      {
        $allow[2]="1";
      }
    }
    #echo $allow.PHP_EOL;
    if ($allow=="0000")
    {
      $x=mt_rand(0,$cols-1);
      $y=mt_rand(0,$rows-1);
      continue;
    }
    do
    {
      $d=mt_rand(0,3);
    }
    while ($allow[$d]=="0");
    #echo $d.PHP_EOL;
    for ($j=0;$j<=3;$j++)
    {
      if ($j==$d)
      {
        continue;
      }
      if ($allow[$j]=="0")
      {
        continue;
      }
      if (mt_rand(1,4)==1)
      {
        $branch[]=array("x"=>$x+$dir_x[$j],"y"=>$y+$dir_y[$j]);
      }
      else
      {
        $coords[map_coord($cols,$x+$dir_x[$j],$y+$dir_y[$j])]=$wall_char;
      }
    }
    $x=$x+$dir_x[$d];
    $y=$y+$dir_y[$d];
  }
  while (substr_count($coords,$open_char)>0);
  for ($i=0;$i<$rows;$i++)
  {
    echo substr($coords,$i*$cols,$cols).PHP_EOL;
  }
  return $coords;
}

#####################################################################################################

?>
