<?php

#####################################################################################################

require_once(__DIR__."/../lib.php");
require_once(__DIR__."/../lib_mysql.php");

#####################################################################################################

define("GAME_VERSION","0.0");

define("TIMEOUT_RANDOM_COORD",10); # sec

define("MIN_CITY_SPACING",3);

define("DATA_FILE_PATH","../data/irciv/");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

define("MAX_HEALTH",100);

define("IMAGE_TERRAIN_OCEAN","ocean.png");
define("IMAGE_TERRAIN_LAND","grassland.png");
define("IMAGE_SHIELD","shield.png");
define("IMAGE_CITY_FLAG","city_flag.png");

define("PATH_IMAGES",__DIR__."/images/");

define("GAME_BUCKET_PREFIX","IRCIV_GAME_");

#####################################################################################################

$unit_strengths=array();
# d=defense,a=attack,l=land,s=sea,a=air
# dl,ds,da,al,as,aa
$unit_strengths["settler"]="2,0,0,0,0,0";
$unit_strengths["warrior"]="1,0,0,1,0,0";

$unit_movement=array();
# land,sea,air
# 0=no,1=yes
$unit_movement["settler"]="1,0,0";
$unit_movement["warrior"]="1,0,0";

#####################################################################################################

function output_help()
{
  irciv_privmsg("QUICK START GUIDE");
  irciv_privmsg("unit movement: (left|l),(right|r),(up|u),(down|d)");
  irciv_privmsg("settler actions: (build|b)");
  irciv_privmsg("player functions: (help|?),status,init,flag/unflag,set/unset");
  irciv_privmsg("flags: public_status,grid,coords,city_names,crop_map");
  irciv_privmsg("http://sylnt.us/irciv");
}

#####################################################################################################

function get_game_list()
{
  $prefix="IRCIV_GAME_";
  $len_prefix=strlen($prefix);
  $buckets_str=bucket_list();
  $buckets_arr=explode(" ",$buckets_str);
  $game_list=array();
  for ($i=0;$i<count($buckets_arr);$i++)
  {
    if (substr($buckets_arr[$i],0,$len_prefix)==$prefix)
    {
      $channel=substr($buckets_arr[$i],$len_prefix);
      $game_list[$channel]=$buckets_arr[$i];
    }
  }
  return $game_list;
}

#####################################################################################################

function is_gm()
{
  global $account;
  global $gm_accounts;
  if (in_array($account,$gm_accounts)==True)
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function init_ai()
{
  player_init("AI_Player_1");
  #player_init("AI_Player_2");
}

#####################################################################################################

function test_ai()
{
  move_ai("AI_Player_1");
  #move_ai("AI_Player_2");
}

#####################################################################################################

function move_ai($account)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return;
  }
  $test_enemy_account="crutchy";
  $path=array();
  $start=array();
  $finish=array();
  $start["x"]=$player_data[$account]["units"][$active]["x"];
  $start["y"]=$player_data[$account]["units"][$active]["y"];
  $finish["x"]=$player_data[$test_enemy_account]["units"][$active]["x"];
  $finish["y"]=$player_data[$test_enemy_account]["units"][$active]["y"];
  find_path($path,$start,$finish);
  #move_active_unit($account,$dir)
  # log status messages to file
}

#####################################################################################################

function register_channel()
{
  global $trailing;
  global $dest;
  global $game_data;
  global $irciv_data_changed;
  irciv_term_echo("trailing = \"$trailing\"");
  $channel="";
  if ($trailing<>"")
  {
    irciv_privmsg("syntax: [~civ] register-channel");
    return;
  }
  $channel=strtolower($dest);
  if ($channel=="")
  {
    irciv_term_echo("error: channel not specified");
    return;
  }
  if (users_chan_exists($channel)==False)
  {
    irciv_privmsg("error: channel not found");
    return;
  }
  if (isset($game_data["map"])==True)
  {
    irciv_privmsg("error: existing map data found");
    return;
  }
  $map_data=generate_map_data();
  $game_data["map"]=$map_data;
  $game_data["players"]=array();
  $irciv_data_changed=True;
  $msg="map generated for channel $channel";
  if ($trailing<>"")
  {
    irciv_privmsg_dest($trailing,$msg);
  }
  if (($dest<>"") and ($dest<>$trailing))
  {
    irciv_privmsg($msg);
  }
}

#####################################################################################################

function generate_map_data()
{
  $cols=128;
  $rows=64;
  $landmass_count=50;
  $landmass_size=80;
  $land_spread=100;
  if (($landmass_count*$landmass_size)>=(0.8*$cols*$rows))
  {
    irciv_privmsg("landmass parameter error in generating map");
    return;
  }
  $coords=map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,TERRAIN_OCEAN,TERRAIN_LAND);
  $data=array();
  $data["cols"]=$cols;
  $data["rows"]=$rows;
  $data["coords"]=$coords;
  return $data;
}

#####################################################################################################

function irciv_term_echo($msg)
{
  echo "\033[36m$msg\033[0m\n";
}

#####################################################################################################

function irciv_privmsg($msg)
{
  privmsg($msg);
}

#####################################################################################################

function irciv_privmsg_dest($dest,$msg)
{
  pm($dest,$msg);
}

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function irciv_save_data()
{
  global $dest;
  $games=get_game_list();
  $n=count($games);
  if ($n==0)
  {
    if ($dest<>"")
    {
      irciv_privmsg("no games registered");
    }
    else
    {
      irciv_term_echo("no games registered");
    }
    return;
  }
  $chanlist=array();
  foreach ($games as $channel => $bucket)
  {
    $game_bucket=get_array_bucket($bucket);
    $game_bucket=json_encode($game_bucket,JSON_PRETTY_PRINT);
    $filename=DATA_FILE_PATH.$channel;
    if (file_put_contents($filename,$game_bucket)===False)
    {
      if ($dest<>"")
      {
        irciv_privmsg("error saving channel data file \"$filename\"");
      }
      else
      {
        irciv_term_echo("error saving channel data file \"$filename\"");
      }
    }
    else
    {
      $chanlist[]=$channel;
      if ($dest<>"")
      {
        irciv_privmsg("channel data file \"$filename\" saved successfully");
      }
      else
      {
        irciv_term_echo("channel data file \"$filename\" saved successfully");
      }
    }
  }
  $data=implode("\n",$chanlist);
  $filename=DATA_FILE_PATH."irciv_chan_list";
  if (file_put_contents($filename,$data)===False)
  {
    if ($dest<>"")
    {
      irciv_privmsg("error saving irciv channel list file \"$filename\"");
    }
    else
    {
      irciv_term_echo("error saving irciv channel list file \"$filename\"");
    }
  }
  else
  {
    if ($dest<>"")
    {
      irciv_privmsg("irciv channel list file \"$filename\" saved successfully");
    }
    else
    {
      irciv_term_echo("irciv channel list file \"$filename\" saved successfully");
    }
  }
}

