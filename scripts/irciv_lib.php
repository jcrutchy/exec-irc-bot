<?php

# gpl2
# by crutchy
# 17-may-2014

# irciv_lib.php

require_once("lib.php");

define("GAME_VERSION","0.0");
define("GAME_CHAN","#civ");
define("BUCKET_PREFIX","IRCiv_".GAME_CHAN."_");

define("IRCIV_DATA_FILE","../data/irciv_data");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

define("IMAGE_TERRAIN_OCEAN","ocean.png");
define("IMAGE_TERRAIN_LAND","grassland.png");
define("IMAGE_UNIT_SETTLER","settler.png");
define("IMAGE_UNIT_WARRIOR","warrior.png");
define("IMAGE_CITY_SIZE_1","city1.png");

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

function map_img($map_coords,$map_data,$filename="",$player_data="",$nick="",$filetype="gif")
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
  $buffer_unit_settler=imagecreatefrompng(PATH_IMAGES.IMAGE_UNIT_SETTLER);
  if ($buffer_unit_settler===False)
  {
    return False;
  }
  $buffer_unit_warrior=imagecreatefrompng(PATH_IMAGES.IMAGE_UNIT_WARRIOR);
  if ($buffer_unit_warrior===False)
  {
    return False;
  }
  $buffer_city_size_1=imagecreatefrompng(PATH_IMAGES.IMAGE_CITY_SIZE_1);
  if ($buffer_city_size_1===False)
  {
    return False;
  }
  $tile_w=imagesx($buffer_terrain_ocean);
  $tile_h=imagesy($buffer_terrain_ocean);
  $unit_w=imagesx($buffer_unit_settler);
  $unit_h=imagesy($buffer_unit_settler);
  $city_w=imagesx($buffer_city_size_1);
  $city_h=imagesy($buffer_city_size_1);
  $w=$cols*$tile_w;
  $h=$rows*$tile_h;
  $buffer=imagecreatetruecolor($w,$h);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
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
    $color_transparent=imagecolorallocate($buffer_unit_settler,255,0,255);
    imagecolortransparent($buffer_unit_settler,$color_transparent);
    imagecolortransparent($buffer_unit_warrior,$color_transparent);
    imagecolortransparent($buffer_city_size_1,$color_transparent);
    imagealphablending($buffer,True);
    imagesavealpha($buffer,True);
    for ($i=0;$i<count($player_data[$nick]["cities"]);$i++)
    {
      $city=$player_data[$nick]["cities"][$i];
      $x=$city["x"];
      $y=$city["y"];
      $dx=($city_w-$tile_w)/2;
      $dy=($city_h-$tile_h)/2;
      imagecopy($buffer,$buffer_city_size_1,round($x*$tile_w-$dx),round($y*$tile_h-$dy),0,0,$city_w,$city_h);
    }
    for ($i=0;$i<count($player_data[$nick]["units"]);$i++)
    {
      $unit=$player_data[$nick]["units"][$i];
      $x=$unit["x"];
      $y=$unit["y"];
      $dx=($unit_w-$tile_w)/2;
      $dy=$unit_h-$tile_h;
      switch ($unit["type"])
      {
        case "settler":
          imagecopy($buffer,$buffer_unit_settler,round($x*$tile_w-$dx),round($y*$tile_h-$dy),0,0,$unit_w,$unit_h);
          break;
        case "warrior":
          imagecopy($buffer,$buffer_unit_warrior,round($x*$tile_w-$dx),round($y*$tile_h-$dy),0,0,$unit_w,$unit_h);
          break;
      }
    }
  }
  imagedestroy($buffer_unit_settler);
  imagedestroy($buffer_unit_warrior);
  imagedestroy($buffer_city_size_1);
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
    return $data;
  }
  imagedestroy($buffer);
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
    #irciv_set_bucket("players",serialize($players));*/
  }
  else
  {
    irciv_term_echo("IRCiv data file not found");
  }
}

#####################################################################################################

?>
