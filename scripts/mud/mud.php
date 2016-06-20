<?php

#####################################################################################################

/*
exec:add ~mud
exec:edit ~mud timeout 240
exec:edit ~mud cmd php scripts/mud/mud.php %%trailing%% %%dest%% %%nick%% %%user%% %%hostname%% %%alias%% %%cmd%% %%timestamp%% %%server%%
exec:enable ~mud
*/

#####################################################################################################

/*
run setup.php script in mysql directory to create mysql schema
*/

#####################################################################################################

/****************************************************************************************************

    DEVELOPMENT TO-DO
    =================

    - make so that players can leave waypoints on their maps and auto-move to them if they get lost
    - include wormholes where players can move between points on the map quickly
    - include a boundary fence that limits the playing area of the map, which can be varied on the fly based on the number of players

****************************************************************************************************/

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

$dir_x=array(0,1,0,-1);
$dir_y=array(-1,0,1,0);
$dir_names=array("up","right","down","left");
/* 0 = up
   1 = right
   2 = down
   3 = left */

$parts=explode(" ",$trailing);
$action=array_shift($parts);
$trailing=implode(" ",$parts);
switch ($action)
{
  case "u":
  case "up":
    player_move($hostname,$dir_x[0],$dir_y[0],$map_data,$trailing);
    break;
  case "r":
  case "right":
    player_move($hostname,$dir_x[1],$dir_y[1],$map_data,$trailing);
    break;
  case "d":
  case "down":
    player_move($hostname,$dir_x[2],$dir_y[2],$map_data,$trailing);
    break;
  case "l":
  case "left":
    player_move($hostname,$dir_x[3],$dir_y[3],$map_data,$trailing);
    break;
  case "init":
    mud_init_player($hostname,$map_data);
    break;
  case "die":
    mud_delete_player($hostname);
    break;
  case "status":
    player_status($hostname);
    break;
  case "map":
    player_map($hostname);
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
    if (is_gm($hostname,$server)==True)
    {
      $data=mud_map_image($map_data["coords"],$map_data["cols"],$map_data["rows"]);
      if ($data===False)
      {
        return;
      }
      $result=upload_to_imgur($data);
      if ($result===False)
      {
        return;
      }
      privmsg($result);
    }
    break;
  case "admin-set-gm":
    if (is_admin($hostname,$server)==True)
    {
      $player=check_player($trailing);
      if ($player===False)
      {
        return;
      }
      mud_update_player($player["hostname"],$player["x_coord"],$player["y_coord"],$player["deaths"],$player["kills"],$player["map"],1);
    }
    break;
  case "admin-unset-gm":
    if (is_admin($hostname,$server)==True)
    {
      $player=check_player($trailing);
      if ($player===False)
      {
        return;
      }
      mud_update_player($player["hostname"],$player["x_coord"],$player["y_coord"],$player["deaths"],$player["kills"],$player["map"],0);
    }
    break;
  case "admin-test-ai":
    if (is_admin($hostname,$server)==True)
    {
      $ai_hostname="mud_ai";
      mud_init_player($ai_hostname,$map_data);
      
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
      $player_map=gzuncompress($player["map"]);
      move_ai($player,$players,$ai_hostname);
      
      $data=mud_map_image($map_data["coords"],$map_data["cols"],$map_data["rows"],$player);
      if ($data===False)
      {
        return;
      }
      $result=upload_to_imgur($data);
      if ($result===False)
      {
        return;
      }
      privmsg($result);
    }
    break;
}

#####################################################################################################