#####################################################################################################

function irciv_load_data()
{
  $filename=DATA_FILE_PATH."irciv_chan_list";
  if (file_exists($filename)==False)
  {
    irciv_term_echo("error: channel list file not found");
    return;
  }
  $game_chans=file_get_contents($filename);
  $game_chans=explode("\n",$game_chans);
  for ($i=0;$i<count($game_chans);$i++)
  {
    $chan=trim($game_chans[$i]);
    if ($chan=="")
    {
      continue;
    }
    if (users_chan_exists($chan)==False)
    {
      irciv_privmsg("error: channel \"$chan\" not found");
      continue;
    }
    $filename=DATA_FILE_PATH.$chan;
    if (file_exists($filename)==False)
    {
      irciv_privmsg("error: file \"$filename\" not found");
      continue;
    }
    $game_bucket=file_get_contents($filename);
    $game_bucket=json_decode($game_bucket,True);
    if ($game_bucket===NULL)
    {
      irciv_privmsg("error: json_decode returned null when processing \"$filename\"");
      continue;
    }
    set_array_bucket($game_bucket,GAME_BUCKET_PREFIX.$chan,True);
    irciv_privmsg("game data for channel \"$chan\" loaded successfully");
  }
}

#####################################################################################################

function irciv_init()
{
  irciv_load_data();
}

#####################################################################################################

function map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char)
{
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = up
     1 = right
     2 = down
     3 = left */
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

function map_paint_unit(&$buffer,&$unit_buffers,&$buffer_shield,$tile_w,$tile_h,$unit_w,$unit_h,$unit,$color_str)
{
  $shield_w=imagesx($buffer_shield);
  $shield_h=imagesy($buffer_shield);
  $x=$unit["x"];
  $y=$unit["y"];
  $dx=($unit_w-$tile_w)/2;
  $dy=$unit_h-$tile_h;
  $components=explode(",",$color_str);
  $color_shield=imagecolorallocate($buffer_shield,$components[0],$components[1],$components[2]);
  imagefill($buffer_shield,round($shield_w/2),round($shield_h/2),$color_shield);
  imagecopy($buffer,$buffer_shield,round($x*$tile_w-$dx+$unit_w-$shield_w),round($y*$tile_h-$dy),0,0,$shield_w,$shield_h);
  imagecopy($buffer,$unit_buffers[$unit["type"]],round($x*$tile_w-$dx),round($y*$tile_h-$dy),0,0,$unit_w,$unit_h);
}

#####################################################################################################

function map_paint_city(&$buffer,&$city_buffers,&$buffer_city_flag,$tile_w,$tile_h,$city_w,$city_h,$city,$color_str,$show_city_names)
{
  $city_flag_w=imagesx($buffer_city_flag);
  $city_flag_h=imagesy($buffer_city_flag);
  $x=$city["x"];
  $y=$city["y"];
  $dx=($city_w-$tile_w)/2;
  $dy=($city_h-$tile_h)/2;
  imagecopy($buffer,$city_buffers[$city["size"]],round($x*$tile_w-$dx),round($y*$tile_h-$dy),0,0,$city_w,$city_h);
  $city_flag_x=round($x*$tile_w-$dx+$city_w/2-$tile_w/2+$city["size"]*$tile_w);
  $city_flag_y=round($y*$tile_h-$dy+$city_h/2-$city["size"]*$tile_h);
  $components=explode(",",$color_str);
  $r=$components[0];
  $g=$components[1];
  $b=$components[2];
  $color_city_flag=imagecolorallocate($buffer_city_flag,$r,$g,$b);
  imagefill($buffer_city_flag,round($city_flag_w/2),round($city_flag_h/3),$color_city_flag);
  imagecopy($buffer,$buffer_city_flag,$city_flag_x,$city_flag_y,0,0,$city_flag_w,$city_flag_h);
  if ($show_city_names==False)
  {
    return;
  }
  $color_text=imagecolorallocate($buffer,$r,$g,$b);
  $rs=255;
  $gs=255;
  $bs=255;
  if (($r>127) and ($g>127) and ($b>127))
  {
    $rs=0;
    $gs=0;
    $bs=0;
  }
  $color_text_shadow=imagecolorallocate($buffer,$rs,$gs,$bs);
  $font_w=imagefontwidth(5);
  $font_h=imagefontheight(5);
  $text_w=$font_w*strlen($city["name"]);
  $text_x=round($x*$tile_w-$dx+$city_w/2-$text_w/2);
  $text_y=round($y*$tile_h-$dy+$city_h/2-$tile_h/2+$city["size"]*$tile_h);
  imagefilledrectangle($buffer,$text_x,$text_y,$text_x+$text_w,$text_y+$font_h,$color_text_shadow);
  imagestring($buffer,5,$text_x+1,$text_y,$city["name"],$color_text);
}

#####################################################################################################

function map_path_img($map_data,$filename="",$player_data="",$account="",$filetype="png")
{
  if ($account=="")
  {
    return False;
  }
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];

  $buffer_terrain_ocean=imagecreatefrompng(PATH_IMAGES.IMAGE_TERRAIN_OCEAN);
  if ($buffer_terrain_ocean===False)
  {
    return False;
  }

  $tile_w=imagesx($buffer_terrain_ocean);
  $tile_h=imagesy($buffer_terrain_ocean);

  $w=$cols*$tile_w;
  $h=$rows*$tile_h;
  $buffer=imagecreatetruecolor($w,$h);

  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if (($player_data<>"") and ($account<>""))
      {
        if ($player_data[$account]["fog"][$i]=="0")
        {
          continue;
        }
      }
      if ($map_data["coords"][$i]==TERRAIN_LAND)
      {

      }
      if ($map_data["coords"][$i]==TERRAIN_OCEAN)
      {

      }
    }
  }
  imagedestroy($buffer_terrain_ocean);

  # to make final map image smaller filesize, use createimage to create palleted image, then copy truecolor image to palleted image
  $scale=1.0;
  $final_w=round($w*$scale);
  $final_h=round($h*$scale);
  $buffer_resized=imagecreatetruecolor($final_w,$final_h);
  if (imagecopyresampled($buffer_resized,$buffer,0,0,0,0,$final_w,$final_h,$w,$h)==False)
  {
    irciv_term_echo("imagecopyresampled error");
    return False;
  }
  imagedestroy($buffer);
  $buffer=imagecreate($final_w,$final_h);
  if (imagecopy($buffer,$buffer_resized,0,0,0,0,$final_w,$final_h)==False)
  {
    irciv_term_echo("imagecopy error");
    return False;
  }
  imagedestroy($buffer_resized);
  unset($buffer_resized);
  if (isset($player_data[$account]["flags"]["crop_map"])==True)
  {
    $fog_boundary_l=$cols;
    $fog_boundary_t=$rows;
    $fog_boundary_r=0;
    $fog_boundary_b=0;
    for ($y=0;$y<$rows;$y++)
    {
      for ($x=0;$x<$cols;$x++)
      {
        $coord=map_coord($cols,$x,$y);
        if ($player_data[$account]["fog"][$coord]=="1")
        {
          if ($x<$fog_boundary_l)
          {
            $fog_boundary_l=$x;
          }
          if ($x>$fog_boundary_r)
          {
            $fog_boundary_r=$x;
          }
          if ($y<$fog_boundary_t)
          {
            $fog_boundary_t=$y;
          }
          if ($y>$fog_boundary_b)
          {
            $fog_boundary_b=$y;
          }
        }
      }
    }
    $fog_boundary_l=max(0,$fog_boundary_l-1);
    $fog_boundary_t=max(0,$fog_boundary_t-1);
    $fog_boundary_r=min($cols,$fog_boundary_r+2);
    $fog_boundary_b=min($rows,$fog_boundary_b+2);
    irciv_term_echo("IRCiv >> map_img: fog_boundary_l = $fog_boundary_l");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_t = $fog_boundary_t");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_r = $fog_boundary_r");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_b = $fog_boundary_b");
    if (($fog_boundary_l<$fog_boundary_r) and ($fog_boundary_t<$fog_boundary_b))
    {
      $range_x=$fog_boundary_r-$fog_boundary_l;
      $range_y=$fog_boundary_b-$fog_boundary_t;
      irciv_term_echo("IRCiv >> map_img: range_x = $range_x");
      irciv_term_echo("IRCiv >> map_img: range_y = $range_y");
      $w=$range_x*$tile_w;
      $h=$range_y*$tile_h;
      $buffer_resized=imagecreatetruecolor($w,$h);
      if (imagecopy($buffer_resized,$buffer,0,0,$fog_boundary_l*$tile_w,$fog_boundary_t*$tile_h,$w,$h)==False)
      {
        irciv_term_echo("imagecopy error");
        return False;
      }
      imagedestroy($buffer);
      $buffer=imagecreate($w,$h);
      if (imagecopy($buffer,$buffer_resized,0,0,0,0,$w,$h)==False)
      {
        irciv_term_echo("imagecopy error");
        return False;
      }
      imagedestroy($buffer_resized);
      unset($buffer_resized);
    }
  }
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

