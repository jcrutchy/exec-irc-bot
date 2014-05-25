<?php

# gpl2
# by crutchy
# 24-may-2014

# irciv_lib.php

#####################################################################################################

require_once("lib.php");

define("GAME_VERSION","0.0");
define("GAME_CHAN","#civ");
define("BUCKET_PREFIX","IRCiv_".GAME_CHAN."_");

define("IRCIV_DATA_FILE","../data/irciv_data");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

define("IMAGE_TERRAIN_OCEAN","ocean.png");
define("IMAGE_TERRAIN_LAND","grassland.png");
define("IMAGE_SHIELD","shield.png");
define("IMAGE_CITY_FLAG","city_flag.png");

define("PATH_IMAGES",__DIR__."/images/");

#####################################################################################################

function irciv_term_echo($msg)
{
  term_echo("IRCiv: $msg");
}

#####################################################################################################

function irciv_privmsg($msg)
{
  privmsg("IRCiv: $msg");
}

#####################################################################################################

function irciv_err($msg)
{
  err("IRCiv error: $msg");
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

function map_zip($coords)
{
  # replace consecutive characters with one character followed by the number of repetitions
  # or maybe use gzcompress but escape the control characters (prolly easier)
  return $coords;
}

#####################################################################################################

function map_unzip($coords)
{
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
      if (($player==$nick) or ($player==NICK_EXEC))
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

function random_string($length)
{
  $legal="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  $result="";
  for ($i=0;$i<$length;$i++)
  {
    $result=$result.$legal[mt_rand(0,strlen($legal)-1)];
  }
  return $result;
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
  irciv_term_echo("saving IRCiv data...");
  $players=irciv_get_bucket("players");
  $map_coords=irciv_get_bucket("map_coords");
  $map_data=irciv_get_bucket("map_data");
  if (file_put_contents(IRCIV_DATA_FILE,$players."\n".$map_coords."\n".$map_data)===False)
  {
    irciv_err("IRCiv data not saved");
    return;
  }
  irciv_term_echo("IRCiv data saved");
}

#####################################################################################################

function irciv_load_data()
{
  irciv_term_echo("loading IRCiv data...");
  if (file_exists(IRCIV_DATA_FILE)==True)
  {
    $data=file_get_contents(IRCIV_DATA_FILE);
    $lines=explode("\n",$data);
    $players=$lines[0];
    $map_coords=$lines[1];
    $map_data=$lines[2];
    irciv_set_bucket("players",$players);
    irciv_set_bucket("map_coords",$map_coords);
    irciv_set_bucket("map_data",$map_data);
  }
  else
  {
    irciv_term_echo("IRCiv data file not found");
  }
}

#####################################################################################################

?>
