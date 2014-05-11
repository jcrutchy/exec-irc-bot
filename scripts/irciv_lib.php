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

function map_gif($map_coords,$map_data,$scale,$filename="")
{
  $cols=$map_data["cols"];
  $rows=$map_data["rows"];
  $w=$cols*$scale;
  $h=$rows*$scale;
  $buffer=imagecreatetruecolor($w,$h);
  $color_ocean=imagecolorallocate($buffer,142,163,234); # blue
  $color_land=imagecolorallocate($buffer,90,132,72); # green
  imagefill($buffer,0,0,$color_ocean);
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($cols,$x,$y);
      if ($map_coords[$i]==TERRAIN_LAND)
      {
        imagefilledrectangle($buffer,$x*$scale,$y*$scale,($x+1)*$scale-1,($y+1)*$scale-1,$color_land);
      }
    }
  }
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

function upload_map_image($filename,$map_coords,$map_data)
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
  $gif_data=map_gif($map_coords,$map_data,10);
  if ($gif_data===False)
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
