<?php

# gpl2
# by crutchy

#####################################################################################################

function chart__ppu($pix,$min,$max)
{
  return (($pix-1)/($max-$min));
}

#####################################################################################################

function chart__r2p_x($w,$min_x,$max_x,$rx)
{
  return round(($rx-$min_x)*chart__ppu($w,$min_x,$max_x));
}

#####################################################################################################

function chart__r2p_y($h,$min_y,$max_y,$ry)
{
  return ($h-1-round(($ry-$min_y)*chart__ppu($h,$min_y,$max_y)));
}

#####################################################################################################

function chart__p2r_x($w,$min_x,$max_x,$px)
{
  return $px/chart__ppu($w,$min_x,$max_x)+$min_x;
}

#####################################################################################################

function chart__p2r_y($h,$min_y,$max_y,$py)
{
  return (($h-1-$py)/chart__ppu($h,$min_y,$max_y)+$min_y);
}

#####################################################################################################

function scale_img(&$buffer,$scale,$w,$h)
{
  $final_w=round($w*$scale);
  $final_h=round($h*$scale);
  $buffer_resized=imagecreatetruecolor($final_w,$final_h);
  if (imagecopyresampled($buffer_resized,$buffer,0,0,0,0,$final_w,$final_h,$w,$h)==False)
  {
    term_echo("imagecopyresampled error");
    return False;
  }
  imagedestroy($buffer);
  $buffer=imagecreate($final_w,$final_h);
  if (imagecopy($buffer,$buffer_resized,0,0,0,0,$final_w,$final_h)==False)
  {
    term_echo("imagecopy error");
    return False;
  }
  imagedestroy($buffer_resized);
}

#####################################################################################################

?>