function map_img($map_data,$filename="",$player_data="",$account="",$filetype="png")
{
  if ($account=="")
  {
    return False;
  }
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  # make some kind of image library structure (maybe a class might be useful here)
  $buffer_terrain_ocean=imagecreatefrompng(PATH_IMAGES.IMAGE_TERRAIN_OCEAN);
  if ($buffer_terrain_ocean===False)
  {
    return False;
  }
  $buffer_terrain_land=imagecreatefrompng(PATH_IMAGES.IMAGE_TERRAIN_LAND);
  if ($buffer_terrain_land===False)
  {
    return False;
  }
  $unit_types=array("settler","warrior");
  for ($i=0;$i<count($unit_types);$i++)
  {
    $unit_buffers[$unit_types[$i]]=imagecreatefrompng(PATH_IMAGES.$unit_types[$i].".png");
    if ($unit_buffers[$unit_types[$i]]===False)
    {
      return False;
    }
  }
  $city_sizes=array("1");
  for ($i=0;$i<count($city_sizes);$i++)
  {
    $city_buffers[$city_sizes[$i]]=imagecreatefrompng(PATH_IMAGES."city".$city_sizes[$i].".png");
    if ($city_buffers[$city_sizes[$i]]===False)
    {
      return False;
    }
  }
  $buffer_shield=imagecreatefrompng(PATH_IMAGES.IMAGE_SHIELD);
  if ($buffer_shield===False)
  {
    return False;
  }
  $buffer_city_flag=imagecreatefrompng(PATH_IMAGES.IMAGE_CITY_FLAG);
  if ($buffer_city_flag===False)
  {
    return False;
  }
  $tile_w=imagesx($buffer_terrain_ocean);
  $tile_h=imagesy($buffer_terrain_ocean);
  $unit_w=imagesx($unit_buffers[$unit_types[0]]);
  $unit_h=imagesy($unit_buffers[$unit_types[0]]);
  $city_w=imagesx($city_buffers[$city_sizes[0]]);
  $city_h=imagesy($city_buffers[$city_sizes[0]]);
  $w=$cols*$tile_w;
  $h=$rows*$tile_h;
  $buffer=imagecreatetruecolor($w,$h);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if (($player_data<>"") and ($account<>""))
      {
        if ($player_data[$account]["fog"][$i]=="0")
        {
          continue;
        }
      }
      if ($map_data["coords"][$i]==TERRAIN_LAND)
      {
        if (imagecopy($buffer,$buffer_terrain_land,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)==False)
        {
          return False;
        }
      }
      if ($map_data["coords"][$i]==TERRAIN_OCEAN)
      {
        if (imagecopy($buffer,$buffer_terrain_ocean,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)==False)
        {
          return False;
        }
      }
    }
  }
  #~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  $path=array();
  $start=array("x"=>14,"y"=>19);
  $finish=array("x"=>7,"y"=>52);
  find_path($path,$start,$finish);
  $color_path=imagecolorallocate($buffer,255,0,0);
  for ($i=1;$i<count($path);$i++)
  {
    $p1x=round($path[$i-1]["x"]*$tile_w+$tile_w/2);
    $p1y=round($path[$i-1]["y"]*$tile_h+$tile_h/2);
    $p2x=round($path[$i]["x"]*$tile_w+$tile_w/2);
    $p2y=round($path[$i]["y"]*$tile_h+$tile_h/2);
    imageline($buffer,$p1x,$p1y,$p2x,$p2y,$color_path);
  }
  #~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  imagedestroy($buffer_terrain_ocean);
  imagedestroy($buffer_terrain_land);
  if (($player_data<>"") and ($account<>""))
  {
    if (isset($player_data[$account]["flags"]["grid"])==True)
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
    if (isset($player_data[$account]["flags"]["coords"])==True)
    {
      $color_text=imagecolorallocate($buffer,0,0,0);
      $color_text_shadow=imagecolorallocate($buffer,255,255,255);
      for ($y=0;$y<$rows;$y++)
      {
        for ($x=0;$x<$cols;$x++)
        {
          $i=map_coord($cols,$x,$y);
          if ($player_data[$account]["fog"][$i]=="0")
          {
            continue;
          }
          imagestring($buffer,1,$x*$tile_w+1,$y*$tile_h,"$x,$y",$color_text_shadow);
          imagestring($buffer,1,$x*$tile_w+2,$y*$tile_h+1,"$x,$y",$color_text);
        }
      }
    }
    $color_transparent=imagecolorallocate($unit_buffers[$unit_types[0]],255,0,255);
    for ($i=0;$i<count($unit_types);$i++)
    {
      imagecolortransparent($unit_buffers[$unit_types[$i]],$color_transparent);
    }
    for ($i=0;$i<count($city_sizes);$i++)
    {
      imagecolortransparent($city_buffers[$city_sizes[$i]],$color_transparent);
    }
    imagecolortransparent($buffer_shield,$color_transparent);
    imagecolortransparent($buffer_city_flag,$color_transparent);
    imagealphablending($buffer,True);
    imagesavealpha($buffer,True);
    $color_str_account=$player_data[$account]["color"];
    $show_city_names=False;
    if (isset($player_data[$account]["flags"]["city_names"])==True)
    {
      $show_city_names=True;
    }
    for ($i=0;$i<count($player_data[$account]["cities"]);$i++)
    {
      $city=$player_data[$account]["cities"][$i];
      map_paint_city($buffer,$city_buffers,$buffer_city_flag,$tile_w,$tile_h,$city_w,$city_h,$city,$color_str_account,$show_city_names);
    }
    foreach ($player_data as $player => $data)
    {
      if ($player==$account)
      {
        continue;
      }
      $color_str=$player_data[$player]["color"];
      $n=count($player_data[$player]["cities"]);
      for ($i=0;$i<$n;$i++)
      {
        $city=$player_data[$player]["cities"][$i];
        $x=$city["x"];
        $y=$city["y"];
        if (is_fogged($account,$x,$y)==False)
        {
          map_paint_city($buffer,$city_buffers,$buffer_city_flag,$tile_w,$tile_h,$city_w,$city_h,$city,$color_str,$show_city_names);
        }
      }
      $n=count($player_data[$player]["units"]);
      for ($i=0;$i<$n;$i++)
      {
        $unit=$player_data[$player]["units"][$i];
        $x=$unit["x"];
        $y=$unit["y"];
        if (is_fogged($account,$x,$y)==False)
        {
          map_paint_unit($buffer,$unit_buffers,$buffer_shield,$tile_w,$tile_h,$unit_w,$unit_h,$unit,$color_str);
        }
      }
    }
    for ($i=0;$i<count($player_data[$account]["units"]);$i++)
    {
      $unit=$player_data[$account]["units"][$i];
      map_paint_unit($buffer,$unit_buffers,$buffer_shield,$tile_w,$tile_h,$unit_w,$unit_h,$unit,$color_str_account);
    }
  }
  imagedestroy($buffer_shield);
  imagedestroy($buffer_city_flag);
  for ($i=0;$i<count($unit_types);$i++)
  {
    imagedestroy($unit_buffers[$unit_types[$i]]);
  }
  for ($i=0;$i<count($city_sizes);$i++)
  {
    imagedestroy($city_buffers[$city_sizes[$i]]);
  }
  # to make final map image smaller filesize, use createimage to create palleted image, then copy truecolor image to palleted image
  $scale=1.0;
  $final_w=round($w*$scale);
  $final_h=round($h*$scale);
  $buffer_resized=imagecreatetruecolor($final_w,$final_h);
  if (imagecopyresampled($buffer_resized,$buffer,0,0,0,0,$final_w,$final_h,$w,$h)==False)
  {
    irciv_term_echo("imagecopyresampled error");
    return False;
  }
  imagedestroy($buffer);
  $buffer=imagecreate($final_w,$final_h);
  if (imagecopy($buffer,$buffer_resized,0,0,0,0,$final_w,$final_h)==False)
  {
    irciv_term_echo("imagecopy error");
    return False;
  }
  imagedestroy($buffer_resized);
  unset($buffer_resized);
  if (isset($player_data[$account]["flags"]["crop_map"])==True)
  {
    $fog_boundary_l=$cols;
    $fog_boundary_t=$rows;
    $fog_boundary_r=0;
    $fog_boundary_b=0;
    for ($y=0;$y<$rows;$y++)
    {
      for ($x=0;$x<$cols;$x++)
      {
        $coord=map_coord($cols,$x,$y);
        if ($player_data[$account]["fog"][$coord]=="1")
        {
          if ($x<$fog_boundary_l)
          {
            $fog_boundary_l=$x;
          }
          if ($x>$fog_boundary_r)
          {
            $fog_boundary_r=$x;
          }
          if ($y<$fog_boundary_t)
          {
            $fog_boundary_t=$y;
          }
          if ($y>$fog_boundary_b)
          {
            $fog_boundary_b=$y;
          }
        }
      }
    }
    $fog_boundary_l=max(0,$fog_boundary_l-1);
    $fog_boundary_t=max(0,$fog_boundary_t-1);
    $fog_boundary_r=min($cols,$fog_boundary_r+2);
    $fog_boundary_b=min($rows,$fog_boundary_b+2);
    irciv_term_echo("IRCiv >> map_img: fog_boundary_l = $fog_boundary_l");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_t = $fog_boundary_t");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_r = $fog_boundary_r");
    irciv_term_echo("IRCiv >> map_img: fog_boundary_b = $fog_boundary_b");
    if (($fog_boundary_l<$fog_boundary_r) and ($fog_boundary_t<$fog_boundary_b))
    {
      $range_x=$fog_boundary_r-$fog_boundary_l;
      $range_y=$fog_boundary_b-$fog_boundary_t;
      irciv_term_echo("IRCiv >> map_img: range_x = $range_x");
      irciv_term_echo("IRCiv >> map_img: range_y = $range_y");
      $w=$range_x*$tile_w;
      $h=$range_y*$tile_h;
      $buffer_resized=imagecreatetruecolor($w,$h);
      if (imagecopy($buffer_resized,$buffer,0,0,$fog_boundary_l*$tile_w,$fog_boundary_t*$tile_h,$w,$h)==False)
      {
        irciv_term_echo("imagecopy error");
        return False;
      }
      imagedestroy($buffer);
      $buffer=imagecreate($w,$h);
      if (imagecopy($buffer,$buffer_resized,0,0,0,0,$w,$h)==False)
      {
        irciv_term_echo("imagecopy error");
        return False;
      }
      imagedestroy($buffer_resized);
      unset($buffer_resized);
    }
  }
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

