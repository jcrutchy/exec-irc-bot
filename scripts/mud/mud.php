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
if ($map_file===False)
{
  privmsg("error reading map file");
  return;
}
$map_file=explode(PHP_EOL,trim($map_file));
if (count($map_file)<>6)
{
  privmsg("invalid map file");
  return;
}
$map_data=array();
$map_data["char_path"]=substr(trim($map_file[0]),strlen("char_path="));
$map_data["char_wall"]=substr(trim($map_file[1]),strlen("char_wall="));
$map_data["char_open"]=substr(trim($map_file[2]),strlen("char_open="));
$map_data["cols"]=substr(trim($map_file[3]),strlen("cols="));
$map_data["rows"]=substr(trim($map_file[4]),strlen("rows="));
$map_data["coords"]=trim($map_file[5]);
if (strlen($map_data["coords"])<>($map_data["cols"]*$map_data["rows"]))
{
  privmsg("map coords length invalid");
  return;
}

$parts=explode(" ",$trailing);
$action=array_shift($parts);
switch ($action)
{
  case "u":
  case "up":
  
    break;
  case "d":
  case "down":
  
    break;
  case "l":
  case "left":
  
    break;
  case "r":
  case "right":
  
    break;
  case "init":
    mud_init_player($hostname,$map_data);
    break;
  case "die":
    mud_delete_player($hostname);
    break;
  case "status":
    $player=check_player($hostname);
    if ($player===False;
    {
      return;
    }
    $players=mud_query_players();
    if ($players===False)
    {
     return;
    }
    player_status($player,$players);
    break;
  case "ranks":
  
    break;
  case "player-list":
  
    break;
  case "help":
  
    break;
  case "list-gms":
  
    break;
  case "gm-view-map":
  
    break;
  case "admin-set-gm":
    if (is_admin($hostname,$server)==True)
    {
    
    }
    break;
}

#####################################################################################################

function is_admin($hostname,$server)
{
  $user="$hostname $server";
  $admins_filename=DATA_PATH."mud_admins.txt";
  if (file_exists($admins_filename)==False)
  {
    privmsg("admins file not found");
    return False;
  }
  $admin_users=file_get_contents($admins_filename);
  if ($admin_users===False)
  {
    privmsg("error reading admins file");
    return False;
  }
  $admin_users=json_decode($admin_users,True);
  if ($admin_users===Null)
  {
    privmsg("error decoding admins file");
    return False;
  }
  if (in_array($user,$admin_users)==False)
  {
    privmsg("not authorized");
    return False;
  }
  return True;
}

#####################################################################################################

function mud_query_player($hostname)
{
  $params=array("hostname"=>$hostname);
  $result=fetch_prepare("SELECT * FROM `exec_mud`.`mud_players` WHERE (`hostname`=:hostname)",$params);
  if ($result===False)
  {
    privmsg("player query error");
  }
  return $result;
}

#####################################################################################################

function mud_query_players()
{
  $result=fetch_query("SELECT * FROM `exec_mud`.`mud_players`");
  if ($result===False)
  {
    privmsg("players query error");
  }
  return $result;
}

#####################################################################################################

function check_player($hostname)
{
  $result=mud_query_player($hostname);
  if ($exist===False)
  {
    return False;
  }
  if (isset($exist["hostname"])==True)
  {
    privmsg("player already exists");
    return False;
  }
  return $result;
}

#####################################################################################################

function mud_init_player($hostname,&$map_data)
{
  $player=check_player($hostname);
  if ($player===False)
  {
    return;
  }
  $players=mud_query_players();
  if ($players===False)
  {
    return;
  }
  $items=array("hostname"=>$hostname,"x_coord"=>-1,"x_coord"=>-1,"deaths"=>0,"kills"=>0,"gm"=>0);
  mud_player_start_location($items,$players,$map_data);
  sql_insert($items,"players","exec_mud");
  privmsg("initialized player");
}

#####################################################################################################

function mud_delete_player($hostname)
{
  $items=array("hostname"=>$hostname);
  sql_delete($items,"players","exec_mud");
  privmsg("deleted player");
}

#####################################################################################################

function mud_update_player($hostname,$x,$y,$deaths,$kills,$gm=0)
{
  $value_items=array("x_coord"=>$x,"x_coord"=>$y,"deaths"=>$deahts,"kills"=>$kills,"gm"=>$gm);
  $where_items=array("hostname"=>$hostname);
  sql_update($value_items,$where_items,"players","exec_mud");
}

#####################################################################################################

function player_move($hostname,$dx,$dy,&$map_data)
{
  $player=check_player($hostname);
  if ($player===False)
  {
    return;
  }
  $players=mud_query_players();
  if ($players===False)
  {
    return;
  }
  $x=$player["x_coord"]+$dx;
  $y=$player["y_coord"]+$dy;
  if (($x>=$map_data["cols"]) or ($y>=$map_data["rows"]))
  {
    privmsg("move error: edge of map");
    return;
  }
  $c=mud_map_coord($map_data["cols"],$player["x_coord"],$player["y_coord"]);
  if ($map_data[$c]<>$map_data["char_path"])
  {
    privmsg("move error: obstacle");
    return;
  }
  $kills=$player["kills"];
  for ($i=0;$i<count($players);$i++)
  {
    if (($players[$i]["x_coord"]==$x) and ($players[$i]["y_coord"]==$y))
    {
      if ($players[$i]["hostname"]<>$hostname)
      {
        $killed=$players[$i];
        mud_player_start_location($killed,$players,$map_data);
        mud_update_player($killed["hostname"],$killed["x_coord"],$killed["y_coord"],$killed["deaths"]+1,$killed["kills"]);
        $kills++;
        $killed_nick=users_get_nick($killed["hostname"]);
        privmsg("you killed \"$killed_nick\"");
        break;
      }
    }
  }
  mud_update_player($hostname,$x,$y,$player["deaths"],$kills);
  player_status($player,$players);
}

#####################################################################################################

function ranking_sort_callback($a,$b)
{
  $a_result=$a["kills"]-$a["deaths"];
  $b_result=$b["kills"]-$b["deaths"];
  if ($a_result<>$b_result)
  {
    return ($b_result-$a_result);
  }
  else
  {
    return strcmp($a["hostname"],$b["hostname"]);
  }
}

#####################################################################################################

function update_ranking(&$player,&$players)
{
  uasort($players,"ranking_sort_callback");
  $found=False;
  $i=1;
  foreach ($players as $key => $data)
  {
    if ($data["hostname"]===$player["hostname"])
    {
      $found=True;
      break;
    }
    $i++;
  }
  if ($found==True)
  {
    return $i;
  }
  else
  {
    return -1;
  }
}

#####################################################################################################

function player_status(&$player,&$players)
{
  $i=update_ranking($player,$players);
  $nick=users_get_nick($player["hostname"]);
  if ($nick=="")
  {
    $nick=$player["hostname"];
  }
  $x=$player["x_coord"];
  $y=$player["y_coord"];
  privmsg("mud: $nick => $x,$y [rank: $i]");
}

#####################################################################################################

function mud_player_start_location(&$new_player,&$exist_players,&$map_data)
{
  $invalid=False;
  $start=microtime(True);
  do
  {
    $new_player["x_coord"]=mt_rand(0,$map_data["cols"]);
    $new_player["y_coord"]=mt_rand(0,$map_data["rows"]);
    # check for obstacle
    $c=mud_map_coord($map_data["cols"],$new_player["x_coord"],$new_player["y_coord"]);
    if ($map_data[$c]<>$map_data["char_path"])
    {
      $invalid=True;
      break;
    }
    # check if coord or neighbouring coords are occupied
    $dir_x=array(0,0,1,0,-1);
    $dir_y=array(0,-1,0,1,0);
    for ($i=0;$i<count($exist_players);$i++)
    {
      for ($j=0;$j<4;$j++)
      {
        $x=$new_player["x_coord"]+$dir_x[$j];
        $y=$new_player["y_coord"]+$dir_y[$j];
        if (($x==$exist_players[$i]["x_coord"]) and ($y==$exist_players[$i]["y_coord"]))
        {
          $invalid=True;
          break 3;
        }
      }
    }
  }
  while ((microtime(True)-$start)<20);
  if ($invalid==True)
  {
    $player_data["x_coord"]=-1;
    $player_data["y_coord"]=-1;
    privmsg("error: timed out looking for a random start location on the map");
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function mud_map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
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