function is_admin($hostname,$server)
{
  $bot_operator=get_bucket("<<OPERATOR_HOSTNAME>>");
  if ($hostname===$bot_operator)
  {
    return True;
  }
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

function is_gm($hostname,$server)
{
  if (is_admin($hostname,$server)==True)
  {
    return True;
  }
  $player=check_player($hostname);
  if ($player===False)
  {
    return False;
  }
  if ($player["gm"]!==1)
  {
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
    privmsg("mysql query error (player)");
  }
  return $result;
}

#####################################################################################################

function mud_query_players()
{
  $result=fetch_query("SELECT * FROM `exec_mud`.`mud_players`");
  if ($result===False)
  {
    privmsg("mysql query error (players)");
  }
  return $result;
}

#####################################################################################################

function check_player($hostname)
{
  $result=mud_query_player($hostname);
  if ($result===False)
  {
    return False;
  }
  if (count($result)<>1)
  {
    privmsg("multiple records for player");
    return False;
  }
  if (isset($result[0]["hostname"])==False)
  {
    privmsg("player not found");
    return False;
  }
  return $result[0];
}

#####################################################################################################

function mud_init_player($hostname,&$map_data)
{
  $player=mud_query_player($hostname);
  if ($player===False)
  {
    return;
  }
  if (count($player)<>0)
  {
    privmsg("player already exists");
    return;
  }
  $players=mud_query_players();
  if ($players===False)
  {
    return;
  }
  $map=str_repeat(" ",$map_data["rows"]*$map_data["cols"]);
  $items=array("hostname"=>$hostname,"x_coord"=>-1,"x_coord"=>-1,"deaths"=>0,"kills"=>0,"map"=>gzcompress($map),"gm"=>0);
  if (mud_player_start_location($items,$players,$map_data)==False)
  {
    return;
  }
  if (sql_insert($items,"mud_players","exec_mud")==True)
  {
    privmsg("initialized player");
  }
  else
  {
    privmsg("mysql insert error");
  }
}

#####################################################################################################

function mud_delete_player($hostname)
{
  $items=array("hostname"=>$hostname);
  if (sql_delete($items,"mud_players","exec_mud")==True)
  {
    privmsg("deleted player");
  }
  else
  {
    privmsg("mysql delete error");
  }
}

#####################################################################################################

function mud_update_player($hostname,$x,$y,$deaths,$kills,$map,$gm=0)
{
  $value_items=array("x_coord"=>$x,"y_coord"=>$y,"deaths"=>$deaths,"kills"=>$kills,"map"=>$map,"gm"=>$gm);
  $where_items=array("hostname"=>$hostname);
  if (sql_update($value_items,$where_items,"mud_players","exec_mud")==False)
  {
    privmsg("mysql update error");
  }
}

#####################################################################################################

function player_move($hostname,$dx,$dy,&$map_data,$factor="")
{
  if (strlen($factor)>0)
  {
    if (is_valid_chars($factor,VALID_NUMERIC)==False)
    {
      $factor=1;
    }
    elseif ($factor==0)
    {
      $factor=1;
    }
  }
  else
  {
    $factor=1;
  }
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
  $x=$player["x_coord"]+$dx*$factor;
  $y=$player["y_coord"]+$dy*$factor;
  if (($x<0) or ($y<0) or ($x>=$map_data["cols"]) or ($y>=$map_data["rows"]))
  {
    privmsg("move error: edge of map");
    return;
  }
  $c=mud_map_coord($map_data["cols"],$x,$y);
  if ($map_data["coords"][$c]<>$map_data["char_path"])
  {
    privmsg("move error: obstacle");
    return;
  }
  $map=gzuncompress($player["map"]);
  $map[$c]=$map_data["coords"][$c];
  $kills=$player["kills"];
  for ($i=0;$i<count($players);$i++)
  {
    if (($players[$i]["x_coord"]==$x) and ($players[$i]["y_coord"]==$y))
    {
      if ($players[$i]["hostname"]<>$hostname)
      {
        $killed=$players[$i];
        mud_player_start_location($killed,$players,$map_data);
        mud_update_player($killed["hostname"],$killed["x_coord"],$killed["y_coord"],$killed["deaths"]+1,$killed["kills"],$killed["map"]);
        $kills++;
        $killed_nick=users_get_nick($killed["hostname"]);
        privmsg("you killed \"$killed_nick\"");
        break;
      }
    }
  }
  mud_update_player($hostname,$x,$y,$player["deaths"],$kills,gzcompress($map));
  player_status($hostname);
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

function player_status($hostname)
{
  global $dir_x;
  global $dir_y;
  global $dir_names;
  global $map_data;
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
  $i=update_ranking($player,$players);
  $nick=users_get_nick($player["hostname"]);
  if ($nick=="")
  {
    $nick=$player["hostname"];
  }
  $x=$player["x_coord"];
  $y=$player["y_coord"];
  privmsg("mud: $nick => $x,$y [rank: $i]");
  $open_dirs=array();
  for ($i=0;$i<count($dir_names);$i++)
  {
    $ix=$x+$dir_x[$i];
    $iy=$y+$dir_y[$i];
    if (($ix<0) or ($iy<0) or ($ix>=$map_data["cols"]) or ($iy>=$map_data["rows"]))
    {
      continue;
    }
    $c=mud_map_coord($map_data["cols"],$ix,$iy);
    if ($map_data["coords"][$c]<>$map_data["char_path"])
    {
      continue;
    }
    $open_dirs[]=$dir_names[$i];
  }
  $open_dirs=implode(", ",$open_dirs);
  privmsg("mud: $nick => open directions: ".$open_dirs);
}

#####################################################################################################

function player_map($hostname)
{
  global $map_data;
  $player=check_player($hostname);
  if ($player===False)
  {
    return;
  }
  $nick=users_get_nick($player["hostname"]);
  if ($nick=="")
  {
    $nick=$player["hostname"];
  }
  $data=mud_map_image($map_data["coords"],$map_data["cols"],$map_data["rows"],$player);
  if ($data===False)
  {
    return;
  }
  $result=upload_to_imgur($data);
  if ($result===False)
  {
    return;
  }
  privmsg("mud: $nick => ".$result);
}

#####################################################################################################

function mud_player_start_location(&$new_player,&$exist_players,&$map_data)
{
  $start=microtime(True);
  do
  {
    $invalid=False;
    $new_player["x_coord"]=mt_rand(0,$map_data["cols"]-1);
    $new_player["y_coord"]=mt_rand(0,$map_data["rows"]-1);
    # check for obstacle
    $c=mud_map_coord($map_data["cols"],$new_player["x_coord"],$new_player["y_coord"]);
    if ($map_data["coords"][$c]<>$map_data["char_path"])
    {
      $invalid=True;
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
          break 2;
        }
      }
    }
  }
  while (((microtime(True)-$start)<20) and ($invalid==True));
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

function mud_map_image($coords,$cols,$rows,$player=False)
{
  $map=False;
  if ($player!==False)
  {
    $map=gzuncompress($player["map"]);
  }
  $wall_char="X";
  $open_char="O";
  $path_char="P";
  $filetype="png";
  $tile_w=15;
  $tile_h=15;
  $w=$cols*$tile_w;
  $h=$rows*$tile_h;
  $buffer=imagecreatetruecolor($w,$h);
  $color_open=imagecolorallocate($buffer,10,10,10);
  $color_path=imagecolorallocate($buffer,255,148,77);
  $color_wall=imagecolorallocate($buffer,128,0,0);
  $color_visited=imagecolorallocate($buffer,0,153,0);
  $color_current=imagecolorallocate($buffer,0,51,0);
  $color_fog=imagecolorallocate($buffer,100,100,100);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=mud_map_coord($cols,$x,$y);
      if (($map===False) or (isset($player["path"])==True))
      {
        if ($coords[$i]==$open_char)
        {
          if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_open)==False)
          {
            return False;
          }
        }
        if ($coords[$i]==$path_char)
        {
          if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_path)==False)
          {
            return False;
          }
        }
        if ($coords[$i]==$wall_char)
        {
          if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_wall)==False)
          {
            return False;
          }
        }
      }
      else
      {
        $current_coord=mud_map_coord($cols,$player["x_coord"],$player["y_coord"]);
        $visible=False;
        if ($map[$i]<>" ")
        {
          $visible=True;
        }
        $dir_x=array(0,1,0,-1,-1,1,-1,1);
        $dir_y=array(-1,0,1,0,-1,1,1,-1);
        for ($j=0;$j<count($dir_x);$j++)
        {
          $dx=$x+$dir_x[$j];
          $dy=$y+$dir_y[$j];
          if (($dx<0) or ($dy<0) or ($dx>=$cols) or ($dy>=$rows))
          {
            continue;
          }
          $k=mud_map_coord($cols,$dx,$dy);
          if ($map[$k]<>" ")
          {
            $visible=True;
          }
        }
        if ($visible==False)
        {
          if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_fog)==False)
          {
            return False;
          }
        }
        else
        {
          if ($coords[$i]==$open_char)
          {
            if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_open)==False)
            {
              return False;
            }
          }
          if ($coords[$i]==$path_char)
          {
            if ($map[$i]==" ")
            {
              if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_path)==False)
              {
                return False;
              }
            }
            else
            {
              if ($current_coord==$i)
              {
                if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_current)==False)
                {
                  return False;
                }
              }
              else
              {
                if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_visited)==False)
                {
                  return False;
                }
              }
            }
          }
          if ($coords[$i]==$wall_char)
          {
            if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_wall)==False)
            {
              return False;
            }
          }
        }
      }
    }
  }
  $grid=True; # make into user setting
  $coords=True; # make into user setting
  if ($grid==True)
  {
    $color_grid=imagecolorallocate($buffer,0,0,0);
    for ($x=0;$x<$cols;$x++)
    {
      imageline($buffer,$x*$tile_w,0,$x*$tile_w,$h,$color_grid);
    }
    for ($y=0;$y<$rows;$y++)
    {
      imageline($buffer,0,$y*$tile_h,$w,$y*$tile_h,$color_grid);
    }
  }
  /*if ($coords==True)
  {
    $color_text=imagecolorallocate($buffer,0,0,0);
    #$color_text_shadow=imagecolorallocate($buffer,255,255,255);
    for ($y=0;$y<$rows;$y++)
    {
      for ($x=0;$x<$cols;$x++)
      {
        $i=mud_map_coord($cols,$x,$y);
        #if ($player_data[$account]["fog"][$i]=="0")
        #{
          #continue;
        #}
        #imagestring($buffer,1,$x*$tile_w+1,$y*$tile_h,"$x,$y",$color_text_shadow);
        imagestring($buffer,1,$x*$tile_w+2,$y*$tile_h+1,"$x,$y",$color_text);
      }
    }
  }*/
  if (isset($player["path"])==True)
  {
    $path=$player["path"];
    $color_path=imagecolorallocate($buffer,200,0,200);
    $color_path_line=imagecolorallocate($buffer,255,0,0);
    for ($i=1;$i<count($path);$i++)
    {
      $x=$path[$i]["x"];
      $y=$path[$i]["y"];
      if (imagefilledrectangle($buffer,$x*$tile_w,$y*$tile_h,($x+1)*$tile_w,($y+1)*$tile_h,$color_path)==False)
      {
        return False;
      }
      $p1x=round($path[$i-1]["x"]*$tile_w+$tile_w/2);
      $p1y=round($path[$i-1]["y"]*$tile_h+$tile_h/2);
      $p2x=round($x*$tile_w+$tile_w/2);
      $p2y=round($path[$i]["y"]*$tile_h+$tile_h/2);
      imageline($buffer,$p1x,$p1y,$p2x,$p2y,$color_path_line);
    }
  }
  $crop=True;
  if (($crop==True) and ($map!==False) and (isset($player["path"])==False))
  {
    $boundary_l=$cols;
    $boundary_t=$rows;
    $boundary_r=0;
    $boundary_b=0;
    for ($y=0;$y<$rows;$y++)
    {
      for ($x=0;$x<$cols;$x++)
      {
        $coord=mud_map_coord($cols,$x,$y);
        if ($map[$coord]<>" ")
        {
          if ($x<$boundary_l)
          {
            $boundary_l=$x;
          }
          if ($x>$boundary_r)
          {
            $boundary_r=$x;
          }
          if ($y<$boundary_t)
          {
            $boundary_t=$y;
          }
          if ($y>$boundary_b)
          {
            $boundary_b=$y;
          }
        }
      }
    }
    $boundary_l=max(0,$boundary_l-1);
    $boundary_t=max(0,$boundary_t-1);
    $boundary_r=min($cols,$boundary_r+2);
    $boundary_b=min($rows,$boundary_b+2);
    if (($boundary_l<$boundary_r) and ($boundary_t<$boundary_b))
    {
      $range_x=$boundary_r-$boundary_l;
      $range_y=$boundary_b-$boundary_t;
      $w=$range_x*$tile_w+1; # the +1 only applies if grid is enabled
      $h=$range_y*$tile_h+1; # the +1 only applies if grid is enabled
      $buffer_resized=imagecreatetruecolor($w,$h);
      if (imagecopy($buffer_resized,$buffer,0,0,$boundary_l*$tile_w,$boundary_t*$tile_h,$w,$h)==False)
      {
        privmsg("map cropping imagecopy error (1)");
        return False;
      }
      imagedestroy($buffer);
      $buffer=imagecreate($w,$h);
      if (imagecopy($buffer,$buffer_resized,0,0,0,0,$w,$h)==False)
      {
        privmsg("map cropping imagecopy error (2)");
        return False;
      }
      imagedestroy($buffer_resized);
      unset($buffer_resized);
    }
    else
    {
      #privmsg("map boundary error");
      return False;
    }
  }
  
  # to make final map image smaller filesize, use createimage to create palleted image, then copy truecolor image to palleted image
  /*$scale=1.0;
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
  unset($buffer_resized);*/
  ob_start();
  imagepng($buffer);
  $data=ob_get_contents();
  ob_end_clean();
  imagedestroy($buffer);
  return $data;
}

