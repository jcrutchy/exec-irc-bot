<?php

#####################################################################################################

function utils__load_settings($filename)
{
  $data=file_get_contents($filename);
  $data=explode("\n",$data);
  $settings=array();
  for ($i=0;$i<count($data);$i++)
  {
    $parts=explode("=",$data[$i]);
    $key=$parts[0];
    array_shift($parts);
    $value=implode("=",$parts);
    $settings[$key]=$value;
  }
  return $settings;
}

#####################################################################################################

?>
