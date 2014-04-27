<?php

# gpl2
# by crutchy
# 27-april-2014

# irciv_lib.php

define("GAME_NAME","IRCiv");
define("NICK_EXEC","exec");

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

function get_bucket()
{
  global $bucket;
  echo ":".NICK_EXEC." BUCKET_GET :[\"civ\"]\n";
  $f=fopen("php://stdin","r");
  $line=fgets($f);
  if ($line===False)
  {
    irciv__err("unable to read bucket data");
  }
  else
  {
    $line=trim($line);
    if ($line<>"")
    {
      echo "$line\n";
      $tmp=unserialize($line);
      if ($tmp!==False)
      {
        $bucket["civ"]=$tmp;
        irciv__term_echo("successfully loaded bucket data");
      }
      else
      {
        irciv__term_echo("error unserializing bucket data");
      }
    }
    else
    {
      irciv__term_echo("no bucket data to load");
    }
  }
  fclose($f);
}

#####################################################################################################

function set_bucket()
{
  global $bucket;
  $data=serialize($bucket);
  echo ":".NICK_EXEC." BUCKET_SET :$data\n";
}

#####################################################################################################

function map_coord($cols,$x,$y)
{
  return ($x+$y*$cols);
}

#####################################################################################################

function map_generate($chan)
{
  global $maps;
  global $cols;
  global $rows;
  $dir_x=array(0,1,0,-1);
  $dir_y=array(-1,0,1,0);
  /* 0 = Up
     1 = Right
     2 = Down
     3 = Left */
  $count=$rows*$cols;
  $maps[$chan]=array();
  $coords=str_repeat("O",$count);
  $landmass_count=20;
  $landmass_size=200;
  for ($i=0;$i<$landmass_count;$i++)
  {
    $n=0;
    $x=mt_rand(0,$cols-1);
    $y=mt_rand(0,$rows-1);
    $coords[map_coord($x,$y)]="L";
    $n++;
    $x1=$x;
    $y1=$y;
    $d=mt_rand(0,3);
    $size=$landmass_size;
    while ($n<$size)
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
      if ($coords[map_coord($x1,$y1)]<>"L")
      {
        $coords[map_coord($x1,$y1)]="L";
        $n++;
      }
      if (mt_rand(0,100)==0) # higher upper limit makes landmass more spread out
      {
        $x1=$x;
        $y1=$y;
      }
      $size=mt_rand($landmass_size-round(0.2*$landmass_size),$landmass_size+round(0.2*$landmass_size));
    }
  }
  # fill in any isolated inland 1x1 lakes
  for ($y=0;$y<$rows;$y++)
  {
    for ($x=0;$x<$cols;$x++)
    {
      $i=map_coord($x,$y);
      if ($coords[$i]=="O")
      {
        $n=0;
        for ($j=0;$j<=3;$j++)
        {
          $x1=$x+$dir_x[$j];
          $y1=$y+$dir_y[$j];
          if (($x1>=0) and ($y1>=0) and ($x1<$cols) and ($y1<$rows))
          {
            if ($coords[map_coord($x1,$y1)]=="L")
            {
              $n++;
            }
          }
        }
        if ($n==4)
        {
          $coords[$i]="L";
        }
      }
    }
  }
  #$maps[$chan]["coords"]=gzcompress($coords);
  $maps[$chan]["coords"]=$coords;
}

#####################################################################################################

function map_dump($chan)
{
  global $maps;
  global $cols;
  global $rows;
  if (isset($maps[$chan]["coords"])==False)
  {
    return;
  }
  #$coords=gzuncompress($maps[$chan]["coords"]);
  $coords=$maps[$chan]["coords"];
  irciv__term_echo("############ BEGIN MAP DUMP ############");
  for ($i=0;$i<$rows;$i++)
  {
    irciv__term_echo(substr($coords,$i*$cols,$cols));
  }
  irciv__term_echo("########################################");
}

#####################################################################################################

?>
