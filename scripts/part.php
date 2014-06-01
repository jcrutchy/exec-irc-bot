<?php

# gpl2
# by crutchy
# 1-june-2014

$locked_chans=array("#soylent","#test","#civ","#*");

if (in_array(strtolower($argv[1]),$locked_chans)==True)
{
  return;
}

if ($argv[2]=="")
{
  echo "IRC_RAW PART ".$argv[1]." :bye\n";
}
else
{
  echo "IRC_MSG this command must have no additional parameters and must be executed in the channel you want exec to leave\n";
}

?>
