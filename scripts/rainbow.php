<?php

# gpl2
# by crutchy

#####################################################################################################

/*
exec:~rainbow|5|0|0|0|||||php scripts/rainbow.php %%trailing%%
*/

#####################################################################################################

require_once("lib.php");

$trailing=$argv[1];

define("COLOR_PREFIX","");
define("COLOR_SUFFIX","");
$rainbow_colors=array("04","07","08","09","12","02","06");

privmsg(rainbowize($trailing));

#####################################################################################################

function rainbowize($msg)
{
  global $rainbow_colors;
  $offset=mt_rand(1,count($rainbow_colors));
  $out="";
  for ($i=0;$i<strlen($msg);$i++)
  {
    $out=$out.colored($msg[$i],"00",$rainbow_colors[($i+$offset)%count($rainbow_colors)]);
  }
  return $out;
}

#####################################################################################################

function colored($msg,$fg,$bg)
{
  if ($bg==-1)
  {
    if ($fg==-1)
    {
      $out=$msg;
    }
    else
    {
      $out=COLOR_PREFIX.$fg.$msg.COLOR_SUFFIX;
    }
  }
  else
  {
    if ($fg==-1)
    {
      $out=COLOR_PREFIX."00,".$bg.$msg.COLOR_SUFFIX;
    }
    else
    {
      $out=COLOR_PREFIX.$fg.",".$bg.$msg.COLOR_SUFFIX;
    }
  }
  return $out;
}

#####################################################################################################

?>
