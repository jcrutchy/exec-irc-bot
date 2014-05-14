<?php

# gpl2
# by crutchy
# 02-may-2014

# irciv_lib.php

require_once("lib.php");

define("GAME_NAME","IRCiv");
define("GAME_VERSION","0.0");
define("GAME_CHAN","#civ");
define("BUCKET_PREFIX",GAME_NAME."_".GAME_CHAN."_");

define("TERRAIN_OCEAN","O");
define("TERRAIN_LAND","L");

define("IMAGE_TERRAIN_OCEAN","ocean.png");
define("IMAGE_TERRAIN_LAND","grassland.png");
define("IMAGE_UNIT_SETTLER","settler.png");
define("IMAGE_UNIT_WARRIOR","warrior.png");

define("PATH_IMAGES",__DIR__."/images/");

#####################################################################################################

function irciv_term_echo($msg)
{
  term_echo(GAME_NAME.": $msg");
}

#####################################################################################################

function irciv_privmsg($msg)
{
  privmsg(GAME_NAME.": $msg");
}

#####################################################################################################

function irciv_err($msg)
{
  err(GAME_NAME." error: $msg");
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

function map_gif($map_coords,$map_data,$filename="",$player_data="",$nick="")
{
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  $buffer_terrain_ocean=imagecreatefrompng(PATH_IMAGES.IMAGE_TERRAIN_OCEAN);
  $buffer_terrain_land=imagecreatefrompng(PATH_IMAGES.IMAGE_TERRAIN_LAND);
  $buffer_unit_settler=imagecreatefrompng(PATH_IMAGES.IMAGE_UNIT_SETTLER);
  $buffer_unit_warrior=imagecreatefrompng(PATH_IMAGES.IMAGE_UNIT_WARRIOR);
  if (($buffer_terrain_ocean===False) or ($buffer_terrain_land===False) or ($buffer_unit_settler===False) or ($buffer_unit_warrior===False))
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
  if (($player_data<>"") and ($nick<>""))
  {
    #imagealphablending($imgdest,False);
    #imagesavealpha($imgdest,True);
  }
  # to make final map image smaller filesize, use createimage to create palleted image, then copy truecolor image to palleted image

  $scale=0.25;
  $final_w=round($w*$scale);
  $final_h=round($h*$scale);
  $buffer_resized=imagecreatetruecolor($final_w,$final_h);
  imagecopyresampled($buffer_resized,$buffer,0,0,0,0,$final_w,$final_h,$w,$h);
  if ($filename<>"")
  {
    imagegif($buffer,$filename.".gif");
  }
  else
  {
    ob_start();
    imagegif($buffer);
    $data=ob_get_contents();
    ob_end_clean();
    return $data;
  }
  imagedestroy($buffer_terrain_ocean);
  imagedestroy($buffer_terrain_land);
  imagedestroy($buffer_unit_settler);
  imagedestroy($buffer_unit_warrior);
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
  $gif_data=False;
  #$gif_data=map_gif($map_coords,$map_data,"",$players,$nick);
  if (($gif_data===False) or ($gif_data==""))
  {
    return "upload_map_image: map_gif error";
  }
  do
  {
    $boundary=random_string(40);
  }
  while ((strpos($gif_data,$boundary)!==False) or (strpos($exec_key,$boundary)!==False));
  $headers=str_replace("%%uri%%",$uri,$headers);
  $headers=str_replace("%%host%%",$host,$headers);
  $content=str_replace("%%filename%%",$filename,$content);
  $headers=str_replace("%%boundary%%",$boundary,$headers);
  $content=str_replace("%%boundary%%",$boundary,$content);
  $headers=str_replace("%%game_name%%",GAME_NAME,$headers);
  $headers=str_replace("%%game_version%%",GAME_VERSION,$headers);
  $content=str_replace("%%exec_key%%",$exec_key,$content);
  $content=str_replace("%%gif_data%%",$gif_data,$content);
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

?>
