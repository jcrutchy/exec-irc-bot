<?php

$coords=generate_maze(100,100);
if (map_img($coords,100,100)===False)
{
  echo "map error".PHP_EOL;
}
else
{
  echo "map success".PHP_EOL;
}

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

function map_img($coords,$cols,$rows)
{
  $wall_char="X";
  $open_char="O";
  $path_char="P";
  $filename="testmaze";
  $filetype="png";
  $path_images="./";
  $image_open="open.png";
  $image_path="path.png";
  $image_wall="wall.png";
  $buffer_open=imagecreatefrompng($path_images.$image_open);
  if ($buffer_open===False)
  {
    return False;
  }
  $buffer_path=imagecreatefrompng($path_images.$image_path);
  if ($buffer_path===False)
  {
    return False;
  }
  $buffer_wall=imagecreatefrompng($path_images.$image_wall);
  if ($buffer_wall===False)
  {
    return False;
  }
  $tile_w=imagesx($buffer_open);
  $tile_h=imagesy($buffer_open);
  $w=$cols*$tile_w;
  $h=$rows*$tile_h;
  $buffer=imagecreatetruecolor($w,$h);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if ($coords[$i]==$open_char)
      {
        if (imagecopy($buffer,$buffer_open,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)===False)
        {
          return False;
        }
      }
      if ($coords[$i]==$path_char)
      {
        if (imagecopy($buffer,$buffer_path,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)===False)
        {
          return False;
        }
      }
      if ($coords[$i]==$wall_char)
      {
        if (imagecopy($buffer,$buffer_wall,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)===False)
        {
          return False;
        }
      }
    }
  }
  imagedestroy($buffer_open);
  imagedestroy($buffer_path);
  imagedestroy($buffer_wall);
  # to make final map image smaller filesize, use createimage to create palleted image, then copy truecolor image to palleted image
  $scale=1.0;
  $final_w=round($w*$scale);
  $final_h=round($h*$scale);
  $buffer_resized=imagecreatetruecolor($final_w,$final_h);
  if (imagecopyresampled($buffer_resized,$buffer,0,0,0,0,$final_w,$final_h,$w,$h)==False)
  {
    echo "imagecopyresampled error".PHP_EOL;
    return False;
  }
  imagedestroy($buffer);
  $buffer=imagecreate($final_w,$final_h);
  if (imagecopy($buffer,$buffer_resized,0,0,0,0,$final_w,$final_h)==False)
  {
    echo "imagecopy error".PHP_EOL;
    return False;
  }
  imagedestroy($buffer_resized);
  unset($buffer_resized);
  if ($filename<>"")
  {
    switch ($filetype)
    {
      case "gif":
        imagegif($buffer,$filename.".gif");
        break;
      case "png":
        imagepng($buffer,$filename.".png");
        break;
      case "jpg":
        imagejpg($buffer,$filename.".jpg");
        break;
    }
    imagedestroy($buffer);
  }
  else
  {
    ob_start();
    switch ($filetype)
    {
      case "gif":
        imagegif($buffer);
        break;
      case "png":
        imagepng($buffer);
        break;
      case "jpg":
        imagejpg($buffer);
        break;
    }
    $data=ob_get_contents();
    ob_end_clean();
    imagedestroy($buffer);
    return $data;
  }
}

#####################################################################################################

?>
