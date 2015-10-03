<?php

#####################################################################################################

if (isset($_GET["exec"])==True)
{
  require_once(__DIR__."/relay.inc");
  return;
}

if (isset($_GET["stats"])==True)
{
  require_once(__DIR__."/stats.inc");
  return;
}

if (isset($_GET["execfs"])==True)
{
  require_once(__DIR__."/execfs.inc");
  return;
}

if (isset($_GET["karma"])==True)
{
  require_once(__DIR__."/karma.inc");
  return;
}

# IRCiv

define("MAPS_PATH","/var/include/vhosts/irciv.bot.nu/inc/maps");

if ((isset($_POST["exec_key"])==True) and (isset($_FILES["image"])==True))
{
  check_exec_key();
  if (valid_filename($_FILES["image"]["name"])==False)
  {
    die("ERROR: INVALID FILENAME");
  }
  if (move_uploaded_file($_FILES["image"]["tmp_name"],MAPS_PATH."/".$_FILES["image"]["name"].".png")==False)
  {
    die("ERROR: UNABLE TO MOVE UPLOADED FILE");
  }
  die("SUCCESS");
}

if (isset($_GET["logo"])==True)
{
  output_png_image(__DIR__."/irciv.png");
}

if (isset($_GET["img"])==True)
{
  if (valid_filename($_GET["img"])==True)
  {
    output_png_image(MAPS_PATH."/".$_GET["img"].".png");
  }
  else
  {
    output_png_image("");
  }
}

$page=file_get_contents(__DIR__."/template");
$page=str_replace("%%domain%%","http://".$_SERVER["SERVER_NAME"]."/",$page);
$map="";
if (isset($_GET["map"])==True)
{
  if (valid_filename($_GET["map"])==True)
  {
    $map=$_GET["map"];
  }
}
if (isset($_GET["pid"])==True)
{
  if (is_numeric($_GET["pid"])==True)
  {
    $player_id=sprintf("%02d",$_GET["pid"]);
    $dir=opendir(MAPS_PATH);
    $last_timestamp=0;
    $last_filename="";
    while(($file=readdir($dir))!==False)
    {
      $file_player_id=substr($file,2,2);
      $file_timestamp=substr($file,4,14);
      if (($file_player_id==$player_id) and ($file_timestamp>$last_timestamp))
      {
        $last_timestamp=$file_timestamp;
        $last_filename=$file;
      }
    }
    if (strlen($last_filename)>0)
    {
      $map=substr($last_filename,0,strlen($last_filename)-4);
    }
    closedir($dir);
  }
}
$page=str_replace("%%map%%",$map,$page);
echo $page;

#####################################################################################################

function empty_img()
{
  $buffer=imagecreatetruecolor(1,1);
  $bg_color=imagecolorallocate($buffer,255,0,255);
  imagecolortransparent($buffer,$bg_color);
  imagefill($buffer,0,0,$bg_color);
}

#####################################################################################################

function output_png_image($filename)
{
  ob_clean();
  if (file_exists($filename)==False)
  {
    $buffer=empty_img();
  }
  else
  {
    $buffer=imagecreatefrompng($filename);
    if ($buffer===False)
    {
      $buffer=empty_img();
    }
  }
  header("Content-Type: image/png");
  imagepng($buffer);
  imagedestroy($buffer);
  die();
}

#####################################################################################################

function valid_filename($value)
{
  $legal="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  for ($i=0;$i<strlen($value);$i++)
  {
    if (strpos($legal,$value[$i])===False)
    {
      return False;
    }
  }
  return True;
}

#####################################################################################################

function check_exec_key()
{
  $key=file_get_contents("/var/include/vhosts/irciv.bot.nu/pwd/exec_key");
  if ($key===False)
  {
    die("ERROR: UNABLE TO READ EXEC KEY FILE");
  }
  if ($key!==$_POST["exec_key"])
  {
    die("ERROR: EXEC KEY MISMATCH");
  }
}

#####################################################################################################

?>
