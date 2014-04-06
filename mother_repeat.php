<?php

# gpl2
# by crutchy
# 6-april-2014

if ($argc<3)
{
  echo "error 1";
  return;
}
if ($argv[1]<>"irc")
{
  echo "error 2";
  return;
}
$items=unserialize($argv[2]);
echo $items["msg"];

?>
