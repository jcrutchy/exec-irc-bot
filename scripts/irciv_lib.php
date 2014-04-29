<?php

# gpl2
# by crutchy
# 27-april-2014

# irciv_lib.php

define("GAME_NAME","IRCiv");
define("NICK_EXEC","exec");
define("GAME_CHAN","#civ");
define("BUCKET_PREFIX",GAME_NAME."_".GAME_CHAN."_");

#####################################################################################################

function irciv__term_echo($msg)
{
  echo "\033[34m".GAME_NAME.": $msg\033[0m\n";
}

#####################################################################################################

function irciv__privmsg($msg)
{
  echo "IRC_MSG ".GAME_NAME.": $msg\n";
}

#####################################################################################################

function irciv__err($msg)
{
  echo "IRC_MSG ".GAME_NAME." error: $msg\n";
  die();
}

#####################################################################################################

function get_bucket($suffix)
{
  echo ":".NICK_EXEC." BUCKET_GET :".BUCKET_PREFIX."$suffix\n";
  $f=fopen("php://stdin","r");
  $data=fgets($f);
  if ($data===False)
  {
    irciv__err("unable to read bucket data");
  }
  else
  {
    return trim($data);
  }
  fclose($f);
}

#####################################################################################################

function set_bucket($suffix,$data)
{
  echo ":".NICK_EXEC." BUCKET_SET :".BUCKET_PREFIX."$suffix $data\n";
}

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function map_generate($cols,$rows,$landmass_count,$landmass_size,$land_spread,$ocean_char,$land_char)
{
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = Up
     1 = Right
     2 = Down
     3 = Left */
  $count=$rows*$cols;
  $coords=str_repeat($ocean_char,$count);
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
  }
  # fill in any isolated inland 1x1 lakes
  for ($y=0;$y<$rows;$y++)
  {
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

function map_dump($coords,$cols,$rows)
{
  irciv__term_echo("############ BEGIN MAP DUMP ############");
  for ($i=0;$i<$rows;$i++)
  {
    irciv__term_echo(substr($coords,$i*$cols,$cols));
  }
  irciv__term_echo("############# END MAP DUMP #############");
}

#####################################################################################################

?>
