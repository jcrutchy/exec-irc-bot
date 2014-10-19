<?php

# gpl2
# by crutchy

#####################################################################################################

require_once(__DIR__."/../lib.php");

#####################################################################################################

define("GAME_VERSION","0.0");

define("ACTION_MAP_GENERATE","map-generate");
define("ACTION_MAP_DUMP","map-dump");
define("ACTION_MAP_IMAGE","map-image");

define("ACTION_LOGIN","login");
define("ACTION_LOGOUT","logout");
define("ACTION_RENAME","rename");
define("ACTION_INIT","init");
define("ACTION_STATUS","status");
define("ACTION_SET","set");
define("ACTION_UNSET","unset");
define("ACTION_FLAG","flag");
define("ACTION_UNFLAG","unflag");

define("ACTION_PLAYER_DATA","admin-player-data");
define("ACTION_PLAYER_UNSET","admin-player-unset");
define("ACTION_PLAYER_EDIT","admin-player-edit");
define("ACTION_OBJECT_EDIT","admin-object-edit");
define("ACTION_PLAYER_LIST","admin-player-list");
define("ACTION_MOVE_UNIT","admin-move-unit");
define("ACTION_PART","admin-part");
define("ACTION_INIT_GAME","init-game");

define("ACTION_LOAD_DATA","load-data");
define("ACTION_SAVE_DATA","save-data");

define("TIMEOUT_RANDOM_COORD",10); # sec

define("MIN_CITY_SPACING",3);

define("BUCKET_USERDATA_PREFIX","irciv_user_");
define("BUCKET_PLAYERSDATA_PREFIX","irciv_players_");
define("BUCKET_MAPDATA_PREFIX","irciv_map_");

define("FILE_PLAYER_DATA","../data/irciv_player_data");
define("FILE_MAP_DATA","../data/irciv_map_data_");
define("FILE_MAP_COORDS","../data/irciv_map_coords_");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

define("IMAGE_TERRAIN_OCEAN","ocean.png");
define("IMAGE_TERRAIN_LAND","grassland.png");
define("IMAGE_SHIELD","shield.png");
define("IMAGE_CITY_FLAG","city_flag.png");

define("PATH_IMAGES",__DIR__."/images/");

#####################################################################################################

# d=defense,a=attack,l=land,s=sea,a=air
# dl,ds,da,al,as,aa
$unit_strengths["settler"]="2,0,0,0,0,0";
$unit_strengths["warrior"]="1,0,0,1,0,0";

#####################################################################################################

function irciv_term_echo($msg)
{
  term_echo("irciv: $msg");
}

#####################################################################################################

function irciv_privmsg($msg)
{
  privmsg("irciv: $msg");
}

#####################################################################################################

function irciv_privmsg_dest($dest,$msg)
{
  pm($dest,$msg);
}

#####################################################################################################

function irciv_err($msg)
{
  err("irciv error: $msg");
}

#####################################################################################################

function irciv_get_bucket($suffix)
{
  return get_bucket(BUCKET_PREFIX.$suffix);
}

#####################################################################################################

function irciv_set_bucket($suffix,$data)
{
  set_bucket(BUCKET_PREFIX.$suffix,$data);
}

#####################################################################################################

function irciv_unset_bucket($suffix)
{
  unset_bucket(BUCKET_PREFIX.$suffix);
}

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
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