function upload_map_image($filename,$map_data,$player_data,$account)
{
  if ($account=="")
  {
    return "upload_map_image: no account";
  }
  $headers=file_get_contents(__DIR__."/irciv_map_request_headers");
  $content=file_get_contents(__DIR__."/irciv_map_request_content");
  $exec_key=file_get_contents("../pwd/exec_key");
  if (($headers===False) or ($content===False) or ($exec_key===False))
  {
    return "upload_map_image: file load error";
  }
  $uri="/";
  $host="irciv.bot.nu";
  $img_data=map_img($map_data,"",$player_data,$account,"png");
  if (($img_data===False) or ($img_data==""))
  {
    return "upload_map_image: map_img error";
  }
  do
  {
    $boundary=random_string(40);
  }
  while ((strpos($img_data,$boundary)!==False) or (strpos($exec_key,$boundary)!==False));
  $headers=str_replace("%%uri%%",$uri,$headers);
  $headers=str_replace("%%host%%",$host,$headers);
  $headers=str_replace("%%boundary%%",$boundary,$headers);
  $headers=str_replace("%%game_name%%","IRCiv",$headers);
  $headers=str_replace("%%game_version%%",GAME_VERSION,$headers);
  $content=str_replace("%%filename%%",$filename,$content);
  $content=str_replace("%%boundary%%",$boundary,$content);
  $content=str_replace("%%exec_key%%",$exec_key,$content);
  $content=str_replace("%%img_data%%",$img_data,$content);
  $content_length=strlen($content);
  $headers=str_replace("%%content_length%%",$content_length,$headers);
  $request=$headers.$content;
  $fp=fsockopen($host,80);
  if ($fp===False)
  {
    return "upload_map_image: error connecting to $host";
  }
  fwrite($fp,$request);
  $response="";
  while (!feof($fp))
  {
    $response=$response.fgets($fp,1024);
  }
  fclose($fp);
  return $response;
}

