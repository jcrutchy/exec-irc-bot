<?php

# gpl2
# by crutchy

#####################################################################################################

require_once(__DIR__."/../lib.php");

#####################################################################################################

define("GAME_VERSION","0.0");

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

function get_game_list()
{
  $prefix="IRCIV_GAME_";
  $len_prefix=strlen($prefix);
  $buckets=bucket_list();
  $game_list=array();
  for ($i=0;$i<count($buckets);$i++)
  {
    if (substr($buckets[$i],0,$len_prefix)==$prefix)
    {
      $game_list[]=$buckets[$i];
    }
  }
  return $game_list;
}

#####################################################################################################

function is_gm()
{
  global $nick;
  global $gm_accounts;
  $account=users_get_account($nick);
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

function register_channel()
{
  global $trailing;
  global $dest;
  global $irciv_players;
  global $irciv_channels;
  global $irciv_data_changed;
  $channel="";
  if ($trailing<>"")
  {
    $channel=strtolower($trailing);
  }
  elseif ($dest<>"")
  {
    $channel=strtolower($dest);
  }
  if ($channel=="")
  {
    irciv_term_echo("register_channel: channel not specified");
    return;
  }
  if (users_chan_exists($channel)==False)
  {
    irciv_privmsg("error: channel not found");
    return;
  }
  if (isset($irciv_channels[$channel])==True)
  {
    unset($irciv_channels[$channel]);
  }
  $map_data=generate_map_data();
  $irciv_channels[$channel]["map"]=$map_data;
  $irciv_data_changed=True;
  $msg="registered and generated map for channel $channel";
  if ($trailing<>"")
  {
    irciv_privmsg_dest($trailing,$msg);
  }
  if (($dest<>"") and ($dest<>$trailing))
  {
    irciv_privmsg_dest($dest,$msg);
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
  irciv_term_echo("saving irciv data...");
  $players=irciv_get_bucket("players");
  if (file_put_contents(IRCIV_FILE_PLAYER_DATA,$players)===False)
  {
    irciv_term_echo("irciv player data not saved");
  }
  for ($i=0;$i<count($game_chans);$i++)
  {
    $map_coords=irciv_get_bucket("map_coords_".$game_chans[$i]);
    $map_data=irciv_get_bucket("map_data_".$game_chans[$i]);
    if (file_put_contents(IRCIV_FILE_MAP_COORDS.$game_chans[$i],$map_coords)===False)
    {
      irciv_term_echo("irciv map coords for channel \"".$game_chans[$i]."\" not saved");
    }
    if (file_put_contents(IRCIV_FILE_MAP_DATA.$game_chans[$i],$map_data)===False)
    {
      irciv_term_echo("irciv map data for channel \"".$game_chans[$i]."\" not saved");
    }
  }
  irciv_term_echo("irciv data saved");
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

function privmsg_player_game_chans($nick,$msg)
{
  global $game_chans;
  $nick_chans=get_bucket($nick."_channel_list");
  if ($nick_chans=="")
  {
    irciv_term_echo("priv_msg_all_player_game_chans: nick \"$nick\" channels not set");
  }
  $nick_chans=explode(" ",$nick_chans);
  for ($i=0;$i<count($game_chans);$i++)
  {
    if (in_array($game_chans[$i],$nick_chans)==True)
    {
      echo "IRC_RAW :".NICK_EXEC." PRIVMSG ".$game_chans[$i]." :$msg\n";
    }
  }
}

#####################################################################################################

function set_player_color($nick,$color="")
{
  global $players;
  $reserved_colors=array("255,0,255","0,0,0","255,255,255");
  if ($color=="")
  {
    do
    {
      $color=mt_rand(0,255).",".mt_rand(0,255).",".mt_rand(0,255);
      foreach ($players as $player => $data)
      {
        if (($player<>$nick) and ($color==$players[$player]["color"]))
        {
          continue;
        }
      }
    }
    while (in_array($color,$reserved_colors)==True);
    $players[$nick]["color"]=$color;
  }
  else
  {
    foreach ($players as $player => $data)
    {
      if (($player<>$nick) and ($color==$players[$player]["color"]))
      {
        return False;
      }
    }
    if (in_array($color,$reserved_colors)==True)
    {
      return False;
    }
    $players[$nick]["color"]=$color;
    return True;
  }
}

#####################################################################################################

function get_new_player_id()
{
  global $players;
  $player_id=1;
  foreach ($players as $nick => $data)
  {
    if ($player_id<=$data["player_id"])
    {
      $player_id=$data["player_id"]+1;
    }
  }
  return $player_id;
}

#####################################################################################################

function validate_logins()
{
  global $players;
  global $start;
  foreach ($players as $nick => $data)
  {
    if (isset($players[$nick]["login_time"])==True)
    {
      if ($players[$nick]["login_time"]<$start)
      {
        $players[$nick]["logged_in"]=False;
      }
    }
  }
}

#####################################################################################################

function is_logged_in($nick)
{
  global $players;
  if (isset($players[$nick]["logged_in"])==False)
  {
    return False;
  }
  if ($players[$nick]["logged_in"]==False)
  {
    return False;
  }
  else
  {
    return True;
  }
}

#####################################################################################################

function output_help()
{
  irciv_privmsg("QUICK START GUIDE");
  irciv_privmsg("unit movement: (left|l),(right|r),(up|u),(down|d)");
  irciv_privmsg("settler actions: (build|b)");
  irciv_privmsg("player functions: (help|?),status,init,flag/unflag,set/unset");
  irciv_privmsg("flags: public_status,grid,coords,city_names");
}

#####################################################################################################

function player_ready($nick)
{
  global $players;
  global $map_data;
  if (isset($map_data["cols"])==False)
  {
    irciv_privmsg("error: map not ready");
    return False;
  }
  if (isset($players[$nick])==False)
  {
    irciv_privmsg("player \"$nick\" not found");
    return False;
  }
  return True;
}

#####################################################################################################

function player_init($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
  {
    return;
  }
  $players[$nick]["init_time"]=time();
  set_player_color($nick);
  $players[$nick]["units"]=array();
  $players[$nick]["cities"]=array();
  $players[$nick]["fog"]=str_repeat("0",strlen($map_coords));
  $start_x=-1;
  $start_y=-1;
  if (random_coord(TERRAIN_LAND,$start_x,$start_y)==False)
  {
    return;
  }
  add_unit($nick,"settler",$start_x,$start_y);
  add_unit($nick,"warrior",$start_x,$start_y);
  $players[$nick]["active"]=-1;
  cycle_active($nick);
  $players[$nick]["start_x"]=$start_x;
  $players[$nick]["start_y"]=$start_y;
  status($nick);
}

#####################################################################################################

function random_coord($terrain,&$x,&$y)
{
  global $map_coords;
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
  while ($map_coords[$coord]<>$terrain);
  return True;
}

#####################################################################################################

function add_unit($nick,$type,$x,$y)
{
  global $players;
  global $unit_strengths;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $units=&$players[$nick]["units"];
  $data["type"]=$type;
  $data["health"]=100;
  $data["sight_range"]=4;
  $data["x"]=$x;
  $data["y"]=$y;
  $data["strength"]=$unit_strengths[$type];
  $units[]=$data;
  $i=count($units)-1;
  $units[$i]["index"]=$i;
  unfog($nick,$x,$y,$data["sight_range"]);
  return True;
}

#####################################################################################################

function add_city($nick,$x,$y,$city_name)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return;
  }
  $cities=&$players[$nick]["cities"];
  $data["name"]=$city_name;
  $data["population"]=1;
  $data["size"]=1;
  $data["sight_range"]=7;
  $data["x"]=$x;
  $data["y"]=$y;
  $cities[]=$data;
  $i=count($cities)-1;
  $cities[$i]["index"]=$i;
  unfog($nick,$x,$y,$data["sight_range"]);
}

#####################################################################################################

function unfog($nick,$x,$y,$range)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
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
          $players[$nick]["fog"][$coord]="1";
        }
      }
    }
  }
  imagedestroy($region);
}

