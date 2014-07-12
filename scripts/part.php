<?php

# gpl2
# by crutchy
# 12-july-2014

$locked_chans=array("#");

$dest=strtolower($argv[1]);
$trailing=$argv[2];

/*if (in_array($dest,$locked_chans)==True)
{
  if (($trailing<>"") or (in_array($trailing,$locked_chans)==True))
  {
    return;
  }
}*/

term_echo("part working");

if ($trailing=="")
{
  echo "/IRC PART $dest :bye\n";
}
else
{
  echo "/IRC PART $trailing :bye\n";
}

?>