#####################################################################################################

function find_path(&$path,$start,$finish)
{
  global $dir_x;
  global $dir_y;
  global $map_data;
  $path=array();
  $locations=array();
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  if (($start["x"]<0) or ($start["x"]>=$cols) or ($finish["x"]<0) or ($finish["x"]>=$cols) or ($start["y"]<0) or ($start["y"]>=$rows) or ($finish["y"]<0) or ($finish["y"]>=$rows))
  {
    # invalid start or finish coordinate(s)
    return False;
  }
  $coord_start=mud_map_coord($cols,$start["x"],$start["y"]);
  $coord_finish=mud_map_coord($cols,$finish["x"],$finish["y"]);
  if ($map_data["coords"][$coord_start]<>$map_data["coords"][$coord_finish])
  {
    # start and finish coordinates are on different terrain
    return False;
  }
  # initialize the direction map with X (no direction)
  $direction_map=str_repeat("X",strlen($map_data["coords"]));
  $location_index=-1;
  $currrent_location=$start;
  do
  {
    # test for traversable locations in all directions around the current location
    for ($direction=0;$direction<count($dir_x);$direction++)
    {
      $x=$currrent_location["x"]+$dir_x[$direction];
      $y=$currrent_location["y"]+$dir_y[$direction];
      # if the point at ($x, $y) is traversable, add it to the locations array if it hasn't already been added, and add the direction relative to the current location to the direction map
      if (($x>=0) and ($y>=0) and ($x<$cols) and ($y<$rows))
      {
        $coord=mud_map_coord($cols,$x,$y);
        if (($map_data["coords"][$coord_start]==$map_data["coords"][$coord]) and ($direction_map[$coord]=="X"))
        {
          $locations[]=array("x"=>$x,"y"=>$y);
          $direction_map[$coord]=$direction;
        }
      }
    }
    # the current location has been fully tested. move on to the next traversable location stored in the locations array
    $location_index++;
    if ($location_index>=count($locations))
    {
      # run out of locations to test and finish hasn't been found
      return False;
    }
    $currrent_location=$locations[$location_index];
  }
  # if the current location is the same as the finish location, a path has been found (break from the searching loop)
  while (($currrent_location["x"]<>$finish["x"]) or ($currrent_location["y"]<>$finish["y"]));
  $inverse_path=array();
  $direction=$direction_map[mud_map_coord($cols,$currrent_location["x"],$currrent_location["y"])];
  $inverse_path[]=array("x"=>$currrent_location["x"],"y"=>$currrent_location["y"],"dir"=>$direction);
  # start from the finish and work back to the start, following the inverted directions and adding locations as you go
  do
  {
    # to invert the direction, subtract the ordinal in the directions array instead of adding it
    $currrent_location["x"]=$currrent_location["x"]-$dir_x[$direction];
    $currrent_location["y"]=$currrent_location["y"]-$dir_y[$direction];
    $direction=$direction_map[mud_map_coord($cols,$currrent_location["x"],$currrent_location["y"])];
    $inverse_path[]=array("x"=>$currrent_location["x"],"y"=>$currrent_location["y"],"dir"=>$direction);
  }
  # when the start location is reached, break from the loop
  while (($currrent_location["x"]<>$start["x"]) or ($currrent_location["y"]<>$start["y"]));
  for ($i=count($inverse_path)-1;$i>=0;$i--)
  {
    $path[]=$inverse_path[$i];
  }
  return True;
}