#####################################################################################################

function status($nick)
{
  global $players;
  global $map_data;
  global $dest;
  if (isset($players[$nick])==False)
  {
    return;
  }
  /*$public=False;
  if (isset($players[$nick]["flags"]["public_status"])==True)
  {
    $public=True;
  }*/
  $public=True; # TODO: DELETE & RESTORE CODE ABOVE
  $i=$players[$nick]["active"];
  $unit=$players[$nick]["units"][$i];
  $index=$unit["index"];
  $type=$unit["type"];
  $health=$unit["health"];
  $x=$unit["x"];
  $y=$unit["y"];
  $n=count($players[$nick]["units"]);
  if (isset($players[$nick]["status_messages"])==True)
  {
    for ($i=0;$i<count($players[$nick]["status_messages"]);$i++)
    {
      status_msg($nick,$dest." $nick => ".$players[$nick]["status_messages"][$i],$public);
    }
    unset($players[$nick]["status_messages"]);
  }
  status_msg($nick,$dest." $nick => $index/$n, $type, +$health, ($x,$y)",$public);
}

#####################################################################################################

function status_msg($nick,$msg,$public)
{
  if ($public==False)
  {
    pm($nick,$msg);
  }
  else
  {
    irciv_privmsg($msg);
  }
}

#####################################################################################################

