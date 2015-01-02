<?php

#####################################################################################################

/*
exec:~part|5|0|0|1|||||php scripts/part.php %%dest%% %%trailing%%
*/

#####################################################################################################

$locked_chans=array("#");

$dest=strtolower($argv[1]);
$trailing=$argv[2];

if ((in_array($dest,$locked_chans)==True) and ($trailing==""))
{
  return;
}

if ((in_array($trailing,$locked_chans)==True) and ($trailing<>""))
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

#####################################################################################################

?>