#####################################################################################################

function privmsg_player_game_chans($msg)
{
  global $game_chans;
  global $nick;
  $nick_chans=users_get_channels($nick);
  if (count($nick_chans)==0)
  {
    irciv_term_echo("privmsg_player_game_chans: nick \"$nick\" channels not set");
  }
  foreach ($game_chans as $channel => $bucket)
  {
    if (in_array($channel,$nick_chans)==True)
    {
      irciv_privmsg_dest($channel,$msg);
    }
  }
}

#####################################################################################################

function player_ready($account)
{
  global $player_data;
  global $map_data;
  if ($account=="")
  {
    irciv_privmsg("error: no account");
    return False;
  }
  if (isset($map_data["cols"])==False)
  {
    irciv_privmsg("error: map not ready");
    return False;
  }
  if (isset($player_data[$account])==False)
  {
    irciv_privmsg("player \"$account\" not found");
    return False;
  }
  return True;
}

#####################################################################################################

function get_unique_player_id()
{
  global $player_data;
  $result=1;
  if (count($player_data)>0)
  {
    foreach ($player_data as $account => $data)
    {
      if ($data["player_id"]>=$result)
      {
        $result=$data["player_id"]+1;
      }
    }
  }
  return $result;
}

#####################################################################################################

function player_init($account)
{
  global $player_data;
  global $map_data;
  if ($account=="")
  {
    irciv_privmsg("error: no account");
    return False;
  }
  if (isset($map_data["cols"])==False)
  {
    irciv_privmsg("error: map not ready");
    return False;
  }
  unset($player_data[$account]);
  $id=get_unique_player_id();
  $player_data[$account]=array();
  $player_data[$account]["init_time"]=time();
  $player_data[$account]["player_id"]=$id;
  set_player_color($account);
  $player_data[$account]["units"]=array();
  $player_data[$account]["cities"]=array();
  $player_data[$account]["flags"]["public_status"]="";
  $player_data[$account]["flags"]["grid"]="";
  $player_data[$account]["flags"]["coords"]="";
  $player_data[$account]["flags"]["city_names"]="";
  $player_data[$account]["flags"]["crop_map"]="";
  #$player_data[$account]["fog"]=str_repeat("0",strlen($map_data["coords"]));
  $player_data[$account]["fog"]=str_repeat("1",strlen($map_data["coords"]));
  $start_x=-1;
  $start_y=-1;
  if (random_coord(TERRAIN_LAND,$start_x,$start_y)==False)
  {
    return False;
  }
  add_unit($account,"settler",$start_x,$start_y);
  add_unit($account,"warrior",$start_x,$start_y);
  $player_data[$account]["active"]=-1;
  cycle_active($account);
  $player_data[$account]["start_x"]=$start_x;
  $player_data[$account]["start_y"]=$start_y;
  status($account);
  return True;
}

#####################################################################################################

function set_player_color($account,$color="")
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  $reserved_colors=array("255,0,255","0,0,0","255,255,255");
  if ($color=="")
  {
    do
    {
      $color=mt_rand(0,255).",".mt_rand(0,255).",".mt_rand(0,255);
      foreach ($player_data as $player => $data)
      {
        if (($player<>$account) and ($color==$player_data[$player]["color"]))
        {
          continue;
        }
      }
    }
    while (in_array($color,$reserved_colors)==True);
    $player_data[$account]["color"]=$color;
  }
  else
  {
    foreach ($player_data as $player => $data)
    {
      if (($player<>$account) and ($color==$player_data[$player]["color"]))
      {
        return False;
      }
    }
    if (in_array($color,$reserved_colors)==True)
    {
      return False;
    }
    $player_data[$account]["color"]=$color;
    return True;
  }
}

#####################################################################################################

function random_coord($terrain,&$x,&$y)
{
  global $map_data;
  $start=microtime(True);
  do
  {
    $x=mt_rand(0,$map_data["cols"]-1);
    $y=mt_rand(0,$map_data["rows"]-1);
    $coord=map_coord($map_data["cols"],$x,$y);
    $dt=microtime(True)-$start;
    if ($dt>TIMEOUT_RANDOM_COORD)
    {
      irciv_privmsg("error: random_coord timeout");
      return False;
    }
  }
  while ($map_data["coords"][$coord]<>$terrain);
  return True;
}

#####################################################################################################

function add_unit($account,$type,$x,$y)
{
  global $player_data;
  global $unit_strengths;
  if (player_ready($account)==False)
  {
    return False;
  }
  $units=&$player_data[$account]["units"];
  $data["type"]=$type;
  $data["health"]=MAX_HEALTH;
  $data["sight_range"]=4;
  $data["x"]=$x;
  $data["y"]=$y;
  $data["strength"]=$unit_strengths[$type];
  $units[]=$data;
  $i=count($units)-1;
  $units[$i]["index"]=$i;
  unfog($account,$x,$y,$data["sight_range"]);
  return True;
}

#####################################################################################################

function add_city($account,$x,$y,$city_name)
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return;
  }
  $cities=&$player_data[$account]["cities"];
  $data["name"]=$city_name;
  $data["population"]=1;
  $data["size"]=1;
  #$data["sight_range"]=7;
  $data["sight_range"]=100;
  $data["x"]=$x;
  $data["y"]=$y;
  $cities[]=$data;
  $i=count($cities)-1;
  $cities[$i]["index"]=$i;
  unfog($account,$x,$y,$data["sight_range"]);
}