#####################################################################################################

function move_ai(&$player,&$players,$hostname)
{
  global $map_data;
  $start=array();
  $start["x"]=$player["x_coord"];
  $start["y"]=$player["y_coord"];
  $paths=array();
  foreach ($players as $record_index=>$target_data)
  {
    if ($target_data["hostname"]==$hostname)
    {
      continue;
    }
    $path=array();
    $finish=array();
    $finish["x"]=$target_data["x_coord"];
    $finish["y"]=$target_data["y_coord"];
    if (find_path($path,$start,$finish)==False)
    {
      privmsg("no path exists between $hostname and ".$target_data["hostname"]);
      return;
    }
    if (count($path)<=1)
    {
      privmsg("no path exists between $hostname and ".$target_data["hostname"]);
      return;
    }
    $paths[]=$path;
  }
  $min_path_length=$map_data["cols"]*$map_data["rows"];
  $min_path=-1;
  for ($i=0;$i<count($paths);$i++)
  {
    if (count($paths[$i])<$min_path_length)
    {
      $min_path=$i;
      $min_path_length=count($paths[$i]);
    }
  }
  if ($min_path<0)
  {
    privmsg("minimum path not found for ".$hostname);
    return;
  }
  $player["path"]=$paths[$min_path];
}

#####################################################################################################

?>
