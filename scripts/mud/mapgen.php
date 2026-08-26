<?php

#####################################################################################################

$cols=500;
$rows=500;
$wall_char="X";
$open_char="O";
$path_char="P";
$filename="/home/jared/git/data/mud_map.txt";

#####################################################################################################

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
  $c=mud_map_coord($cols,$x,$y);
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
    $c=mud_map_coord($cols,$x-1,$y);
    if (($coords[$c]<>$wall_char) and ($coords[$c]<>$path_char))
    {
      $allow[3]="1";
    }
  }
  if ($x<($cols-1))
  {
    $c=mud_map_coord($cols,$x+1,$y);
    if (($coords[$c]<>$wall_char) and ($coords[$c]<>$path_char))
    {
      $allow[1]="1";
    }
  }
  if ($y>0)
  {
    $c=mud_map_coord($cols,$x,$y-1);
    if (($coords[$c]<>$wall_char) and ($coords[$c]<>$path_char))
    {
      $allow[0]="1";
    }
  }
  if ($y<($rows-1))
  {
    $c=mud_map_coord($cols,$x,$y+1);
    if (($coords[$c]<>$wall_char) and ($coords[$c]<>$path_char))
    {
      $allow[2]="1";
    }
  }
  if ($allow=="0000")
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
  do
  {
    $d=mt_rand(0,3);
  }
  while ($allow[$d]=="0");
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
      $coords[mud_map_coord($cols,$x+$dir_x[$j],$y+$dir_y[$j])]=$wall_char;
    }
  }
  $x=$x+$dir_x[$d];
  $y=$y+$dir_y[$d];
}
while (substr_count($coords,$open_char)>0);
$content="char_path=".$path_char.PHP_EOL."char_wall=".$wall_char.PHP_EOL."char_open=".$open_char.PHP_EOL."cols=".$cols.PHP_EOL."rows=".$rows.PHP_EOL.$coords;
file_put_contents($filename,$content);

#####################################################################################################

function mud_map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

?>