#####################################################################################################

function unfog($account,$x,$y,$range)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  $size=2*$range+1;
  $region=imagecreate($size,$size);
  $white=imagecolorallocate($region,255,255,255);
  $black=imagecolorallocate($region,0,0,0);
  imagefill($region,0,0,$white);
  imagefilledellipse($region,$range,$range,$size,$size,$black);
  for ($j=0;$j<$size;$j++)
  {
    for ($i=0;$i<$size;$i++)
    {
      $xx=$x-$range+$i;
      $yy=$y-$range+$j;
      if (imagecolorat($region,$i,$j)==$black)
      {
        if (($xx>=0) and ($yy>=0) and ($xx<$cols) and ($yy<$rows))
        {
          $coord=map_coord($cols,$xx,$yy);
          $player_data[$account]["fog"][$coord]="1";
        }
      }
    }
  }
  imagedestroy($region);
}

#####################################################################################################

function status($account,$params="")
{
  global $player_data;
  global $map_data;
  global $dest;
  if (player_ready($account)==False)
  {
    return;
  }
  $public=False;
  if (isset($player_data[$account]["flags"]["public_status"])==True)
  {
    $public=True;
  }
  if ($params<>"")
  {
    $params=explode(" ",$params);
    if (count($params)<>2)
    {
      status_msg("syntax: [~civ] status [x y]",$public);
      return;
    }
    $x=$params[0];
    $y=$params[1];
    if ((exec_is_integer($x)==False) or (exec_is_integer($y)==False))
    {
      status_msg("error: coordinates must be two positive integers",$public);
      return;
    }
    if (($x<0) or ($x>=$map_data["cols"]) or ($y<0) or ($y>=$map_data["rows"]))
    {
      status_msg("error: coordinate $x,$y is outside the map",$public);
      return;
    }
    $terrain=$map_data["coords"][map_coord($map_data["cols"],$x,$y)];
    if (is_fogged($account,$x,$y)==True)
    {
      status_msg("coordinate $x,$y is fogged",$public);
      return;
    }
    $owner="";
    $units=array();
    foreach ($player_data as $player => $data)
    {
      for ($i=0;$i<count($player_data[$player]["units"]);$i++)
      {
        $unit=$player_data[$player]["units"][$i];
        if (($unit["x"]==$x) and ($unit["y"]==$y))
        {
          if ($owner=="")
          {
            $owner=$player;
          }
          elseif ($owner<>$player)
          {
            status_msg("error: multiple players have assets at $x,$y",$public);
            return;
          }
          $units[]=$unit;
        }
      }
    }
    $cities=array();
    foreach ($player_data as $player => $data)
    {
      for ($i=0;$i<count($player_data[$player]["cities"]);$i++)
      {
        $city=$player_data[$player]["cities"][$i];
        if (($city["x"]==$x) and ($city["y"]==$y))
        {
          if ($owner=="")
          {
            $owner=$player;
          }
          elseif ($owner<>$player)
          {
            status_msg("error: multiple players have assets at $x,$y",$public);
            return;
          }
          $cities[]=$city;
        }
      }
    }
    if ((count($cities)==0) and (count($units)==0))
    {
      status_msg("coordinate $x,$y ($terrain) is currently unoccupied",$public);
      return;
    }
    if (count($cities)>1)
    {
      status_msg("error: multiple cities at $x,$y",$public);
      return;
    }
    status_msg("status for coordinate $x,$y ($terrain): occupied by $owner",$public);
    if (count($cities)>0)
    {
      status_msg("city: ".$cities[0]["name"],$public);
    }
    status_msg("units: ".count($units),$public);
  }
  else
  {
    $i=$player_data[$account]["active"];
    $unit=$player_data[$account]["units"][$i];
    $index=$unit["index"];
    $type=$unit["type"];
    $health=$unit["health"];
    $x=$unit["x"];
    $y=$unit["y"];
    $n=count($player_data[$account]["units"]);
    if (isset($player_data[$account]["status_messages"])==True)
    {
      $unique_messages=array_count_values($player_data[$account]["status_messages"]);
      foreach ($unique_messages as $msg => $count)
      {
        if ($count>1)
        {
          $msg=$msg." (x$count)";
        }
        status_msg($dest." $account => $msg",$public);
      }
      unset($player_data[$account]["status_messages"]);
    }
    status_msg($dest." $account => $index/$n, $type, +$health, ($x,$y)",$public);
  }
}

#####################################################################################################

function status_msg($msg,$public)
{
  global $nick;
  if ($public==False)
  {
    notice($nick,$msg);
  }
  else
  {
    irciv_privmsg($msg);
  }
}

#####################################################################################################

function move_active_unit($account,$dir)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  $captions=array("up","right","down","left");
  if (isset($player_data[$account]["active"])==True)
  {
    $active=$player_data[$account]["active"];
    $old_x=$player_data[$account]["units"][$active]["x"];
    $old_y=$player_data[$account]["units"][$active]["y"];
    $x=$old_x+$dir_x[$dir];
    $y=$old_y+$dir_y[$dir];
    $caption=$captions[$dir];
    if (($x<0) or ($x>=$map_data["cols"]) or ($y<0) or ($y>=$map_data["rows"]))
    {
      $player_data[$account]["status_messages"][]="move $caption failed for active unit (already @ edge of map)";
    }
    elseif ($map_data["coords"][map_coord($map_data["cols"],$x,$y)]<>TERRAIN_LAND)
    {
      $player_data[$account]["status_messages"][]="move $caption failed for active unit (already @ edge of landmass)";
    }
    else
    {
      $forein_unit=False;
      $forein_city=False;
      $player_unit=is_foreign_unit($account,$x,$y,$forein_unit);
      $player_city=is_foreign_city($account,$x,$y,$forein_city);
      if ($player_unit!==False)
      {
        $player_data[$account]["status_messages"][]="move $caption failed for active unit (player \"$player_unit\" has occupying unit)";
        # if player is enemy, attack!
        unit_attack($account,$player_unit,$player_data[$account]["units"][$active],$forein_unit);
      }
      elseif ($player_city!==False)
      {
        $player_data[$account]["status_messages"][]="move $caption failed for active unit (player \"$player_city\" has occupying city)";
        # if player is enemy, attack!
      }
      else
      {
        $player_data[$account]["units"][$active]["x"]=$x;
        $player_data[$account]["units"][$active]["y"]=$y;
        unfog($account,$x,$y,$player_data[$account]["units"][$active]["sight_range"]);
        $type=$player_data[$account]["units"][$active]["type"];
        $player_data[$account]["status_messages"][]="successfully moved $type $caption from ($old_x,$old_y) to ($x,$y)";
        update_other_players($account,$active);
        cycle_active($account);
      }
    }
    status($account);
  }
}

