<?php

# gpl2
# by crutchy
# 25-june-2014

$locked_chans=array("#");

$dest=strtolower($argv[1]);
$trailing=$argv[2];

if (in_array($dest,$locked_chans)==True)
{
  return;
}

if ($trailing=="")
{
  echo "/IRC PART $dest :bye\n";
}
else
{
  echo "/IRC PART $trailing :bye\n";
}

?>
