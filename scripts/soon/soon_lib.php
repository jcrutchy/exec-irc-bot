<?php

#####################################################################################################

define("TRANSLATIONS_FILE",__DIR__."/soon_translations");

#####################################################################################################

function load_translations()
{
  if (file_exists(TRANSLATIONS_FILE)==False)
  {
    term_echo("*** TRANSLATIONS FILE NOT FOUND: ".TRANSLATIONS_FILE);
    return False;
  }
  $data=file_get_contents(TRANSLATIONS_FILE);
  if ($data===False)
  {
    term_echo("*** ERROR LOADING TRANSLATIONS FILE: ".TRANSLATIONS_FILE);
    return False;
  }
  $data=explode(PHP_EOL,trim($data));
  $translations=array();
  if (count($data)%2<>0)
  {
    term_echo("*** TRANSLATIONS FILE CONTAINS INVALID NUMBER OF LINES: ".TRANSLATIONS_FILE);
    return False;
  }
  for ($i=0;$i<count($data);$i=$i+2)
  {
    $key=trim($data[$i]);
    $value=trim($data[$i+1]);
    if (($key=="") or ($value==""))
    {
      term_echo("*** TRANSLATION NO. ".($i+1)." CONTAINS EMPTY KEY OR VALUE: ".TRANSLATIONS_FILE);
      return False;
    }
    $translations[$key]=trim($value);
  }
  return $translations;
}

#####################################################################################################

?>