#####################################################################################################

# refer to: http://www.gamasutra.com/view/feature/1535/designing_ai_algorithms_for_.php?print=1

function unit_attack($attack_account,$defend_account,&$attack_unit,&$defend_unit)
{
  global $player_data;
  $attack_unit["health"]=attacker_health($attack_unit,$defend_unit);
  irciv_term_echo("*** irciv: attacker health: ".$attack_unit["health"]);
  $defend_unit["health"]=defender_health($defend_unit,$attack_unit);
  irciv_term_echo("*** irciv: defender health: ".$defend_unit["health"]);
  if ($attack_unit["health"]==0)
  {
    # attacker died
    $player_data[$attack_account]["status_messages"][]="$defend_account's ".$defend_unit["type"]." has killed your attacking ".$attack_unit["type"]."!";
    $player_data[$defend_account]["status_messages"][]="your defending ".$defend_unit["type"]." has killed $attack_account's ".$attack_unit["type"]."!";
  }
  elseif ($defend_unit["health"]==0)
  {
    # defender died
    $player_data[$attack_account]["status_messages"][]="your attacking ".$attack_unit["type"]." has killed $defend_account's ".$defend_unit["type"]."!";
    $player_data[$defend_account]["status_messages"][]="$attack_account's ".$attack_unit["type"]." has attacked and killed your ".$defend_unit["type"]."!";
  }
  else
  {
    $player_data[$attack_account]["status_messages"][]="your ".$attack_unit["type"]." (health: ".$attack_unit["health"].") attacked $defend_account's ".$defend_unit["type"]." (health: ".$defend_unit["health"].")";
    $player_data[$defend_account]["status_messages"][]="$attack_account's ".$attack_unit["type"]." (health: ".$attack_unit["health"].") attacked your ".$defend_unit["type"]." (health: ".$defend_unit["health"].")";
  }
}

#####################################################################################################

function attacker_health($attacker,$defender)
{
  $health=$attacker["health"];
  # dl,ds,da,al,as,aa (warrior: 1,0,0,1,0,0)
  $attacker_strength=explode(",",$attacker["strength"]);
  $defender_strength=explode(",",$attacker["strength"]);
  for ($i=0;$i<3;$i++) # 0:land,1:sea,2:air
  {
    $attacker_defend=$attacker_strength[$i]*MAX_HEALTH;
    $attacker_attack=$attacker_strength[$i+3]*MAX_HEALTH;
    $defender_defend=$defender_strength[$i]*MAX_HEALTH;
    $defender_attack=$defender_strength[$i+3]*MAX_HEALTH;
    $attack_rand=mt_rand(round($defender_attack/2),$defender_attack);
    $defend_rand=mt_rand(round($attacker_defend/2),$attacker_defend);
    $delta=$defend_rand-$attack_rand;
    $health=min($health,max(0,$health-$delta));
  }
  return $health;
}

#####################################################################################################

function defender_health($defender,$attacker)
{
  return attacker_health($defender,$attacker);
}

#####################################################################################################

function is_foreign_unit($account,$x,$y,&$forein_unit)
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  foreach ($player_data as $player => $data)
  {
    if ($player<>$account)
    {
      for ($i=0;$i<count($player_data[$player]["units"]);$i++)
      {
        $unit=$player_data[$player]["units"][$i];
        if (($unit["x"]==$x) and ($unit["y"]==$y))
        {
          $forein_unit=$unit;
          return $player;
        }
      }
    }
  }
  return False;
}

#####################################################################################################

function is_foreign_city($account,$x,$y,&$forein_city)
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  foreach ($player_data as $player => $data)
  {
    if ($player<>$account)
    {
      for ($i=0;$i<count($player_data[$player]["cities"]);$i++)
      {
        $city=$player_data[$player]["cities"][$i];
        if (($city["x"]==$x) and ($city["y"]==$y))
        {
          $forein_city=$city;
          return $player;
        }
      }
    }
  }
  return False;
}

#####################################################################################################

function is_fogged($account,$x,$y)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  $cols=$map_data["cols"];
  $coord=map_coord($cols,$x,$y);
  if ($player_data[$account]["fog"][$coord]=="0")
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function update_other_players($account,$active)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return;
  }
  $x=$player_data[$account]["units"][$active]["x"];
  $y=$player_data[$account]["units"][$active]["y"];
  foreach ($player_data as $player => $data)
  {
    if ($player==$account)
    {
      continue;
    }
    if (player_ready($player)==False)
    {
      continue;
    }
    if (is_fogged($player,$x,$y)==False)
    {
      $msg="player \"$account\" moved a unit within your field of vision";
      if (in_array($msg,$player_data[$player]["status_messages"])==False)
      {
        $player_data[$player]["status_messages"][]=$msg;
      }
      $msg="you moved a unit within the field of vision of player \"$player\"";
      if (in_array($msg,$player_data[$account]["status_messages"])==False)
      {
        $player_data[$account]["status_messages"][]=$msg;
      }
    }
  }
}

#####################################################################################################

function delete_unit($account,$index)
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  if (isset($player_data[$account]["units"][$index])==False)
  {
    return False;
  }
  $count=count($player_data[$account]["units"]);
  $next=$index+1;
  for ($i=$next;$i<$count;$i++)
  {
    $player_data[$account]["units"][$i]["index"]=$i-1;
  }
  unset($player_data[$account]["units"][$index]);
  $player_data[$account]["units"]=array_values($player_data[$account]["units"]);
  return True;
}

#####################################################################################################

function build_city($account,$city_name)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  if (isset($player_data[$account]["active"])==False)
  {
    return False;
  }
  $unit=$player_data[$account]["units"][$player_data[$account]["active"]];
  if ($unit["type"]<>"settler")
  {
    $player_data[$account]["status_messages"][]="only settlers can build cities";
  }
  else
  {
    $x=$unit["x"];
    $y=$unit["y"];
    $city_exists=False;
    $city_adjacent=False;
    $cities=&$player_data[$account]["cities"];
    for ($i=0;$i<count($cities);$i++)
    {
      if ($cities[$i]["name"]==$city_name)
      {
        $city_exists=True;
        $player_data[$account]["status_messages"][]="city named \"$city_name\" already exists";
        break;
      }
      $dx=abs($cities[$i]["x"]-$x);
      $dy=abs($cities[$i]["y"]-$y);
      if (($dx<MIN_CITY_SPACING) and ($dy<MIN_CITY_SPACING))
      {
        $city_adjacent=True;
        $player_data[$account]["status_messages"][]="city \"".$cities[$i]["name"]."\" is too close";
        break;
      }
    }
    if (($city_exists==False) and ($city_adjacent==False))
    {
      add_city($account,$x,$y,$city_name);
      #delete_unit($account,$player_data[$account]["active"]); # WORKS BUT LEAVE OUT FOR TESTING
      $player_data[$account]["status_messages"][]="successfully established the new city of \"$city_name\" at coordinates ($x,$y)";
      cycle_active($account);
    }
  }
  status($account);
}