function move_active_unit($nick,$dir)
{
  global $players;
  global $map_data;
  global $map_coords;
  global $update_players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  $captions=array("up","right","down","left");
  if (isset($players[$nick]["active"])==True)
  {
    $active=$players[$nick]["active"];
    $old_x=$players[$nick]["units"][$active]["x"];
    $old_y=$players[$nick]["units"][$active]["y"];
    $x=$old_x+$dir_x[$dir];
    $y=$old_y+$dir_y[$dir];
    $caption=$captions[$dir];
    if (($x<0) or ($x>=$map_data["cols"]) or ($y<0) or ($y>=$map_data["rows"]))
    {
      $players[$nick]["status_messages"][]="move $caption failed for active unit (already @ edge of map)";
    }
    elseif ($map_coords[map_coord($map_data["cols"],$x,$y)]<>TERRAIN_LAND)
    {
      $players[$nick]["status_messages"][]="move $caption failed for active unit (already @ edge of landmass)";
    }
    else
    {
      $player=is_foreign_unit($nick,$x,$y);
      if ($player===False)
      {
        $players[$nick]["units"][$active]["x"]=$x;
        $players[$nick]["units"][$active]["y"]=$y;
        unfog($nick,$x,$y,$players[$nick]["units"][$active]["sight_range"]);
        $type=$players[$nick]["units"][$active]["type"];
        $players[$nick]["status_messages"][]="successfully moved $type $caption from ($old_x,$old_y) to ($x,$y)";
        $update_players=True;
        update_other_players($nick,$active);
        cycle_active($nick);
      }
      else
      {
        $players[$nick]["status_messages"][]="move $caption failed for active unit (player \"$player\" is occupying)";
        # if player is enemy, attack!
      }
    }
    status($nick);
  }
}

#####################################################################################################

function is_foreign_unit($nick,$x,$y)
{
  global $players;
  foreach ($players as $player => $data)
  {
    if ($player<>$nick)
    {
      for ($i=0;$i<count($players[$player]["units"]);$i++)
      {
        $unit=$players[$player]["units"][$i];
        if (($unit["x"]==$x) and ($unit["y"]==$y))
        {
          return $player;
        }
      }
    }
  }
  return False;
}

#####################################################################################################

function is_fogged($nick,$x,$y)
{
  global $players;
  global $map_data;
  $cols=$map_data["cols"];
  $coord=map_coord($cols,$x,$y);
  if ($players[$nick]["fog"][$coord]=="0")
  {
    return True;
  }
  else
  {
    return False;
  }
}

#####################################################################################################

function update_other_players($nick,$active)
{
  global $players;
  global $map_data;
  global $map_coords;
  $x=$players[$nick]["units"][$active]["x"];
  $y=$players[$nick]["units"][$active]["y"];
  foreach ($players as $player => $data)
  {
    if ($player==$nick)
    {
      continue;
    }
    if (player_ready($player)==False)
    {
      continue;
    }
    if (is_fogged($player,$x,$y)==False)
    {
      $players[$player]["status_messages"][]="player \"$nick\" moved a unit within your field of vision";
      $players[$nick]["status_messages"][]="you moved a unit within the field of vision of player \"$player\"";
      output_map($player);
      status($player);
    }
  }
}

