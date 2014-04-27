<?php

# gpl2
# by crutchy
# 27-april-2014

$admin_nicks=array("crutchy");
if (in_array($argv[1],$admin_nicks)==False)
{
  return;
}
echo ":exec BUCKET_SET :".serialize(array())."\n";
echo "IRC_MSG bucket flushed\n";

?>