#####################################################################################################

function output_map($account)
{
  global $player_data;
  global $map_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  $game_id=sprintf("%02d",0);
  $player_id=sprintf("%02d",$player_data[$account]["player_id"]);
  $timestamp=date("YmdHis",time());
  $key=random_string(16);
  $filename=$game_id.$player_id.$timestamp.$key;
  $response=upload_map_image($filename,$map_data,$player_data,$account);
  $response_lines=explode("\n",$response);
  $msg=trim($response_lines[count($response_lines)-1]);
  if (trim($response_lines[0])=="HTTP/1.1 200 OK")
  {
    if ($msg=="SUCCESS")
    {
      $player_data[$account]["status_messages"][]="http://irciv.bot.nu/?pid=".$player_data[$account]["player_id"];
      #$player_data[$account]["status_messages"][]="http://irciv.bot.nu/?map=$filename";
    }
    else
    {
      $player_data[$account]["status_messages"][]=$msg;
    }
  }
  else
  {
    $player_data[$account]["status_messages"][]=$msg;
  }
  return True;
}

#####################################################################################################

function cycle_active($account)
{
  global $player_data;
  if (player_ready($account)==False)
  {
    return False;
  }
  output_map($account);
  $n=count($player_data[$account]["units"]);
  if (isset($player_data[$account]["active"])==False)
  {
    $player_data[$account]["active"]=0;
  }
  else
  {
    $player_data[$account]["active"]=$player_data[$account]["active"]+1;
    if ($player_data[$account]["active"]>=$n)
    {
      $player_data[$account]["active"]=0;
    }
  }
}

#####################################################################################################

function find_path(&$path,$start,$finish)
{
  global $map_data;
  # up,right,down,left,up/left,up/right,down/left,down/right
  $dir_x=array(0,1,0,-1,-1,1,-1,1);
  $dir_y=array(-1,0,1,0,-1,-1,1,1);
  $path=array();
  $locations=array();
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  if (($start["x"]<0) or ($start["x"]>=$cols) or ($finish["x"]<0) or ($finish["x"]>=$cols) or ($start["y"]<0) or ($start["y"]>=$rows) or ($finish["y"]<0) or ($finish["y"]>=$rows))
  {
    irciv_privmsg("  error: invalid start or finish coordinate(s)");
    return False;
  }
  $coord_start=map_coord($cols,$start["x"],$start["y"]);
  $coord_finish=map_coord($cols,$finish["x"],$finish["y"]);
  if ($map_data["coords"][$coord_start]<>$map_data["coords"][$coord_finish])
  {
    irciv_privmsg("  error: start and finish coordinates are on different terrain");
    return False;
  }
  # initialize the direction map with X (no direction)
  $direction_map=str_repeat("X",strlen($map_data["coords"]));
  $location_index=-1;
  $currrent_location=$start;
  do
  {
    # test for traversable locations in all directions around the current location
    for ($direction=0;$i<count($dir_x);$i++)
    {
      $x=$currrent_location["x"]+$dir_x[$direction];
      $y=$currrent_location["y"]+$dir_y[$direction];
      # if the point at ($x, $y) is traversable, add it to the locations array if it hasn't already been added, and add the direction relative to the current location to the direction map
      if (($x>=0) and ($y>=0) and ($x<$cols) and ($y<$rows))
      {
        $coord=map_coord($cols,$x,$y);
        if (($map_data["coords"][$coord_start]==$map_data["coords"][$coord]) and ($direction_map[$coord]=="X"))
        {
          $locations[]=array("x"=>$x,"y"=>$y);
          
        }
      }
    }
  }
  while (($currrent_location["x"]==$finish["x"]) and ($currrent_location["y"]==$finish["y"]));
}

  /*
  repeat
    // Test for traversable locations in all directions around the current location.
    for Direction := 0 to 7 do
    begin
      x := CurrentLocation.x + Directions[0, Direction];
      y := CurrentLocation.y + Directions[1, Direction];
      // If the point at (x, y) is traversable, add it to the locations array if it hasn't already been added, and add the direction relative to the current location to the direction map.
      if (x >= 0) and (y >= 0) and (x < MapWidth) and (y < MapHeight) then
        if (not ObstacleMap^[y, x]) and (DirectionMap[y, x] = 255) then
        begin
          Inc(LocationsCount);
          SetLength(Locations, LocationsCount);
          Locations[LocationsCount - 1] := Point(x, y);
          DirectionMap[y, x] := Direction;
        end;
    end;
    // The current location has been fully tested. Move on to the next traversable location stored in the locations array.
    Inc(LocationIndex);
    if LocationIndex >= Length(Locations) then
    begin
      SetLength(DirectionMap, 0);
      SetLength(Locations, 0);
      SetLength(InversePath, 0);
      Result := False;
      ShowMessage('LocationIndex >= Length(Locations)');
      Exit;
    end;
    CurrentLocation := Locations[LocationIndex];
    // If the current location is the same as the finish location, a path has been found (break from the searching loop).
  until (CurrentLocation.x = Finish.x) and (CurrentLocation.y = Finish.y);
  PathLength := 1;
  SetLength(InversePath, PathLength);
  InversePath[0].Location := CurrentLocation;
  InversePath[0].Direction := DirectionMap[CurrentLocation.y, CurrentLocation.x];
  // Start from the finish and work back to the start, following the inverted directions and adding locations as you go.
  repeat
    Direction := DirectionMap[CurrentLocation.y, CurrentLocation.x];
    // To invert the direction, subtract the ordinal in the directions array instead of adding it.
    CurrentLocation.x := CurrentLocation.x - Directions[0, Direction];
    CurrentLocation.y := CurrentLocation.y - Directions[1, Direction];
    Inc(PathLength);
    SetLength(InversePath, PathLength);
    InversePath[PathLength - 1].Location := CurrentLocation;
    InversePath[PathLength - 1].Direction := Direction;
    // When the start location is reached, break from the loop.
  until (CurrentLocation.x = Start.x) and (CurrentLocation.y = Start.y);
  SetLength(Path^, PathLength);
  // Copy the points from InversePath into Path in the opposite order.
  y := PathLength - 1;
  for x := 0 to PathLength - 1 do
  begin
    Path^[x] := InversePath[y];
    Dec(y);
  end;
  // Free memory from temporary arrays.
  SetLength(DirectionMap, 0);
  SetLength(Locations, 0);
  SetLength(InversePath, 0);
  // Path has been successfully found (and is stored in the array that Path points to).
  Result := True;*/

#####################################################################################################

?>
