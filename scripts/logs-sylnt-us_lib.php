<?php

#####################################################################################################

require_once("chart_lib.php");

#####################################################################################################

function chart(&$log_data,$chan,$filename)
{
  $w=1800;
  $h=800;
  $nick_data=array();
  for ($i=0;$i<count($log_data);$i++)
  {
    if ($log_data[$i]["dest"]<>$chan)
    {
      continue;
    }
    $nick=$log_data[$i]["nick"];
    $nick_data[$nick][]=$log_data[$i];
  }
  $left_margin=50; # pixels
  $min_y=-1;
  $max_y=count($nick_data)-1;
  $min_x=$log_data[0]["timestamp"];
  $max_x=$log_data[count($log_data)-1]["timestamp"];
  $dx=$max_x-$min_x;
  term_echo("*** dx = $dx");
  $dt=1/chart__ppu($w-$left_margin,$min_x,$max_x); # number of seconds per pixel
  term_echo("*** dt = $dt");
  $m=$dx/$dt;
  term_echo("*** m = $m");
  $chart_data=array();
  $y=0;
  $max_lines_per_pixel=0;
  foreach ($nick_data as $nick => $records)
  {
    for ($i=0;$i<count($nick_data[$nick]);$i++)
    {
      $x=chart__r2p_x($w,$min_x,$max_x,$log_data[$i]["timestamp"])+$left_margin;
      if (isset($chart_data[$nick][$x])==True)
      {
        $chart_data[$nick][$x]=$chart_data[$nick][$x]+1;
      }
      else
      {
        $chart_data[$nick][$x]=1;
      }
      $max_lines_per_pixel=max($max_lines_per_pixel,$chart_data[$nick][$x]);
    }
  }

  $dl=$max_lines_per_pixel/255; # number of lines per color gradient (decimal)

  # paint chart
  $buffer=imagecreatetruecolor($w,$h);

  $color_bg=imagecolorallocate($buffer,255,255,255);
  imagefill($buffer,0,0,$color_bg);

  $y=0;
  foreach ($chart_data as $nick => $data)
  {
    $y++;
    $py1=chart__r2p_y($h,$min_y,$max_y,$y)+round(0.3*chart__ppu($h,$min_y,$max_y));
    $py2=chart__r2p_y($h,$min_y,$max_y,$y)-round(0.3*chart__ppu($h,$min_y,$max_y));
    foreach ($chart_data[$nick] as $px => $intensity)
    {
      $color_x=imagecolorallocate($buffer,0,0,round($dl*$intensity));
      imageline($buffer,$px,$py1,$px,$py2,$color_x);
    }
  }

  #scale_img($buffer,0.5,$w,$h);
  imagepng($buffer,$filename.".png");
  imagedestroy($buffer);
}

#####################################################################################################

?>