#####################################################################################################

function delete_unit($nick,$index)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  if (isset($players[$nick]["units"][$index])==False)
  {
    return False;
  }
  $count=count($players[$nick]["units"]);
  $next=$index+1;
  for ($i=$next;$i<$count;$i++)
  {
    $players[$nick]["units"][$i]["index"]=$i-1;
  }
  unset($players[$nick]["units"][$index]);
  $players[$nick]["units"]=array_values($players[$nick]["units"]);
  return True;
}

#####################################################################################################

function build_city($nick,$city_name)
{
  global $players;
  global $map_data;
  global $map_coords;
  if (player_ready($nick)==False)
  {
    return False;
  }
  if (isset($players[$nick]["active"])==False)
  {
    return False;
  }
  $unit=$players[$nick]["units"][$players[$nick]["active"]];
  if ($unit["type"]<>"settler")
  {
    $players[$nick]["status_messages"][]="only settlers can build cities";
  }
  else
  {
    $x=$unit["x"];
    $y=$unit["y"];
    $city_exists=False;
    $city_adjacent=False;
    $cities=&$players[$nick]["cities"];
    for ($i=0;$i<count($cities);$i++)
    {
      if ($cities[$i]["name"]==$city_name)
      {
        $city_exists=True;
        $players[$nick]["status_messages"][]="city named \"$city_name\" already exists";
        break;
      }
      $dx=abs($cities[$i]["x"]-$x);
      $dy=abs($cities[$i]["y"]-$y);
      if (($dx<MIN_CITY_SPACING) and ($dy<MIN_CITY_SPACING))
      {
        $city_adjacent=True;
        $players[$nick]["status_messages"][]="city \"".$cities[$i]["name"]."\" is too close";
        break;
      }
    }
    if (($city_exists==False) and ($city_adjacent==False))
    {
      add_city($nick,$x,$y,$city_name);
      #delete_unit($nick,$players[$nick]["active"]); # WORKS BUT LEAVE OUT FOR TESTING
      $players[$nick]["status_messages"][]="successfully established the new city of \"$city_name\" at coordinates ($x,$y)";
      cycle_active($nick);
    }
  }
  status($nick);
}

#####################################################################################################

function output_map($nick)
{
  global $players;
  global $map_coords;
  global $map_data;
  if (player_ready($nick)==False)
  {
    return False;
  }
  $game_id=sprintf("%02d",0);
  $player_id=sprintf("%02d",$players[$nick]["player_id"]);
  $timestamp=date("YmdHis",time());
  $key=random_string(16);
  $filename=$game_id.$player_id.$timestamp.$key;
  $response=upload_map_image($filename,$map_coords,$map_data,$players,$nick);
  $response_lines=explode("\n",$response);
  $msg=trim($response_lines[count($response_lines)-1]);
  if (trim($response_lines[0])=="HTTP/1.1 200 OK")
  { 
    if ($msg=="SUCCESS")
    {
      $players[$nick]["status_messages"][]="http://irciv.port119.net/?pid=".$players[$nick]["player_id"];
      #$players[$nick]["status_messages"][]="http://irciv.port119.net/?map=$filename";
    }
  }
  else
  {
    $players[$nick]["status_messages"][]=$msg;
  }
}

#####################################################################################################

function cycle_active($nick)
{
  global $players;
  if (player_ready($nick)==False)
  {
    return False;
  }
  output_map($nick);
  $n=count($players[$nick]["units"]);
  if (isset($players[$nick]["active"])==False)
  {
    $players[$nick]["active"]=0;
  }
  else
  {
    $players[$nick]["active"]=$players[$nick]["active"]+1;
    if ($players[$nick]["active"]>=$n)
    {
      $players[$nick]["active"]=0;
    }
  }
}

#####################################################################################################

?>
