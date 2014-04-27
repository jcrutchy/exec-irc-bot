<?php

# gpl2
# by crutchy
# 26-april-2014

# needs updating

$test["level1"]["level2"]="blah";
$data=serialize($test);
echo ":exec BUCKET_SET :$data\n";

echo ":exec BUCKET_GET :\$buckets\n";
$f=fopen("php://stdin","r");
$line=fgets($f);
if ($line===False)
{
  echo "IRC_MSG ERROR\n";
}
else
{
  echo "RESULT:\n";
  $result=unserialize($line);
  var_dump($result);
  echo "IRC_MSG $line\n";
}
fclose($f);

?>
