<?php

# gpl2
# by crutchy
# 25-june-2014

$locked_chans=array("#");

if (in_array(strtolower($argv[1]),$locked_chans)==True)
{
  return;
}

if ($argv[2]=="")
{
  echo "/IRC PART ".$argv[1]." :bye\n";
}
else
{
  echo "/PRIVMSG this command must have no additional parameters and must be executed in the channel you want exec to leave\n";
}

?>