function map_img($map_coords,$map_data,$filename="",$player_data="",$nick="",$filetype="png")
{
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
      if (($player_data<>"") and ($nick<>""))
      {
        if ($player_data[$nick]["fog"][$i]=="0")
        {
          continue;
        }
      }
      if ($map_coords[$i]==TERRAIN_LAND)
      {
        if (imagecopy($buffer,$buffer_terrain_land,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)==False)
        {
          return False;
        }
      }
      if ($map_coords[$i]==TERRAIN_OCEAN)
      {
        if (imagecopy($buffer,$buffer_terrain_ocean,$x*$tile_w,$y*$tile_h,0,0,$tile_w,$tile_h)==False)
        {
          return False;
        }
      }
    }
  }
  imagedestroy($buffer_terrain_ocean);
  imagedestroy($buffer_terrain_land);
  if (($player_data<>"") and ($nick<>""))
  {
    if (isset($player_data[$nick]["flags"]["grid"])==True)
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
    if (isset($player_data[$nick]["flags"]["coords"])==True)
    {
      $color_text=imagecolorallocate($buffer,0,0,0);
      $color_text_shadow=imagecolorallocate($buffer,255,255,255);
      for ($y=0;$y<$rows;$y++)
      {
        for ($x=0;$x<$cols;$x++)
        {
          $i=map_coord($cols,$x,$y);
          if ($player_data[$nick]["fog"][$i]=="0")
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
    $color_str_nick=$player_data[$nick]["color"];
    $show_city_names=False;
    if (isset($player_data[$nick]["flags"]["city_names"])==True)
    {
      $show_city_names=True;
    }
    for ($i=0;$i<count($player_data[$nick]["cities"]);$i++)
    {
      $city=$player_data[$nick]["cities"][$i];
      map_paint_city($buffer,$city_buffers,$buffer_city_flag,$tile_w,$tile_h,$city_w,$city_h,$city,$color_str_nick,$show_city_names);
    }
    foreach ($player_data as $player => $data)
    {
      if ($player==$nick)
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
        if (is_fogged($nick,$x,$y)==False)
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
        if (is_fogged($nick,$x,$y)==False)
        {
          map_paint_unit($buffer,$unit_buffers,$buffer_shield,$tile_w,$tile_h,$unit_w,$unit_h,$unit,$color_str);
        }
      }
    }
    for ($i=0;$i<count($player_data[$nick]["units"]);$i++)
    {
      $unit=$player_data[$nick]["units"][$i];
      map_paint_unit($buffer,$unit_buffers,$buffer_shield,$tile_w,$tile_h,$unit_w,$unit_h,$unit,$color_str_nick);
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

function upload_map_image($filename,$map_coords,$map_data,$players,$nick)
{
  $headers=file_get_contents(__DIR__."/irciv_map_request_headers");
  $content=file_get_contents(__DIR__."/irciv_map_request_content");
  $exec_key=file_get_contents("../pwd/exec_key");
  if (($headers===False) or ($content===False) or ($exec_key===False))
  {
    return "upload_map_image: file load error";
  }
  $uri="/";
  $host="irciv.port119.net";
  $img_data=map_img($map_coords,$map_data,"",$players,$nick,"png");
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
  $content=str_replace("%%filename%%",$filename,$content);
  $headers=str_replace("%%boundary%%",$boundary,$headers);
  $content=str_replace("%%boundary%%",$boundary,$content);
  $headers=str_replace("%%game_name%%","IRCiv",$headers);
  $headers=str_replace("%%game_version%%",GAME_VERSION,$headers);
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

function irciv_save_data()
{
  global $game_chans;
  irciv_term_echo("saving IRCiv data...");
  $players=irciv_get_bucket("players");
  if (file_put_contents(IRCIV_FILE_PLAYER_DATA,$players)===False)
  {
    irciv_term_echo("IRCiv player data not saved");
  }
  for ($i=0;$i<count($game_chans);$i++)
  {
    $map_coords=irciv_get_bucket("map_coords_".$game_chans[$i]);
    $map_data=irciv_get_bucket("map_data_".$game_chans[$i]);
    if (file_put_contents(IRCIV_FILE_MAP_COORDS.$game_chans[$i],$map_coords)===False)
    {
      irciv_term_echo("IRCiv map coords for channel \"".$game_chans[$i]."\" not saved");
    }
    if (file_put_contents(IRCIV_FILE_MAP_DATA.$game_chans[$i],$map_data)===False)
    {
      irciv_term_echo("IRCiv map data for channel \"".$game_chans[$i]."\" not saved");
    }
  }
  irciv_term_echo("IRCiv data saved");
}

#####################################################################################################

function irciv_load_data()
{
  global $game_chans;
  irciv_term_echo("loading IRCiv data...");
  if (file_exists(IRCIV_FILE_PLAYER_DATA)==True)
  {
    $players=file_get_contents(IRCIV_FILE_PLAYER_DATA);
    irciv_set_bucket("players",$players);
  }
  else
  {
    irciv_term_echo("IRCiv player data not found");
  }
  for ($i=0;$i<count($game_chans);$i++)
  {
    if (file_exists(IRCIV_FILE_MAP_COORDS.$game_chans[$i])==True)
    {
      $map_coords=file_get_contents(IRCIV_FILE_MAP_COORDS.$game_chans[$i]);
      irciv_set_bucket("map_coords_".$game_chans[$i],$map_coords);
    }
    else
    {
      irciv_term_echo("IRCiv map coords for channel \"".$game_chans[$i]."\" not found");
    }
    if (file_exists(IRCIV_FILE_MAP_DATA.$game_chans[$i])==True)
    {
      $map_data=file_get_contents(IRCIV_FILE_MAP_DATA.$game_chans[$i]);
      irciv_set_bucket("map_data_".$game_chans[$i],$map_data);
    }
    else
    {
      irciv_term_echo("IRCiv map data for channel \"".$game_chans[$i]."\" not found");
    }
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
