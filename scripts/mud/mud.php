<?php

#####################################################################################################

/*
exec:add ~mud
exec:edit ~mud timeout 30
exec:edit ~mud cmd php scripts/mud/mud.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~mud
*/

/*
run setup.php script in mysql directory to create mysql schema
*/

#####################################################################################################

error_reporting(E_ALL);
date_default_timezone_set("UTC");

require_once(__DIR__."/../lib.php");
require_once(__DIR__."/../lib_mysql.php");

$trailing=strtolower(trim($argv[1]));
$dest=$argv[2];
$nick=$argv[3];
$user=$argv[4];
$hostname=$argv[5];
$alias=$argv[6];
$cmd=$argv[7];
$timestamp=$argv[8];
$server=$argv[9];

$map_filename=DATA_PATH."mud_map.txt";
if (file_exists($map_filename)==False)
{
  privmsg("map file \"$map_filename\" not found");
  return;
}
$map_file=file_get_contents($map_filename);
$map_file=explode(PHP_EOL,trim($map_file));
if (count($map_file)<>6)
{
  privmsg("invalid map file");
  return;
}
$map_data=array();
$map_data["char_path"]=trim($map_file[0]);
$map_data["char_wall"]=trim($map_file[1]);
$map_data["char_open"]=trim($map_file[2]);
$map_data["cols"]=trim($map_file[3]);
$map_data["rows"]=trim($map_file[4]);
$map_data["coords"]=trim($map_file[5]);
if (strlen($map_data["coords"])<>($map_data["cols"]*$map_data["rows"]))
{
  privmsg("map coords length invalid");
  return;
}

#####################################################################################################

function mud_query_player($hostname)
{
  $params=array("hostname"=>$hostname);
  return fetch_prepare("SELECT * FROM `exec_mud`.`mud_players` WHERE (`hostname`=:hostname) LIMIT 1",$params); # returns False on error
}

#####################################################################################################

function mud_query_players()
{
  return fetch_query("SELECT * FROM `exec_mud`.`mud_players`"); # returns False on error
}

#####################################################################################################

function mud_init_player($hostname,&$map_data)
{
  $exist=mud_query_player($hostname);
  if ($exist===False)
  {
    privmsg("mud_query_player error");
    return;
  }
  if (isset($exist["hostname"])==True)
  {
    privmsg("player already exists");
    return;
  }
  $exist_players=mud_query_players();
  $items=array("hostname"=>$hostname,"x_coord"=>-1,"x_coord"=>-1);
  mud_player_start_location($items,$exist_players,$map_data);
  sql_insert($items,"players","exec_mud");
}

#####################################################################################################

function mud_delete_player($hostname)
{
  $items=array("hostname"=>$hostname);
  sql_delete($items,"players","exec_mud");
}

#####################################################################################################

function mud_update_player($hostname,$x,$y)
{
  $value_items=array("x_coord"=>$x,"x_coord"=>$y);
  $where_items=array("hostname"=>$hostname);
  sql_update($value_items,$where_items,"players","exec_mud");
}

#####################################################################################################

function mud_player_start_location(&$new_player,&$exist_players,&$map_data)
{
  do
  {
    $start_x=mt_rand(0,$map_data["cols"]);
    $start_y=mt_rand(0,$map_data["rows"]);
    $invalid=False;
    for ($i=0;$i<count($exist_players);$i++)
    {
      if (($exist_players[$i]["x_coord"]==$start_x) and ($exist_players[$i]["y_coord"]==$start_y))
      {
        $invalid=True;
        break;
      }
    }
    # check for obstacle
  }
  while ($invalid==True);
  $player_data["x_coord"]=$start_x;
  $player_data["y_coord"]=$start_y;
}

#####################################################################################################

function mud_map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function mud_map_generate($cols,$rows)
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
  return $coords;
}

#####################################################################################################

function mud_map_image($coords,$cols,$rows)
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
