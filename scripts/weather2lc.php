<?php

$codes=unserialize(file_get_contents("weather.codes"));
$codes2=array();
foreach ($codes as $name => $location)
{
  $lname=strtolower($name);
  if (isset($codes2[$lname])==False)
  {
    $codes2[$lname]=$location;
  }
}
file_put_contents("weather.codes",serialize($codes2));

?>
