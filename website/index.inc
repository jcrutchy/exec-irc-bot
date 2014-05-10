<?php

if ((isset($_POST["exec_key"])==True) and (isset($_POST["filename"])==True) and (isset($_FILES["image"])==True))
{
  $ref=file_get_contents(__DIR__."/exec_key");
  if ($ref===False)
  {
    die("ERROR: UNABLE TO LOAD REFERENCE KEY");
  }
  if ($ref!==$_POST["exec_key"])
  {
    die("ERROR: KEY MISMATCH");
  }
  if (valid_filename($_POST["filename"])==False)
  {
    die("ERROR: INVALID FILENAME");
  }
  if (move_uploaded_file($_FILES["image"]["tmp_name"],__DIR__."/maps/".$_POST["filename"].".gif")==False)
  {
    die("ERROR: UNABLE TO MOVE UPLOADED FILE");
  }
  die("SUCCESS");
}

if (isset($_GET["logo"])==True)
{
  output_gif_image(__DIR__."/irciv.gif");
}

if (isset($_GET["img"])==True)
{
  if (valid_filename($_GET["img"])==True)
  {
    output_gif_image(__DIR__."/maps/".$_GET["img"].".gif");
  }
  else
  {
    empty_image();
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
$page=str_replace("%%map%%",$map,$page);
echo $page;

#####################################################################################################

function empty_image()
{
  ob_clean();
  $buffer=imagecreatetruecolor(1,1);
  $bg_color=imagecolorallocate($buffer,255,0,255); # magenta
  imagecolortransparent($buffer,$bg_color);
  imagefill($buffer,0,0,$bg_color);
  header("Content-Type: image/gif");
  imagegif($buffer);
  imagedestroy($buffer);
  die();
}

#####################################################################################################

function output_gif_image($filename)
{
  if (file_exists($filename)==False)
  {
    empty_image();
  }
  ob_clean();
  $buffer=imagecreatefromgif($filename);
  header("Content-Type: image/gif");
  imagegif($buffer);
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

?>
